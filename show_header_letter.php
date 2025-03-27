<?php
// Include database connection
require 'db_connection.php';
session_start();

$sql = "SELECT id, header_text FROM header_table";
$headerStmt = $db->query($sql);
$headers = [];
while ($row = $headerStmt->fetchArray(SQLITE3_ASSOC)) {
    $headers[] = $row;
}
echo json_encode($headers); 
?>