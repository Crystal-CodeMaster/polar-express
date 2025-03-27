<?php
require 'db_connection.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$signupId = $data['signupId'] ?? null;
$groupSize = $data['numPeople'] ?? null;

if ($signupId) {
    
    $stmt = $db->prepare("
        UPDATE shift_availability_cache 
        SET 
            remaining_spots = remaining_spots + (
                SELECT num_people FROM volunteer_signups WHERE id = :signupId
        ),
        is_full = CASE 
            WHEN remaining_spots + (
                SELECT num_people FROM volunteer_signups WHERE id = :signupId
            ) > 0 THEN 0 
            ELSE is_full 
        END 
        WHERE shift_id = (
            SELECT slot_id FROM volunteer_signups WHERE id = :signupId
        );
    ");
    $stmt->bindValue(':signupId', $signupId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $stmt = $db->prepare("DELETE FROM volunteer_signups WHERE id = :signupId");
    $stmt->bindValue(':signupId', $signupId, SQLITE3_INTEGER);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete signup.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid signup ID.']);
}
