<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require 'db_connection.php';

// Set headers
header('Content-Type: application/json');

try {
    // Read raw JSON input
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // Extract parameters
    $roleId = $data['role'] ?? null;
    $shiftId = $data['shift'] ?? null;
    $signupId = $data['signupId'] ?? null;

    $time = $data['time'] ?? null;
    $day = $data['day'] ?? null;

    if($time == "09:30 AM"){
        $time = "09:45 AM";
    }

    $shift_start = date('h:i A', strtotime($time) + 900);
    $days = DateTime::createFromFormat('Y-m-d', $day)->format('Y-m-d');


    $rideStmt = $db->prepare("SELECT id FROM rides WHERE day = :day AND time = :time");
    $rideStmt->bindValue(':day', $days, SQLITE3_TEXT);
    $rideStmt->bindValue(':time', $shift_start, SQLITE3_TEXT);
    $rideResult = $rideStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$rideResult) {
        throw new Exception("Ride not found for day: $days and time: $shift_start ");
    }
    $rideId = $rideResult['id'];


    $slotStmt = $db->prepare("SELECT id FROM volunteer_slots WHERE ride_id = :ride_id AND role_id = :role_id");
    $slotStmt->bindValue(':ride_id', $rideId, SQLITE3_TEXT);
    $slotStmt->bindValue(':role_id', $roleId, SQLITE3_TEXT);
    $slotResult = $slotStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$slotResult) {
        throw new Exception("Volunteer slot not found for ride_id: $rideId and role_id: $roleId");
    }
    $volunteerSlotId = $slotResult['id'];

    // Prepare SQL query
    $stmt = $db->prepare("SELECT remaining_spots 
        FROM shift_availability_cache 
        WHERE role_id = :roleId AND shift_id = :voltnteerslotId
    ");

    // Bind parameters correctly
    $stmt->bindValue(':voltnteerslotId', $volunteerSlotId, SQLITE3_TEXT);
    $stmt->bindValue(':roleId', $roleId, SQLITE3_TEXT);

    // Execute query
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception("Database query failed");
    }

    // Fetch results
    $arrays  = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $arrays[] = $row;
    }
    echo json_encode([
        'success' => !empty($arrays),
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
