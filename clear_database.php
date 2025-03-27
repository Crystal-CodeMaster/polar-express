<?php
header('Content-Type: application/json'); // Ensure JSON response
require 'db_connection.php';
try {
        
    // Delete all records from the volunteers table
    $sql = "DELETE FROM volunteers";
    $result = $db->exec($sql);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "All volunteer records cleared successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to clear volunteer records."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
exit;