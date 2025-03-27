<?php
session_start();

// Enable error reporting for debugging
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

// Fetch the specific record to edit
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Ensure `id` is an integer
$query = "SELECT * FROM volunteer_slots WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$record = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

// Handle missing record
if (!$record) {
    die("Error: Volunteer slot with id = $id not found in the database.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max_volunteers = intval($_POST['max_volunteers']);
    $updateQuery = "UPDATE volunteer_slots SET max_volunteers = :max_volunteers WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindValue(':max_volunteers', $max_volunteers, SQLITE3_INTEGER);
    $updateStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $updateStmt->execute();

    header('Location: manage_volunteers.php?message=updated');
    exit;
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
    <title>Edit Volunteer Slot</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Edit Volunteer Slot</h1>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" value="<?= htmlspecialchars($record['category']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="max_volunteers" class="form-label">Max Volunteers</label>
                <input type="number" class="form-control" id="max_volunteers" name="max_volunteers" value="<?= htmlspecialchars($record['max_volunteers']) ?>" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="manage_volunteers.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
