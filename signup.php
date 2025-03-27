<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
$db = new SQLite3('admin_users.db');

// Handle form submission for account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = htmlspecialchars($_POST['phone']);

    // Insert the user into the database
    $insertQuery = "INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)";
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $password, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "This email is already registered.";
    }
}

// Fetch visible roles from the database
$rolesQuery = "SELECT id, role_name FROM volunteer_roles WHERE visible = 1";
$rolesResult = $db->query($rolesQuery);
$roles = [];
while ($row = $rolesResult->fetchArray(SQLITE3_ASSOC)) {
    $roles[] = $row;
}

// Close the database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Volunteer Signup</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Volunteer Signup</h1>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">Account created successfully! You can now choose your role below.</div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="mb-5">
            <h3>Create Your Account</h3>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone (Optional)</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <h3>Choose Your Role</h3>
        <div class="list-group">
            <?php foreach ($roles as $role): ?>
                <a href="role_signup.php?role_id=<?= $role['id'] ?>" class="list-group-item list-group-item-action">
                    <?= htmlspecialchars($role['role_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
