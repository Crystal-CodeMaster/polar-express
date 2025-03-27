<?php

require 'db_connection.php';
session_start();

$sql = "SELECT f.id, f.path, f.header_id, h.header_text, h.sub_text FROM sponsor_file_uploads f 
    JOIN sponsor_header_table h ON f.header_id = h.id
    ORDER BY h.id DESC, f.id DESC"; // Fetch all images
$result = $db->query($sql);
$images = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $images[] = $row;
}

echo json_encode($images);
?>
