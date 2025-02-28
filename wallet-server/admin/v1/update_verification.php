<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    $user_id = $data["user_id"] ?? null;
    $is_validated = $data["is_validated"] ?? null;

    if (!$user_id || !in_array($is_validated, [1, -1])) {
        $response["message"] = "Invalid request parameters.";
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE verifications SET is_validated = :is_validated WHERE user_id = :user_id");
        $stmt->bindParam(":is_validated", $is_validated, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response["status"] = "success";
            $response["message"] = ($is_validated == 1) ? "User verified successfully!" : "Verification request rejected.";
        } else {
            $response["message"] = "Database update failed.";
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
