<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
try {
    $data = json_decode(file_get_contents('php://input'), true);

    $slot_id = $data['current_slot_id'];
    $role_id = $data['current_role_id'];
    $filled_slots_value = $data['filled_slots_value'];
    $remaining_slots = $data['remaining_slots'];
    $max_volunteers = $data['max_volunteers'];

    $sql = "UPDATE volunteer_slots SET max_volunteers = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$max_volunteers, $slot_id]);

    $sql = "UPDATE shift_availability_cache 
            SET remaining_spots = ?, if_full = CASE WHEN ? = 0 THEN 1 ELSE if_full END
            WHERE shift_id = ? AND role_id = ? AND remaining_spots != 0 AND if_full != 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$remaining_slots, $remaining_slots, $slot_id, $role_id]);

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
