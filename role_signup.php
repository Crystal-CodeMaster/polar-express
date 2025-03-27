<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the role_id parameter is provided
if (!isset($_GET['role_id'])) {
    echo "Error: Role ID not provided.";
    exit;
}

// Get the role_id from the URL
$role_id = intval($_GET['role_id']);

// Connect to the database
try {
    $db = new SQLite3('admin_users.db');

    // Fetch role details
    $query = "SELECT role_name FROM volunteer_roles WHERE id = :role_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$result) {
        echo "Error: Role not found.";
        exit;
    }

    // Display role name
    $role_name = htmlspecialchars($result['role_name']);
    echo "<h1>Role: $role_name</h1>";

    // Fetch slots for the role
    $slotsQuery = "
        SELECT vs.id AS slot_id, vs.category, r.day AS day, r.time AS time, vs.max_volunteers AS max_volunteers,
               COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS filled_volunteers
        FROM volunteer_slots vs
        JOIN rides r ON vs.ride_id = r.id
        JOIN ride_visibility rv ON vs.ride_id = rv.ride_id
        WHERE rv.role_id = :role_id AND rv.visible = 1
        ORDER BY r.day, r.time
    ";

    $stmt = $db->prepare($slotsQuery);
    $stmt->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $slotsResult = $stmt->execute();

    // Debugging Output for the Query
    echo "<pre>Debugging Query Results:\n";
    while ($row = $slotsResult->fetchArray(SQLITE3_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";

    // Reset the result for rendering
    $stmt = $db->prepare($slotsQuery);
    $stmt->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $slotsResult = $stmt->execute();

    // Render slots in a table
    if ($slotsResult) {
        $hasSlots = false; // Track if any slots are available

        echo '<table class="table">';
        echo '<thead><tr><th>Day</th><th>Time</th><th>Max Volunteers</th><th>Filled Volunteers</th><th>Action</th></tr></thead>';
        echo '<tbody>';

        while ($row = $slotsResult->fetchArray(SQLITE3_ASSOC)) {
            $hasSlots = true;

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['day']) . '</td>';
            echo '<td>' . htmlspecialchars($row['time']) . '</td>';
            echo '<td>' . htmlspecialchars($row['max_volunteers']) . '</td>';
            echo '<td>' . htmlspecialchars($row['filled_volunteers']) . '</td>';
            echo '<td><a href="signup.php?slot_id=' . $row['slot_id'] . '" class="btn btn-primary btn-sm">Sign Up</a></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        if (!$hasSlots) {
            echo '<p>No available slots for this role.</p>';
        }
    } else {
        echo '<p>No available slots for this role.</p>';
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
