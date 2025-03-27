<?php
// ... existing database connection code ...

$signup_id = $_GET['signup_id'];
$role_id = $_GET['role_id'];
$current_date = date('Y-m-d');

// Get existing volunteer size for this signup
$volunteer_query = "SELECT shift_id, volunteer_size 
                   FROM signups 
                   WHERE signup_id = ? AND role_id = ?";
$stmt = $pdo->prepare($volunteer_query);
$stmt->execute([$signup_id, $role_id]);
$existing_signup = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all available shifts
$shift_query = "SELECT s.shift_id, s.shift_name, COALESCE(sg.volunteer_size, 0) as volunteer_size
                FROM shifts s
                LEFT JOIN signups sg ON s.shift_id = sg.shift_id 
                    AND sg.signup_id = ?
                WHERE s.date = ?
                ORDER BY s.shift_name";
$stmt = $pdo->prepare($shift_query);
$stmt->execute([$signup_id, $current_date]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response
$response = [
    'current_shift_id' => $existing_signup ? $existing_signup['shift_id'] : null,
    'shifts' => $shifts
];

header('Content-Type: application/json');
echo json_encode($response);
?> 