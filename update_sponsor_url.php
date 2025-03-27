<?php
require 'db_connection.php';

// Check for both GET and POST requests
$data = json_decode(file_get_contents("php://input"), true);

// If JSON data is not received, check if data was sent via GET parameters
if (!$data) {
    $data = $_REQUEST; // This will check both $_POST and $_GET
}

// Debugging: Save received data to a log file
file_put_contents("debug_log.txt", print_r($data, true));

if (!isset($data['id']) || !isset($data['url'])) {
    echo json_encode(["status" => "error", "message" => "Invalid data received", "received" => $data]);
    exit;
}

$id = intval($data['id']);
$url = trim($data['url']);

$stmt = $db->prepare("UPDATE sponsor_file_uploads SET url = ? WHERE id = ?");
$stmt->bindValue(1, $url, SQLITE3_TEXT);
$stmt->bindValue(2, $id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
?>
