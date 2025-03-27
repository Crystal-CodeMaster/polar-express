<?php
session_start(); // Start the session

// Destroy only the admin session (without affecting volunteers)
unset($_SESSION['admin_logged_in']); 

// Redirect to the homepage
header("Location: index.php");
exit;
?>
