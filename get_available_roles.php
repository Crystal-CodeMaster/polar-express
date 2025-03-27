<?php
// Prevent any output before headers
ob_start();

// Include database connection
require 'db_connection.php';

// Set headers
header('Content-Type: application/json');

try {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Get POST data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if ($data === null) {
        throw new Exception('Invalid JSON data received');
    }

    // Extract parameters
    $roleId = intval($data['role'] ?? null);
    $rideId = intval($data['shift'] ?? null);
    $day = $data['day'] ?? null;
    $time = $data['startTime'] ?? null;

    $shift_start = trim(strval(date('h:i A', strtotime($time) + 900)));
    
    // Validate required fields
    if (!$roleId || !$rideId || !$day || !$time) {
        throw new Exception("Missing required parameters");
    }

    // Get ride_id from rides table based on start time
    $getRideQuery = $db->prepare('SELECT id FROM rides WHERE day = :day AND time = :shift_start');
    $getRideQuery->bindValue(':shift_start', $shift_start, SQLITE3_TEXT);
    $getRideQuery->bindValue(':day', $day, SQLITE3_TEXT);
    $rideResult = $getRideQuery->execute();
    
    if (!$rideResult) {
        throw new Exception("Failed to fetch ride_id");
    }
    
    $rideRow = $rideResult->fetchArray(SQLITE3_ASSOC);
    if (!$rideRow) {
        throw new Exception("No ride found for the given start time");
    }
    
    $ride_id = $rideRow['id'];

    // Prepare and execute query to get volunteer slots
    $stmt = $db->prepare('SELECT id FROM volunteer_slots WHERE ride_id = :ride_id');
    $stmt->bindValue(':ride_id', $ride_id, SQLITE3_INTEGER);
    
    // Execute query
    $result = $stmt->execute();
    
    // Fetch results into a simple array of IDs
    $slotIds = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $slotIds[] = $row['id'];
    }
    // Debug array properly using print_r()

    // Fetch availability data for each slot
    $arrays = [];
    foreach ($slotIds as $slotId) {
        $availabilityStmt = $db->prepare('SELECT * FROM shift_availability_cache WHERE shift_id = :shift_id');
        $availabilityStmt->bindValue(':shift_id', $slotId, SQLITE3_INTEGER);
        $availabilityResult = $availabilityStmt->execute();
        
        while ($availRow = $availabilityResult->fetchArray(SQLITE3_ASSOC)) {
            $arrays[] = $availRow;
        }
    }


    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $arrays,
        'message' => empty($arrays) ? 'No data found' : 'Data retrieved successfully'
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Close database connection
$db->close();
