<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['admin']['logged_in']) || $_SESSION['admin']['logged_in'] !== true) {
    header('Location: login.php'); // Redirect if not logged in
    exit;
}

// Connect to the database
$db = new SQLite3('admin_users.db');

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $deleteQuery = "DELETE FROM volunteer_slots WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    header('Location: manage_volunteers.php?message=deleted');
    exit;
}

// Fetch the record for confirmation
$id = intval($_GET['id']);
$query = "SELECT * FROM volunteer_slots WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$record = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Close the database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Delete Volunteer Slot</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4 text-danger">Confirm Deletion</h1>
        <p>Are you sure you want to delete the following record?</p>
        <ul>
            <li><strong>Category:</strong> <?= htmlspecialchars($record['category']) ?></li>
            <li><strong>Max Volunteers:</strong> <?= htmlspecialchars($record['max_volunteers']) ?></li>
        </ul>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
            <a href="manage_volunteers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
