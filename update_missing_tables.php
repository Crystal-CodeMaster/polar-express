<?php
try {
    // Connect to the SQLite database
    $db = new SQLite3('admin_users.db');

    // Add the volunteer_roles table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS volunteer_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_name TEXT NOT NULL,
            visible INTEGER NOT NULL DEFAULT 1
        );
    ");

    // Add the ride_visibility table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS ride_visibility (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ride_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            visible INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (ride_id) REFERENCES rides (id),
            FOREIGN KEY (role_id) REFERENCES volunteer_roles (id)
        );
    ");

    // Add the users table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            phone TEXT
        );
    ");

    echo "Missing tables added successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
