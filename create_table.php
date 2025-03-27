<?php
try {
    $db = new SQLite3('admin_users.db'); // Ensure the database name matches
    $filePath = 'create_admin_users_table.sql';

    if (!file_exists($filePath)) {
        throw new Exception("SQL file not found at: " . realpath($filePath));
    }

    $sql = file_get_contents($filePath);
    if (!$db->exec($sql)) {
        throw new Exception("SQL Error: " . $db->lastErrorMsg());
    }

    echo "Table 'admin_users' created successfully!";
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
