<?php


require 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $headerId = $_POST['header'] ?? null;
    $caption = trim($_POST['caption']) ?? '';
    $uploadDir = "uploads/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $fileName = basename($_FILES["file"]["name"]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
            $stmt = $db->prepare("INSERT INTO file_uploads (header_id, caption, path) VALUES (:header_id, :caption, :path)");
            $stmt->bindValue(':header_id', $headerId, SQLITE3_INTEGER);
            $stmt->bindValue(':caption', $caption, SQLITE3_TEXT);
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

