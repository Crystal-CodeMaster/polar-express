<?php


require 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $headerId = $_POST['header'] ?? null;
    $uploadDir = "uploads/sponsor/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $fileName = basename($_FILES["file"]["name"]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
            $stmt = $db->prepare("INSERT INTO sponsor_file_uploads (header_id, path) VALUES (:header_id, :path)");
            $stmt->bindValue(':header_id', $headerId, SQLITE3_INTEGER);
            $stmt->bindValue(':path', $targetPath, SQLITE3_TEXT);
            $stmt->execute();

            echo json_encode(["status" => "success", "path" => $targetPath]);
        } else {
            echo json_encode(["status" => "error", "message" => "File upload failed"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    }
}

?>

