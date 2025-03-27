<?php
header('Content-Type: application/json');

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    // Validate required fields
    $required_fields = ['signup_id', 'shift_id', 'role_id', 'group_size'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Connect to database
    require_once 'db_connection.php';
    
    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update the signup record
        $update_query = "UPDATE signups 
                        SET shift_id = ?, 
                            role_id = ?, 
                            volunteer_size = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE signup_id = ?";
        
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([
            $data['shift_id'],
            $data['role_id'],
            $data['group_size'],
            $data['signup_id']
        ]);

        // Check if any rows were affected
        if ($stmt->rowCount() === 0) {
            // If no existing record, insert new one
            $insert_query = "INSERT INTO signups 
                           (signup_id, shift_id, role_id, volunteer_size, created_at, updated_at)
                           VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            
            $stmt = $pdo->prepare($insert_query);
            $stmt->execute([
                $data['signup_id'],
                $data['shift_id'],
                $data['role_id'],
                $data['group_size']
            ]);
        }

        // Get the current volunteer size for validation
        $size_query = "SELECT volunteer_size 
                      FROM signups 
                      WHERE signup_id = ?";
        $stmt = $pdo->prepare($size_query);
        $stmt->execute([$data['signup_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'volunteer_size' => $result['volunteer_size']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 