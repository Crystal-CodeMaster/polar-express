<?php

session_start();

try {
    $db = new SQLite3('admin_users.db');

    // Fetch all roles
    $query = "SELECT id, role_name, role_number FROM volunteer_roles";
    $result = $db->query($query);
    $roles = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $id = $row['id'];
        $role_name = $row['role_name'];
        $role_number = $row['role_number'];

        $roles[] = [
            'id' => $id,
            'role_name' => $role_name,
            'role_number' => $role_number,
        ];
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
    <title>Manage Volunteers</title>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Manage Volunteers</h1>
        <div class="row" style="justify-content:center;">
            <table class="table table-bordered w-50 text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Role</th>
                        <th>Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr class="full">
                            <td id="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?>
                            <td>
                                <input class="w-100" type="number" value="<?= $role['role_number']  ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="row text-center" style="justify-content:center;">
                <button class="btn btn-secondary w-50" onclick="saveRole()">save</button>
            </div>
        </div>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelectorAll("tbody tr");
        rows.forEach(row => {
            const rowData = {
                id: row.querySelector("td:nth-of-type(1)").id,
                number: row.querySelector("input[type='number']").value,
                role: row.querySelector("td:nth-of-type(1)").textContent.trim(),
            }
            console.log(rowData)
        })
        console.log(rows)
    })

    function saveRole() {
        const rows = document.querySelectorAll("tbody tr");
        const updates = [];
        rows.forEach(row => {
            const rowData = {
                id: row.querySelector("td:nth-of-type(1)").id,
                number: row.querySelector("input[type='number']").value,
                role: row.querySelector("td:nth-of-type(1)").textContent.trim(),
            }
            updates.push(rowData);
        })
        
        const requestBody = JSON.stringify({ updates });
        console.log("Request Body:", requestBody);
        fetch('saveRole.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: requestBody,
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
            })
            .catch(error => {
                console.log(error)
            });
        }
</script>

</html>