<?php
// Enable error reporting for debugging (remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to SQLite database
$db = new SQLite3('database.db');

// Get footer content from the database
$stmt = $db->prepare("SELECT content FROM page_content WHERE page_name = 'footer'");
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$footer_content = $row['content'] ?? "© 2024 Polar Express";
?>

<!-- Footer Section -->
<div style="width: 100%; background-color:#000000; height: 70px; text-align: center; font-size: 16px; display: flex; align-items: center; justify-content: center;">
    <p style="margin: 0; color: #ffffff;"><?php echo $footer_content; ?></p>
</div>
