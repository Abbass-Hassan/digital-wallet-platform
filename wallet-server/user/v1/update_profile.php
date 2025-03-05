<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../connection/db.php";
require_once __DIR__ . "/../../models/UserProfilesModel.php";
require_once __DIR__ . "/../../utils/verify_jwt.php"; // Adjust path if needed

// Remove session_start() and use JWT for authentication.
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["success" => false, "message" => "No authorization header provided."]);
    exit;
}

// Expected header format: "Bearer <token>"
list($bearer, $jwt) = explode(' ', $headers['Authorization']);
if ($bearer !== 'Bearer' || !$jwt) {
    echo json_encode(["success" => false, "message" => "Invalid token format."]);
    exit;
}

// Replace with your secure secret key
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY";
$decoded = verify_jwt($jwt, $jwt_secret);
if (!$decoded) {
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit;
}

$user_id = $decoded['id'];

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON decoding
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid or missing JSON data."]);
    exit;
}

try {
    // Initialize UserProfilesModel
    $userProfilesModel = new UserProfilesModel();

    // Extract update fields from input
    $full_name     = $data["full_name"]     ?? null;
    $date_of_birth = $data["date_of_birth"] ?? null;
    $phone_number  = $data["phone_number"]  ?? null;
    $street_address= $data["street_address"]?? null;
    $city          = $data["city"]          ?? null;
    $country       = $data["country"]       ?? null;

    // Perform update using the extracted user_id from the token
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
