<?php
try {
    $db = new SQLite3('admin_users.db'); // Ensure this matches the renamed database name
    $filePath = 'populate_database.sql'; // Ensure this matches the SQL file name

    // Check if the SQL file exists
    if (!file_exists($filePath)) {
        throw new Exception("SQL file not found at: " . realpath($filePath));
    }

    // Read the SQL file
    $sql = file_get_contents($filePath);
    if (!$sql) {
        throw new Exception("Failed to read SQL file at: " . $filePath);
    }

    // Execute each command in the SQL file
    foreach (explode(';', $sql) as $command) {
        $command = trim($command); // Remove extra whitespace
        if (!empty($command)) {
            if (!$db->exec($command)) {
                throw new Exception("SQL Error: " . $db->lastErrorMsg());
            }
        }
    }

    echo "Database populated successfully!";
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
