<?php
session_start();

// Check if the volunteer is logged in
if (!isset($_SESSION['volunteer_logged_in']) || $_SESSION['volunteer_logged_in'] !== true) {
    header('Location: volunteer_login.php'); // Redirect to login if not logged in
    exit;
}

try {
    $db = new SQLite3('admin_users.db');
    $query = "SELECT id, role_name FROM volunteer_roles ORDER BY role_name ASC";
    $roles = $db->query($query);

    $volunteer_id = $_SESSION['volunteer_id'];
    $query = "SELECT * FROM volunteer_signups WHERE volunteer_id = $volunteer_id";
    $result = $db->query($query);
    $volunteer_signup = $result->fetchArray(SQLITE3_ASSOC);

    if ($volunteer_signup) {
        $edit_btn = 1;
    } else {
        $edit_btn = 0;
    }

} catch (Exception $e) {
    echo "Error fetching roles: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./master.css">
    <title>Role Selection</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        .custom-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            /* Blue gradient */
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            transition: 0.3s;
        }

        .custom-btn:hover {
            background: linear-gradient(45deg, #0056b3, #003f7f);
            /* Darker on hover */
        }

        .form-controller {
            display: flex;
            justify-content: center;
            gap: 15px;
            /* Space between buttons */
        }
    </style>
</head>

<body>
    <div id="login-window">
        <div id="login-box" class="glassy-dark-bg rounded-custom" style="background-color:#01368C;">
            <h2 class="text-center mt-5 mb-3 logo-font">Select a Role</h1>
                <div class="list-group mt-5">
                    <?php if ($edit_btn) { ?>
                        <a href="edit_role_selections.php" class="list-group-item list-group-item-action pb-2 pt-2 text-pale-light full-opacity-bg rounded-custom pb-2 mt-3">Edit My Selection</a><hr/>
                    <?php } ?>  
                    <?php
                    // Map role IDs to the specific pages
                    $role_pages = [
                        1 => 'signup_jolly_people.php',
                        2 => 'signup_elves.php',
                        3 => 'signup_chefs.php',
                        4 => 'signup_conductors.php',
                    ];

                    // Display role links dynamically
                    while ($role = $roles->fetchArray(SQLITE3_ASSOC)) {
                        $role_name = htmlspecialchars($role['role_name']);
                        $role_id = $role['id'];

                        // Ensure there is a corresponding page for the role
                        $page = $role_pages[$role_id] ?? '#';

                        // Include session ID to pass login info
                        echo "<a href=\"$page\" class=\"list-group-item list-group-item-action pb-2 pt-2 text-pale-light full-opacity-bg rounded-custom pb-2 mt-3\" >
                        Sign Up for $role_name
                      </a><hr class=\"mt-1 d-none d-md-block\" style=\"opacity: 0;\"/>";
                    }
                    ?>
                </div>
                <div class="row form-group mt-4">
   
                </div>
        </div>
    </div>
</body>

</html>