<?php
// Enable error reporting for debugging (remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if data is received
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['page']) && isset($_POST['content'])) {
    $page = $_POST['page'];
    $content = $_POST['content'];

    // Connect to the database
    $db = new SQLite3('admin_users.db');

    // Ensure the table exists
    $db->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_name TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL
    )");

    // Insert or update content
    $stmt = $db->prepare("INSERT INTO page_content (page_name, content) VALUES (:page, :content)
        ON CONFLICT(page_name) DO UPDATE SET content = excluded.content");
    $stmt->bindValue(':page', $page, SQLITE3_TEXT);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error saving content.";
    }
} else {
    echo "Invalid request.";
}
?>
