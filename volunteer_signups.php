<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php';

try {
    $query = "
        CREATE TABLE IF NOT EXISTS volunteer_signups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            volunteer_id INTEGER NOT NULL,
            slot_id INTEGER NOT NULL,
            num_people INTEGER NOT NULL DEFAULT 1,
            notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (volunteer_id) REFERENCES volunteers (id),
            FOREIGN KEY (slot_id) REFERENCES volunteer_slots (id)
        );
    ";
    $db->exec($query);
    echo "Table 'volunteer_signups' created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
