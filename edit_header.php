<?php

require 'db_connection.php';
header('Content-Type: application/json');
session_start();

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['id']) || !isset($data['header_text'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

$id = $data['id'];
$header_text = $data['header_text'];
$sub_text = $data['sub_text'];

$sql = "UPDATE header_table SET header_text = :header_text, sub_text = :sub_text WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->bindValue(':header_text', $header_text, SQLITE3_TEXT);
$stmt->bindValue(':sub_text', $sub_text, SQLITE3_TEXT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Header updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update header"]);
}




?>