<?php
require 'db_connection.php'; // Ensure this includes your database connection

// Get JSON data from frontend
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data["path"]) && isset($data["caption"])) {
    $path = $data["path"];
    $caption = $data["caption"];
    $id = $data["id"];
    // Update the caption in the database
    $stmt = $db->prepare("UPDATE file_uploads SET caption = :caption WHERE id = :id");

    $stmt->bindValue(':caption', $caption);
    $stmt->bindValue(':id', $id);
    $result = $stmt->execute();


    if ($result) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update."]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data."]);
}
?>