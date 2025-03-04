<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';

$response = ["status" => "error", "message" => "Something went wrong"];

try {
    // Initialize models
    $verificationsModel = new VerificationsModel();
    $usersModel = new UsersModel();

    // Fetch all verification requests where is_validated = 0
    $verificationRequests = $verificationsModel->getPendingVerifications();

    $requests = [];

    // Append user email to each verification request
    foreach ($verificationRequests as $request) {
        $user = $usersModel->getUserById($request['user_id']);
        if ($user) {
            $request['email'] = $user['email'];
            $requests[] = $request;
        }
    }

    $response = ["status" => "success", "data" => $requests];
} catch (PDOException $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>
