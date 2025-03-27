<?php

require 'db_connection.php';
session_start();

$sql = "SELECT id, header_text, sub_text FROM header_table";
$result = $db->query($sql);
$headers = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $headers[] = $row;
}

echo json_encode($headers);
?>
