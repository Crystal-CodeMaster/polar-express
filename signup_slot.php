<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the slot_id parameter is provided
if (!isset($_GET['slot_id'])) {
    echo "Error: Slot ID not provided.";
    exit;
}

// Get the slot_id from the URL
$slot_id = intval($_GET['slot_id']);

// Simulate a logged-in user (replace with session logic later)
$user_id = 1;

try {
    // Connect to the database
    $db = new SQLite3('admin_users.db');

    // Fetch slot details
    $query = "
        SELECT vs.id AS slot_id, r.day, r.time, vs.max_volunteers,
               COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS filled_volunteers
        FROM volunteer_slots vs
        JOIN rides r ON vs.ride_id = r.id
        WHERE vs.id = :slot_id
    ";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':slot_id', $slot_id, SQLITE3_INTEGER);
    $slot = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$slot) {
        echo "Error: Slot not found.";
        exit;
    }

    // Calculate availability
    $available = $slot['max_volunteers'] - $slot['filled_volunteers'];
    if ($available <= 0) {
        echo "Error: Slot is full.";
        exit;
    }

    // Insert signup into the database
    $insertQuery = "
        INSERT INTO volunteer_signups (ride_id, slot_id, user_id, num_people, notes)
        VALUES ((SELECT ride_id FROM volunteer_slots WHERE id = :slot_id), :slot_id, :user_id, 1, '')
    ";
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(':slot_id', $slot_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();

    echo "Successfully signed up for the slot!";
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
