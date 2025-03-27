<?php
try {
    $db = new SQLite3('admin_users.db');
    
    echo "Modifying the 'volunteers' table schema...\n";

    // Start a transaction for safety
    $db->exec('BEGIN TRANSACTION');

    // Create a new volunteers table without the unnecessary columns
    $db->exec("
        CREATE TABLE volunteers_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT,
            password TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Transfer data from the old table to the new table
    $db->exec("
        INSERT INTO volunteers_new (id, name, email, phone, password, created_at)
        SELECT id, name, email, phone, password, created_at
        FROM volunteers
    ");

    // Drop the old table
    $db->exec("DROP TABLE volunteers");

    // Rename the new table to the original table name
    $db->exec("ALTER TABLE volunteers_new RENAME TO volunteers");

    // Commit the transaction
    $db->exec('COMMIT');

    echo "Schema modification completed successfully.\n";
} catch (Exception $e) {
    // Rollback on error
    $db->exec('ROLLBACK');
    echo "Error: " . $e->getMessage();
}
?>
