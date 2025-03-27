<?php
try {
    $db = new SQLite3('admin_users.db');

    // Delete all entries from the volunteer-related tables
    $db->exec("DELETE FROM volunteer_signups");
    $db->exec("DELETE FROM volunteers");
    $db->exec("DELETE FROM volunteer_slots");

    // Vacuum the database
    $db->exec("VACUUM");

    echo "All volunteer data has been cleared and tables are consistent with the schema.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
