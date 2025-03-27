<?php
session_start();
$db_file = 'admin_users.db'; // Path to SQLite database

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verify login credentials
function verify_login($username, $password) {
    global $db_file;
    $db = new SQLite3($db_file);
    $stmt = $db->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    if ($row && password_verify($password, $row['password'])) {
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (verify_login($username, $password)) {
        // Set session for logged-in user
        $phote['admin'] = [
            'username' => $username,
            'role' => 'admin', // Default role is admin; can expand to other roles
            'logged_in' => true
        ];
        header('Location: dashboard.php');
        exit; // Ensure no further code is executed
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Login</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center">Admin Login</h1>
        <div class="card p-4 shadow-sm mx-auto" style="max-width: 400px;">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error;?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <p class="text-center mt-3"><a href="create_account.php">Create an Account</a></p>
        </div>
    </div>
</body>
</html>
