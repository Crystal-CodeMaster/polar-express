<?php
$db = new SQLite3('admin_users.db');

$footerStmt = $db->prepare("SELECT content FROM page_content WHERE page_name = 'footer'");
$footerResult = $footerStmt->execute();
$footerRow = $footerResult->fetchArray(SQLITE3_ASSOC);

if ($footerRow && isset($footerRow['content'])) {
    echo "<p>Database Fetch Success: " . htmlspecialchars($footerRow['content']) . "</p>";
} else {
    echo "<p>Database Fetch Failed.</p>";
}
?>
