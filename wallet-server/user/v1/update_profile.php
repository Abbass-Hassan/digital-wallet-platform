<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . "/../../connection/db.php";
require_once __DIR__ . "/../../models/UserProfilesModel.php";

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON decoding
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid or missing JSON data."]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Initialize UserProfilesModel
    $userProfilesModel = new UserProfilesModel();

    // Ensure all keys exist in the array
    $full_name = $data["full_name"] ?? null;
    $date_of_birth = $data["date_of_birth"] ?? null;
    $phone_number = $data["phone_number"] ?? null;
    $street_address = $data["street_address"] ?? null;
    $city = $data["city"] ?? null;
    $country = $data["country"] ?? null;

    // Perform update
    $updated = $userProfilesModel->update(
        $user_id,
        $full_name,
        $date_of_birth,
        $phone_number,
        $street_address,
        $city,
        $country
    );

    if ($updated) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating profile. No changes made."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
