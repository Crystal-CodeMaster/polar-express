<?php
try {
    $db = new SQLite3('admin_users.db'); // Adjust the database filename if necessary
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}
