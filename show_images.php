<?php
header("Content-Type: application/json");
require 'db_connection.php';
session_start();

$sql = "SELECT f.id, f.path, f.caption, f.header_id, h.header_text FROM file_uploads f 
    JOIN header_table h ON f.header_id = h.id
    ORDER BY h.id DESC, f.id DESC";
$imageStmt = $db->query($sql);

$images = [];
while ($row = $imageStmt->fetchArray(SQLITE3_ASSOC)) {
    $images[] = $row;
}

echo json_encode($images); 
?>