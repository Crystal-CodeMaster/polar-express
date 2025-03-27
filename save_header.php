<?php
require 'db_connection.php'; // Ensure this connects to SQLite
session_start();

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['header'])) {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

$header = $data['header'];
$sub = $data['sub'];

if (empty($header)) {
    echo json_encode(["status" => "error", "message" => "Header cannot be empty"]);
    exit;
}

// Insert data into header_table
$sql = "INSERT INTO header_table (header_text, sub_text) VALUES (:header, :sub)";
$stmt = $db->prepare($sql);
$stmt->bindValue(':header', $header, SQLITE3_TEXT);
$stmt->bindValue(':sub',$sub, SQLITE3_TEXT);


if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$stmt->close();
$db->close();
?>