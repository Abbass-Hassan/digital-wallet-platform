<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';

$response = ["status" => "error", "message" => "Something went wrong"];

try {
    $stmt = $conn->prepare("SELECT v.user_id, u.email, v.id_document 
                            FROM verifications v 
                            JOIN users u ON v.user_id = u.id 
                            WHERE v.is_validated = 0");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($requests) {
        $response = ["status" => "success", "data" => $requests];
    } else {
        $response = ["status" => "success", "data" => []];
    }
} catch (PDOException $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>
