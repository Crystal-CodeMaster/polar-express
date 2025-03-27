<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the correct database
$db = new SQLite3('admin_users.db');

// Check if a page name is provided
if (!isset($_GET['page'])) {
    die("Error: No page specified.");
}

$page = $_GET['page'];

// Fetch content for the selected page
$stmt = $db->prepare("SELECT content FROM page_content WHERE page_name = :page");
$stmt->bindValue(':page', $page, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

echo $row['content'] ?? "";
?>
