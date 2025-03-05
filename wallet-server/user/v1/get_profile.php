<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// REMOVE session_start(); no longer needed for JWT-based auth

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/UserProfilesModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path if needed

// Retrieve the Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["success" => false, "message" => "No authorization header."]);
    exit;
}

// Expecting format: "Authorization: Bearer <token>"
list($bearer, $jwt) = explode(' ', $headers['Authorization']);
if ($bearer !== 'Bearer' || !$jwt) {
    echo json_encode(["success" => false, "message" => "Invalid token format."]);
    exit;
}

// Verify/Decode the JWT
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY"; // Replace with your secure key
$decoded = verify_jwt($jwt, $jwt_secret); // This function should return payload if valid, or false if invalid/expired

if (!$decoded) {
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit;
}

// Extract user ID from the decoded token payload
$user_id = $decoded['id'];

try {
    // Initialize models
    $userProfilesModel = new UserProfilesModel();
    $usersModel = new UsersModel();

    // Fetch user profile
    $userProfile = $userProfilesModel->getProfileByUserId($user_id);
    
    if (!$userProfile) {
        echo json_encode(["success" => false, "message" => "User profile not found."]);
        exit;
    }

    // If you have a 'tier' column in 'users' table, fetch it
    $user = $usersModel->getUserById($user_id);
    $userProfile['tier'] = $user ? $user['tier'] : 'regular';

    echo json_encode(["success" => true, "user" => $userProfile]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
