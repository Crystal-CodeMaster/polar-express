<?php
header("Content-Type: application/json");
require 'db_connection.php';
session_start(); // Ensure session is started to access user data

// Decode incoming data
$data = json_decode(file_get_contents("php://input"), true);

// Validate incoming data structure
if (!$data || empty($data) || !isset($data[0]['slotId']) || !isset($data[0]['partySize'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data structure or missing required fields']);
    exit;
}

// Ensure the volunteer is logged in
$volunteerId = $_SESSION['volunteer_id'] ?? null;
if (!$volunteerId) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $db->exec('BEGIN TRANSACTION');

    foreach ($data as $ride) {
        $slotId = (int) $ride['slotId'];
        $partySize = (int) $ride['partySize'];
        $notes = $ride['notes'] ?? '';

        $rideStmt = $db->prepare("SELECT ride_id FROM volunteer_slots WHERE id = :slot_id");
        $rideStmt->bindValue(':slot_id', $slotId, SQLITE3_INTEGER);
        $result = $rideStmt->execute();
        $ride = $result->fetchArray(SQLITE3_ASSOC);
        if (!$ride) {
            die(json_encode(['success' => false, 'message' => 'No ride found for this slot ID.']));
        }

        $rideId = $ride['ride_id'];
        $slotStmt = $db->prepare("SELECT id FROM volunteer_slots WHERE ride_id = :ride_id");
        $slotStmt->bindValue(':ride_id', $rideId, SQLITE3_INTEGER);
        $result = $slotStmt->execute();
        $slots = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $slots[] = $row['id'];
            $signupStmt = $db->prepare("SELECT * FROM volunteer_signups WHERE volunteer_id = :volunteer_id AND slot_id = :slot_id");
            $signupStmt->bindValue(':volunteer_id', $volunteerId, SQLITE3_INTEGER);
            $signupStmt->bindValue(':slot_id', $row['id'], SQLITE3_INTEGER);
            $result = $signupStmt->execute();
            $signups = $result->fetchArray(SQLITE3_ASSOC);
        }
        if (!empty($signups)) {
            die(json_encode(['success' => false, 'message' => 'You have already registered this slot.']));
            exit;
        }

        // Check if user already registered for this slot
        $checkExistingStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM volunteer_signups 
            WHERE slot_id = :slot_id AND volunteer_id = :volunteer_id
        ");
        $checkExistingStmt->bindValue(':slot_id', $slotId, SQLITE3_INTEGER);
        $checkExistingStmt->bindValue(':volunteer_id', $volunteerId, SQLITE3_INTEGER);
        $result = $checkExistingStmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);

        if ($existing['count'] > 0) {
            echo json_encode(['success' => false, 'message' => "You have already registered this slot."]);
            $db->exec('ROLLBACK');
            exit;
        }

        // Check if the ride has enough available slots
        $availabilityStmt = $db->prepare("
            SELECT max_volunteers - COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = :slot_id), 0) AS available_slots
            FROM volunteer_slots
            WHERE id = :slot_id
        ");
        $availabilityStmt->bindValue(':slot_id', $slotId, SQLITE3_INTEGER);
        $availabilityResult = $availabilityStmt->execute();
        $availability = $availabilityResult->fetchArray(SQLITE3_ASSOC);

        if (!$availability || $partySize > $availability['available_slots']) {
            echo json_encode(['success' => false, 'message' => "Not enough available slots for Slot."]);
            $db->exec('ROLLBACK');
            exit;
        }

        // Update remaining spots in shift_availability_cache
        $updateStmt = $db->prepare("UPDATE shift_availability_cache 
            SET remaining_spots = remaining_spots - :party_size, 
                is_full = CASE 
                    WHEN (remaining_spots - :party_size) <= 0 THEN 1 
                    ELSE 0 
                END
            WHERE shift_id = :slot_id ");
        $updateStmt->bindValue(':party_size', $partySize, SQLITE3_INTEGER);
        $updateStmt->bindValue(':slot_id', $slotId, SQLITE3_INTEGER);
        $updateStmt->execute();

        // Insert the volunteer signup
        $stmt = $db->prepare("
            INSERT INTO volunteer_signups (slot_id, volunteer_id, num_people, notes)
            VALUES (:slot_id, :volunteer_id, :num_people, :notes)
        ");
        $stmt->bindValue(':slot_id', $slotId, SQLITE3_INTEGER);
        $stmt->bindValue(':volunteer_id', $volunteerId, SQLITE3_INTEGER);
        $stmt->bindValue(':num_people', $partySize, SQLITE3_INTEGER);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->execute();
    }

    $db->exec('COMMIT');
    echo json_encode(['success' => true, 'message' => 'Selections successfully submitted']);
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
