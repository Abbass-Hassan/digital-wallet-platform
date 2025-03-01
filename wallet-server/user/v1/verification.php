<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
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

    if ($file["size"] > 2 * 1024 * 1024) {
        $response["message"] = "File too large. Max size: 2MB.";
        echo json_encode($response);
        exit;
    }

    $upload_dir = __DIR__ . "/../../uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_name = "id_" . $user_id . "_" . time() . "." . pathinfo($file["name"], PATHINFO_EXTENSION);
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($file["tmp_name"], $file_path)) {
        // Check if a verification record already exists for the user
        $checkStmt = $conn->prepare("SELECT id FROM verifications WHERE user_id = ?");
        $checkStmt->execute([$user_id]);
        $existingVerification = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingVerification) {
            // Update the existing verification record
            $stmt = $conn->prepare("UPDATE verifications SET id_document = ?, is_validated = 0, verification_note = 'Verification resubmitted' WHERE user_id = ?");
            if ($stmt->execute([$file_name, $user_id])) {
                $response = ["status" => "success", "message" => "Document updated successfully. Pending admin approval."];
            } else {
                $response["message"] = "Database update failed.";
            }
        } else {
            // (Fallback) Insert a new verification record if none exists
            $stmt = $conn->prepare("INSERT INTO verifications (user_id, id_document, is_validated) VALUES (?, ?, 0)");
            if ($stmt->execute([$user_id, $file_name])) {
                $response = ["status" => "success", "message" => "Document uploaded successfully. Pending admin approval."];
            } else {
                $response["message"] = "Database update failed.";
            }
        }
    } else {
        $response["message"] = "File upload failed.";
    }
}

$conn = null;
echo json_encode($response);
?>
