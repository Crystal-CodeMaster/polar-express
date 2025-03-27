<?php
try {
    $db = new SQLite3('admin_users.db'); // Use the renamed database

    // Load the update schema SQL
    $filePath = 'update_schema.sql';
    if (!file_exists($filePath)) {
        throw new Exception("SQL file not found at: " . realpath($filePath));
    }

    $sql = file_get_contents($filePath);
    if (!$sql) {
        throw new Exception("Failed to read SQL file.");
    }

    // Execute each SQL command
    foreach (explode(';', $sql) as $command) {
        $command = trim($command);
        if (!empty($command)) {
            if (!$db->exec($command)) {
                throw new Exception("SQL Error: " . $db->lastErrorMsg());
            }
        }
    }

    echo "Database updated successfully!";
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
