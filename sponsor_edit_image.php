<?php


require 'db_connection.php';
session_start();
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($data['id']);
    $header_id = intval($data['header_id']);
    
    $stmt = $db->prepare("UPDATE sponsor_file_uploads SET header_id = :header_id WHERE id = :id");
    $stmt->bindValue(':header_id', $header_id, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}   
?>