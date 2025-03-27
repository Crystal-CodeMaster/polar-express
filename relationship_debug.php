<?php
try {
    $db = new SQLite3('admin_users.db');

    $query = "
        SELECT vs.id AS slot_id, vs.category, vs.ride_id, rv.role_id, r.day, r.time, rv.visible
        FROM volunteer_slots vs
        JOIN ride_visibility rv ON vs.ride_id = rv.ride_id
        JOIN rides r ON vs.ride_id = r.id
        WHERE rv.role_id = 4 AND rv.visible = 1
    ";
    $result = $db->query($query);

    echo "<h1>Relationship Debugging</h1>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Slot ID</th><th>Category</th><th>Ride ID</th><th>Role ID</th><th>Day</th><th>Time</th><th>Visible</th></tr>";

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['slot_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ride_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['day']) . "</td>";
        echo "<td>" . htmlspecialchars($row['time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['visible']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
