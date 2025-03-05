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

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../utils/MailService.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path if needed

$response = ["status" => "error", "message" => "Something went wrong"];

// First, verify that the request is a POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve the JWT from the Authorization header
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        $response["message"] = "No authorization header provided.";
        echo json_encode($response);
        exit;
    }
    
    // Expected header format: "Bearer <token>"
    list($bearer, $jwt) = explode(' ', $headers['Authorization']);
    if ($bearer !== 'Bearer' || !$jwt) {
        $response["message"] = "Invalid token format.";
        echo json_encode($response);
        exit;
    }
    
    // Verify the JWT
    $jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY"; // Replace with your secure secret
    $decoded = verify_jwt($jwt, $jwt_secret);
    if (!$decoded) {
        $response["message"] = "Invalid or expired token.";
        echo json_encode($response);
        exit;
    }
    
    // Check if the token belongs to an admin (role must be 1)
    if (!isset($decoded['role']) || $decoded['role'] != 1) {
        $response["message"] = "Access denied. Admins only.";
        echo json_encode($response);
        exit;
    }
    
    // Read JSON input from the request body
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    
    $user_id = $data["user_id"] ?? null;
    $is_validated = $data["is_validated"] ?? null;
    
    // Validate request parameters
    if (!$user_id || !in_array($is_validated, [1, -1])) {
        $response["message"] = "Invalid request parameters.";
        echo json_encode($response);
        exit;
    }
    
    try {
        // Initialize models
        $verificationsModel = new VerificationsModel();
        $usersModel = new UsersModel();
    
        // Fetch the verification record for the given user
        $verification = $verificationsModel->getVerificationByUserId($user_id);
        if (!$verification) {
            echo json_encode(["status" => "error", "message" => "Verification record not found."]);
            exit;
        }
    
        // Update verification status
        $updated = $verificationsModel->update(
            $verification['id'],
            $verification['user_id'],
            $verification['id_document'],
            $is_validated,
            ($is_validated == 1) ? "User verified" : "Verification rejected"
        );
    
        if ($updated) {
            $response["status"] = "success";
            $response["message"] = ($is_validated == 1)
                ? "User verified successfully!"
                : "Verification request rejected.";
    
            // If approved, send a welcome email with the QR link
            if ($is_validated == 1) {
                // Fetch the user's email
                $user = $usersModel->getUserById($user_id);
                $userEmail = $user ? $user['email'] : null;
    
                if ($userEmail) {
                    // Link to generate_qr.php, which embeds a link to receive_payment.php
                    $qrLink = "http://localhost/digital-wallet-platform/wallet-server/utils/generate_qr.php?recipient_id={$user_id}&amount=10";
    
                    $mailer = new MailService();
                    $subject = "Welcome to Our Platform!";
                    $body = "
                        <h1>Congratulations!</h1>
                        <p>Your account has been verified successfully.</p>
                        <p>You can now receive a special bonus by scanning or opening the following link:</p>
                        <p><a href='{$qrLink}' target='_blank'>Click here to view your QR code</a></p>
                        <p>Welcome aboard!</p>
                    ";
    
                    $mailer->sendMail($userEmail, $subject, $body);
                }
            }
        } else {
            $response["message"] = "Database update failed.";
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
