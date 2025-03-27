<?php
require 'db_connection.php'; // Ensure this connects to SQLite
session_start();
header('Content-Type: application/json'); 
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

try{
    $sqlCheck = "SELECT COUNT(*) as count FROM sponsor_header_table";
    $result = $db->querySingle($sqlCheck, true);

    if ($result['count'] > 0) {
        echo json_encode(["status" => "error", "message" => "Sponsor have only one header"]);
        exit;
    } 
    // Insert data if no row exists
    $sql = "INSERT INTO sponsor_header_table (header_text, sub_text) VALUES (:header, :sub)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':header', $header, SQLITE3_TEXT);
    $stmt->bindValue(':sub', $sub, SQLITE3_TEXT);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
    
}catch (Exception $e){
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
exit;

$stmt->close();
$db->close();
?>