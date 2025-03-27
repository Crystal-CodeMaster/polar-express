<?php
try {
    // Connect to the SQLite database
    $db = new SQLite3('admin_users.db');

    // Create tables if they don't already exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS rides (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            day TEXT NOT NULL,
            time TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS volunteer_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ride_id INTEGER NOT NULL,
            category TEXT NOT NULL,
            max_volunteers INTEGER NOT NULL,
            FOREIGN KEY (ride_id) REFERENCES rides (id)
        );
    ");

    // Seed default data
    $db->exec("
        INSERT INTO rides (day, time) VALUES
        ('Saturday', '09:30 AM'),
        ('Saturday', '11:00 AM'),
        ('Saturday', '12:30 PM'),
        ('Sunday', '09:30 AM'),
        ('Sunday', '11:00 AM');
    ");

    $db->exec("
        INSERT INTO volunteer_slots (ride_id, category, max_volunteers) VALUES
        (1, 'Jolly People', 8),
        (1, 'Elves', 12),
        (1, 'Chefs', 6),
        (1, 'Conductors', 3),
        (2, 'Jolly People', 8),
        (2, 'Elves', 12);
    ");

    echo "Database updated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
