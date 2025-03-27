<?php
require 'db_connection.php';
session_start();
header('Content-Type: application/json');

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['updates']) || empty($data['updates'])) {
    echo json_encode(['success' => false, 'message' => 'No updates received.']);
    exit;
}

try {
    foreach ($data['updates'] as $update) {
        $id = intval($update['id']);
        $role_name = trim($update['role']);

        $role_number = intval($update['number']);


        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM volunteer_roles WHERE id = :id");

        $checkStmt->bindValue(':id', $id, SQLITE3_INTEGER);

        $result = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result['count'] > 0) {
            // Update the existing record
            $stmt = $db->prepare("UPDATE volunteer_roles SET role_name = :role_name, role_number = :role_number WHERE id = :id");
        } else {
            // Insert a new record
            $stmt = $db->prepare("INSERT INTO volunteer_roles (id, role_name, role_number) VALUES (:id, :role_name, :role_number)");
        }

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':role_name', $role_name, SQLITE3_TEXT);
        $stmt->bindValue(':role_number', $role_number, SQLITE3_INTEGER);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database operation failed.']);
            exit;
        }
        $updateSlotStmt = $db->prepare("UPDATE volunteer_slots
        SET max_volunteers = :role_number 
        WHERE role_id = :id");
        $updateSlotStmt->bindValue(':role_number', $role_number, SQLITE3_INTEGER);
        $updateSlotStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $updateSlotStmt->execute();

        $updateSlotStmt = $db->prepare("UPDATE shift_availability_cache
        SET remaining_spots = :role_number 
        WHERE role_id = :id");
        $updateSlotStmt->bindValue(':role_number', $role_number, SQLITE3_INTEGER);
        $updateSlotStmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $updateSlotStmt->execute();

        echo json_encode(['success' => true, 'message' => "Data updated successfully."]);
    }

    echo json_encode(['success' => true, 'message' => "Data saved successfully."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
