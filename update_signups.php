<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include database connection
require 'db_connection.php';

// Set headers
header('Content-Type: application/json');

// Capture raw POST data
$rawData = file_get_contents('php://input');

// Decode JSON data
$data = json_decode($rawData, true);

// Check if JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON received.']);
    exit;
}

// Check if 'updates' key exists
if (!isset($data['updates']) || empty($data['updates'])) {
    echo json_encode(['success' => false, 'message' => 'No updates received.']);
    exit;
}

try {
    $db->exec('BEGIN TRANSACTION');
    $updatedCount = 0;

    for ($i = 0; $i < count($data['updates']); $i++) {
        for ($j = $i + 1; $j < count($data['updates']); $j++) {
            $firstShiftDay = $data['updates'][$i]['current_values']['date'];
            $secondShiftDay = $data['updates'][$j]['current_values']['date'];
            $firstShiftTime = $data['updates'][$i]['current_values']['time'];
            $secondShiftTime = $data['updates'][$j]['current_values']['time'];

            // Adjust the time format and create proper datetime values
            $firstShiftStart = date('h:i A', strtotime($firstShiftTime) + 900);
            $secondShiftStart = date('h:i A', strtotime($secondShiftTime) + 900);
            $first_days = DateTime::createFromFormat('Y-m-d', $firstShiftDay)->format('Y-m-d');
            $second_days = DateTime::createFromFormat('Y-m-d', $secondShiftDay)->format('Y-m-d');

            // Query for the first ride
            $firstStmt = $db->prepare("SELECT id FROM rides WHERE day = :day AND time = :time");
            $firstStmt->bindValue(':day', $first_days, SQLITE3_TEXT);
            $firstStmt->bindValue(':time', $firstShiftStart, SQLITE3_TEXT);
            $firstResult = $firstStmt->execute(); // Execute the query

            if ($first = $firstResult->fetchArray(SQLITE3_ASSOC)) {
                $firstRideId = $first['id'];
            }
            // Query for the second ride
            $secondStmt = $db->prepare("SELECT id FROM rides WHERE day = :day AND time = :time");
            $secondStmt->bindValue(':day', $second_days, SQLITE3_TEXT);
            $secondStmt->bindValue(':time', $secondShiftStart, SQLITE3_TEXT);
            $secondResult = $secondStmt->execute(); // Execute the query

            if ($second = $secondResult->fetchArray(SQLITE3_ASSOC)) {
                $secondRideId = $second['id'];
            }

            // Compare ride IDs
            if ($firstRideId == $secondRideId) {
                echo json_encode([
                    'success' => false,
                    'message' => "You already have a signup for this shift."
                ]);
                exit;
            }
        }
    }

    foreach ($data['updates'] as $update) {

        $signupId = intval($update['signup_id']);
        $slotId = intval($update['current_values']['shift']);
        $numPeople = intval($update['current_values']['groupSize']);
        $notes = trim($update['current_values']['notes']);
        $roleId = intval($update['current_values']['role']);
        $day = trim($update['current_values']['date']);
        $time = trim($update['current_values']['time']);

        $originalGroupSize = intval($update['original_values']['groupSize']);
        $originalRoleId = trim($update['original_values']['role']);
        $originalSlotId = trim($update['original_values']['shift']);


        if ($numPeople == 0) {
            echo json_encode(['success' => false, 'message' => 'Group size cannot be zero.']);
            exit;
        }

        // Update Original Cache remaining spots in shift_available_cache
        $cacheStmt = $db->prepare("
            UPDATE shift_availability_cache 
            SET 
                remaining_spots = remaining_spots + :num_people,
                is_full = CASE 
                    WHEN is_full = 1 THEN 0 
                    ELSE is_full 
                END
            WHERE shift_id = :original_slot_id AND role_id = :original_role_id
        ");
        $cacheStmt->bindValue(':num_people', $originalGroupSize, SQLITE3_INTEGER);
        $cacheStmt->bindValue(':original_slot_id', $originalSlotId, SQLITE3_INTEGER);
        $cacheStmt->bindValue(':original_role_id', $originalRoleId, SQLITE3_INTEGER);
        if (!$cacheStmt->execute()) {
            throw new Exception("Failed to update remaining spots in cache");
        }

        $shift_start = date('h:i A', strtotime($time) + 900);
        $days = DateTime::createFromFormat('Y-m-d', $day)->format('Y-m-d');

        // Get ride_id based on day and time
        $rideStmt = $db->prepare("
            SELECT id FROM rides 
            WHERE day = :day AND time = :time
        ");
        $rideStmt->bindValue(':day', $days, SQLITE3_TEXT);
        $rideStmt->bindValue(':time', $shift_start, SQLITE3_TEXT);
        $rideResult = $rideStmt->execute();

        if ($ride = $rideResult->fetchArray(SQLITE3_ASSOC)) {
            $rideId = $ride['id'];
        } else {
            throw new Exception("No ride found for date: $days and time: $shift_start");
        }

        // Get volunteer_slot_id based on ride_id and role_id
        $slotStmt = $db->prepare("
            SELECT id FROM volunteer_slots 
            WHERE ride_id = :ride_id AND role_id = :role_id
        ");
        $slotStmt->bindValue(':ride_id', $rideId, SQLITE3_INTEGER);
        $slotStmt->bindValue(':role_id', $roleId, SQLITE3_INTEGER);
        $slotResult = $slotStmt->execute();

        if ($slot = $slotResult->fetchArray(SQLITE3_ASSOC)) {
            $volunteerSlotId = $slot['id'];
        } else {
            throw new Exception("No volunteer slot found for ride_id: $rideId and role_id: $roleId");
        }

        //check remaining spots in cache
        $cacheStmt = $db->prepare("
            SELECT remaining_spots FROM shift_availability_cache 
            WHERE shift_id = :volunteer_slot_id AND role_id = :role_id
        ");
        $cacheStmt->bindValue(':volunteer_slot_id', $volunteerSlotId, SQLITE3_INTEGER);
        $cacheStmt->bindValue(':role_id', $roleId, SQLITE3_INTEGER);
        $cacheResult = $cacheStmt->execute();
        $cache = $cacheResult->fetchArray(SQLITE3_ASSOC);

        if ($cache) {
            $remainingSpots = $cache['remaining_spots'];
            if ($remainingSpots <= 0 || $remainingSpots < $numPeople) {
                echo json_encode([
                    'success' => false,
                    'message' => "Not enough spots available. Please reduce the group size."
                ]);
                exit;
            }
        }

        // Then update the cache with new values
        $newCacheStmt = $db->prepare("
        UPDATE shift_availability_cache 
        SET 
            remaining_spots = remaining_spots - :num_people,
            is_full = CASE 
                WHEN remaining_spots - :num_people <= 0 THEN 1 
                ELSE is_full 
            END
        WHERE shift_id = :volunteer_slot_id AND role_id = :role_id");

        $newCacheStmt->bindValue(':num_people', $numPeople, SQLITE3_INTEGER);
        $newCacheStmt->bindValue(':volunteer_slot_id', $volunteerSlotId, SQLITE3_INTEGER);
        $newCacheStmt->bindValue(':role_id', $roleId, SQLITE3_INTEGER);

        if (!$newCacheStmt->execute()) {
            throw new Exception("Failed to update remaining spots in cache for new slot");
        }

        // Delete volunteer signup
        $stmt = $db->prepare("
            DELETE FROM volunteer_signups 
            WHERE slot_id = :ride_id AND volunteer_id = :volunteer_id
        ");

        $stmt->bindValue(':ride_id', $originalSlotId, SQLITE3_INTEGER);
        $stmt->bindValue(':volunteer_id', $signupId, SQLITE3_INTEGER);


        if ($stmt->execute()) {
            // Insert new volunteer signup
            $insertStmt = $db->prepare("
                INSERT INTO volunteer_signups (volunteer_id, slot_id, num_people, notes)
                VALUES (:signup_id, :slot_id, :num_people, :notes)
            ");

            $insertStmt->bindValue(':signup_id', $signupId, SQLITE3_INTEGER);
            $insertStmt->bindValue(':slot_id', $volunteerSlotId, SQLITE3_INTEGER);
            $insertStmt->bindValue(':num_people', $numPeople, SQLITE3_INTEGER);
            $insertStmt->bindValue(':notes', $notes, SQLITE3_TEXT);

            if ($insertStmt->execute()) {
                $updatedCount++;
            } else {
                $error = $db->lastErrorMsg();
                throw new Exception("SQL Error: $error");
            }
        } else {
            $error = $db->lastErrorMsg();
            throw new Exception("SQL Error: $error");
        }
    }

    // Send a generic success message
    $responseMessage = $updatedCount > 0 ? "All changes successfully recorded." : "No changes were test @debug.";
    $response = ['success' => true, 'message' => $responseMessage];
    $db->exec('COMMIT');

    echo json_encode($response);
} catch (Exception $e) {
    // Handle exceptions
    $errorResponse = ['success' => false, 'message' => $e->getMessage()];
    echo json_encode($errorResponse);
}
