<?php
require 'db_connection.php';

$signupId = $_POST['signupId'];

$stmt = $db->prepare("DELETE FROM volunteer_signups WHERE signup_id = :signupId");
$stmt->bindValue(':signupId', $signupId, SQLITE3_INTEGER);
$stmt->execute();

?>
