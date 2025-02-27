<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php'; // Database connection
session_start();

$response = ["status" => "error", "message" => "Something went wrong."];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION["user_id"])) {
        $response["message"] = "Unauthorized access.";
        echo json_encode($response);
        exit;
    }

    $user_id = $_SESSION["user_id"];
    
    if (!isset($_FILES["id_document"])) {
        $response["message"] = "No file uploaded.";
        echo json_encode($response);
        exit;
    }

    $file = $_FILES["id_document"];
    $allowed_types = ["image/jpeg", "image/png", "application/pdf"];
    
    if (!in_array($file["type"], $allowed_types)) {
        $response["message"] = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
        echo json_encode($response);
        exit;
    }

    if ($file["size"] > 2 * 1024 * 1024) { // 2MB max
        $response["message"] = "File too large. Max size: 2MB.";
        echo json_encode($response);
        exit;
    }
    
    $upload_dir = __DIR__ . "/../../uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_name = "id_" . $user_id . "_" . time() . "." . pathinfo($file["name"], PATHINFO_EXTENSION);
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($file["tmp_name"], $file_path)) {
        $stmt = $conn->prepare("UPDATE users SET id_document = ?, is_validated = 0 WHERE id = ?");
        if ($stmt->execute([$file_name, $user_id])) {
            $response = ["status" => "success", "message" => "Document uploaded successfully. Pending admin approval."];
        } else {
            $response["message"] = "Database update failed.";
        }
    } else {
        $response["message"] = "File upload failed.";
    }
}

$conn = null;
echo json_encode($response);
?>
