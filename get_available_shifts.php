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
    $rideId = $data['shift'] ?? null;
    $signupId = $data['signupId'] ?? null;

    // $numPeople = intval($update['current_values']['groupSize']);
    // $notes = trim($update['current_values']['notes']);
    // $day = trim($update['original_values']['day']);
    // $time = trim($update['original_values']['time']);
    // $originalRoleId = trim($update['original_values']['role']);
    // $originalRideId = trim($update['original_values']['shift']);

    // Validate required fields
    if (!$roleId || !$rideId) {
        throw new Exception("Missing required parameters");
    }

    // Prepare SQL query
    $stmt = $db->prepare("
        SELECT shift_id, role_id, remaining_spots 
        FROM shift_availability_cache 
        WHERE role_id = :roleId
    ");

    // Bind parameters correctly
    $stmt->bindValue(':rideId', $rideId, SQLITE3_TEXT);
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
