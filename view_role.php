<?php
session_start();


if (!isset($_GET['role_id'])) {
    echo "Error: Role ID not provided.";
    exit;
}

$role_id = intval($_GET['role_id']);

try {
    $db = new SQLite3('admin_users.db');

    // Fetch role name
    $roleQuery = $db->prepare("SELECT role_name FROM volunteer_roles WHERE id = :role_id");
    $roleQuery->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $roleResult = $roleQuery->execute();
    $role = $roleResult->fetchArray(SQLITE3_ASSOC);

    if (!$role) {
        echo "Error: Role not found.";
        exit;
    }

    $role_name = $role['role_name'];

    // Fetch rides and volunteer slots for the selected role
    $query = "SELECT r.day, r.time, vs.id AS slot_id, vs.max_volunteers,
    COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS filled_slots,
    vs.max_volunteers - COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS remaining_slots FROM rides r
    LEFT JOIN volunteer_slots vs ON r.id = vs.ride_id AND vs.role_id = :role_id
    ORDER BY r.day ASC, r.time ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $rides = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Format the day as "Day of Week, MM/DD/YYYY"
        $date = DateTime::createFromFormat('Y-m-d', $row['day']);
        $row['day'] = $date ? $date->format('l, m/d/Y') : $row['day'];

        // Ensure default values for slots
        $row['max_volunteers'] = $row['max_volunteers'] ?? 0;
        $row['filled_slots'] = $row['filled_slots'] ?? 0;
        $row['remaining_slots'] = $row['max_volunteers'] - $row['filled_slots'];

        $rides[] = $row;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>View Role</title>
    <style>
        .full {
            background-color: lightgray;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Role: <?= htmlspecialchars($role_name) ?></h1>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Max Slots</th>
                    <th>Filled Slots</th>
                    <th>Remaining Slots</th>
                    <th>Volunteers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rides as $ride): ?>
                    <?php
                    $remaining_slots = $ride['remaining_slots'];
                    $is_full = ($remaining_slots <= 0);
                    ?>
                    <tr class="<?= $is_full ? 'full' : '' ?>">
                        <td><?= htmlspecialchars($ride['day']) ?></td>
                        <td><?= htmlspecialchars($ride['time']) ?></td>
                        <td><?= htmlspecialchars($ride['max_volunteers']) ?></td>
                        <td><?= htmlspecialchars($ride['filled_slots']) ?></td>
                        <td>
                            <?= ($remaining_slots <= 0) ? 'FULL' : htmlspecialchars($remaining_slots) ?>
                        </td>
                        <td>
                            <?php if ($ride['filled_slots'] > 0): ?>
                                <a href="view_volunteers.php?slot_id=<?= $ride['slot_id'] ?>" class="btn btn-primary btn-sm w-100">
                                    View Volunteers
                                </a>
                            <?php else: ?>
                                No Volunteers
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#editRoleModal" onclick="editValues(<?= $ride['slot_id'] ?>,<?= $ride['remaining_slots'] ?>,<?= $ride['filled_slots'] ?>,<?= $ride['max_volunteers'] ?>)">
                                Edit Role
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="manage_volunteers.php" class="btn btn-secondary mt-3">Back to Roles</a>
    </div>

    <div id="editRoleModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                </div>
                <div class="modal-body">
                    <div class="row d-flex justify-content-between">
                        <div class="row d-flex justify-content-between">
                            <div class="col-md-4">
                                <label for="filled_slots">Filled Slots</label>
                                </div>
                                <div class="col-md-4" style="text-align: right;">
                                    <label for="remaining_slots">Remaining Slots</label>
                                </div>
                                <div class="col-md-4" style="text-align: right;">
                                    <label for="max_volunteers">Max Volunteers</label>
                                </div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-md-3">
                                    <input type="number" class="form-control" id="filled_slots_value" name="filled_slots" value="">
                                </div>
                                <div class="col-md-1">
                                    <label>+</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" id="remaining_slots" name="remaining_slots" value="">
                                </div>
                                <div class="col-md-1">
                                    <label>=</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" id="max_volunteers" name="max_volunteers" value="">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="current_slot_id" name="current_slot_id" value="">
                        <input type="hidden" id="current_role_id" name="current_role_id" value="">
                        <button class="btn btn-primary mt-4 right" onclick="saveChanges()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function editValues(slot_id, remaining_slots, filled_slots_value, max_volunteers) {
                console.log("role_id: ", <?= $role_id ?>);
                console.log("slot_id: ", slot_id);
                console.log("remaining_slots: ", remaining_slots);
                console.log("filled_slots_value: ", filled_slots_value);
                console.log("max_volunteers: ", max_volunteers);

                document.getElementById("filled_slots_value").value = filled_slots_value;
                document.getElementById("remaining_slots").value = remaining_slots;
                document.getElementById("max_volunteers").value = max_volunteers;
                const current_slot_id = slot_id;
                const current_role_id = <?= $role_id ?>;
                console.log("current_slot_id: ", current_slot_id);
                console.log("current_role_id: ", current_role_id);
                document.getElementById("current_slot_id").value = current_slot_id;
                document.getElementById("current_role_id").value = current_role_id;

            }
            function saveChanges() {
                const current_slot_id = document.getElementById("current_slot_id").value;
                const current_role_id = document.getElementById("current_role_id").value;
                const filled_slots_value = document.getElementById("filled_slots_value").value;
                const remaining_slots = document.getElementById("remaining_slots").value;
                const max_volunteers = document.getElementById("max_volunteers").value;
                if(max_volunteers*1 != remaining_slots*1 + filled_slots_value*1){
                    alert("-------------")
                    return;
                }
                fetch("update_role.php", {
                    method: "POST",
                    body: JSON.stringify({
                        current_slot_id: current_slot_id,
                        current_role_id: current_role_id,
                        filled_slots_value: filled_slots_value,
                        remaining_slots: remaining_slots,
                        max_volunteers: max_volunteers  
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("data: ", data);
                })
            }
        </script>

</html>