<?php
require 'db_connection.php';
header('Content-Type: application/json');
session_start();
$data = json_decode(file_get_contents('php://input'), true);

try {
    $db->exec('BEGIN TRANSACTION');

    // Add error checking for empty or invalid data
    if (!isset($data['originalValues']) || !is_array($data['originalValues'])) {
        throw new Exception('Invalid or missing originalValues data');
    }

    foreach ($data['originalValues'] as $deleteValue) {
        // Add validation for required fields
        if (!isset($deleteValue['shift']) || !isset($deleteValue['groupSize']) || !isset($deleteValue['role'])) {
            throw new Exception('Missing required fields in delete values');
        }

        $shiftId = intval($deleteValue['shift']);
        $numPeople = intval($deleteValue['groupSize']);
        $roleId = intval($deleteValue['role']);
        $signupId = intval($data['signupId']);

        $updateQuery = "UPDATE shift_availability_cache 
        SET remaining_spots = remaining_spots + :numPeople,
            is_full = CASE 
                WHEN remaining_spots + :numPeople > 0 THEN 0 
                ELSE is_full 
            END 
        WHERE shift_id = :shiftId AND role_id = :roleId";
        $stmt = $db->prepare($updateQuery);
        $stmt->bindValue(':numPeople', $numPeople, SQLITE3_INTEGER);
        $stmt->bindValue(':shiftId', $shiftId, SQLITE3_INTEGER);
        $stmt->bindValue(':roleId', $roleId, SQLITE3_INTEGER);
        $result = $stmt->execute();

        // Delete from volunteer_signups
        $deleteQuery = "DELETE FROM volunteer_signups WHERE volunteer_id = :signupId AND slot_id = :shiftId";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindValue(':signupId', $signupId, SQLITE3_INTEGER);
        $stmt->bindValue(':shiftId', $shiftId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    }
    $db->exec('COMMIT');
    echo json_encode(['success' => true, 'message' => 'Successfully deleted all signups']);
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    error_log($e->getMessage()); // Log the error server-side
    echo json_encode(['success' => false, 'message' => 'Failed to delete all signups: ' . $e->getMessage()]);
    exit;
}
