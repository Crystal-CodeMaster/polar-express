<?php
try {
    $db = new SQLite3('admin_users.db');

    $db->exec("
        INSERT INTO ride_visibility (ride_id, role_id, visible) VALUES
        (1, 1, 1), -- Jolly People, Ride 1
        (1, 2, 1), -- Elves, Ride 1
        (1, 3, 1), -- Chefs, Ride 1
        (1, 4, 1), -- Conductors, Ride 1
        (2, 1, 1), -- Jolly People, Ride 2
        (2, 2, 1), -- Elves, Ride 2
        (2, 3, 1), -- Chefs, Ride 2
        (2, 4, 1), -- Conductors, Ride 2
        (3, 1, 1), -- Jolly People, Ride 3
        (3, 2, 1), -- Elves, Ride 3
        (3, 3, 1), -- Chefs, Ride 3
        (3, 4, 1), -- Conductors, Ride 3
        (4, 1, 1), -- Jolly People, Ride 4
        (4, 2, 1), -- Elves, Ride 4
        (4, 3, 1), -- Chefs, Ride 4
        (4, 4, 1), -- Conductors, Ride 4
        (5, 1, 1), -- Jolly People, Ride 5
        (5, 2, 1), -- Elves, Ride 5
        (5, 3, 1), -- Chefs, Ride 5
        (5, 4, 1), -- Conductors, Ride 5
    ");

    echo "Ride visibility populated successfully!";
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
