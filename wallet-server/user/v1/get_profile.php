<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/UserProfilesModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';

$user_id = $_SESSION['user_id'];

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

    // Fetch user tier from users table
    $user = $usersModel->getUserById($user_id);
    $userProfile['tier'] = $user ? $user['tier'] : 'regular';

    echo json_encode(["success" => true, "user" => $userProfile]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
