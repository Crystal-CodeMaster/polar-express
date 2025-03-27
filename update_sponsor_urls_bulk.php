<?php
require 'db_connection.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['urls']) || !is_array($data['urls'])) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

foreach ($data['urls'] as $update) {
    if (!isset($update['id']) || !isset($update['url'])) {
        continue;
    }

    $id = intval($update['id']);
    $url = trim($update['url']);

    $stmt = $db->prepare("UPDATE sponsor_file_uploads SET url = ? WHERE id = ?");
    $stmt->bindValue(1, $url, SQLITE3_TEXT);
    $stmt->bindValue(2, $id, SQLITE3_INTEGER);
    $stmt->execute();
}

echo json_encode(["status" => "success"]);
?>