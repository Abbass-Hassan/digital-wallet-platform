<?php
header("Content-Type: application/json");

// Include the DB connection and the models
require_once __DIR__ . '/../../../connection/db.php';
require_once __DIR__ . '/../../../models/UsersModel.php';
require_once __DIR__ . '/../../../models/VerificationsModel.php';

/**
 * Generate a JWT manually (simple version).
 * 
 * For production, consider using firebase/php-jwt library:
 *   composer require firebase/php-jwt
 */
function generate_jwt(array $payload, string $secret, int $expiry_in_seconds = 3600): string
{
    // Add standard claims
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $issuedAt = time();
    $expire = $issuedAt + $expiry_in_seconds;

    $payload = array_merge($payload, [
        'iat' => $issuedAt,
        'exp' => $expire
    ]);

    // Encode Header
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Encode Payload
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);

    // Encode Signature to Base64Url
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Create JWT
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

$response = ["status" => "error", "message" => "Something went wrong"];

// Replace this with a more secure way of storing your secret
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        // Initialize the models
        $usersModel = new UsersModel();
        $verificationsModel = new VerificationsModel();

        // Fetch user by email
        $allUsers = $usersModel->getAllUsers();
        $user = null;

        foreach ($allUsers as $u) {
            if ($u['email'] === $email) {
                $user = $u;
                break;
            }
        }

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Fetch verification status
                $verification = $verificationsModel->getVerificationByUserId($user['id']);
                $is_validated = $verification ? $verification['is_validated'] : 0;

                if ($user['role'] == 1 && $is_validated == 0) {
                    $response["message"] = "Admin account is not validated. Please contact support.";
                } else {
                    // Generate JWT with user data
                    $payload = [
                        "id" => $user["id"],
                        "email" => $user["email"],
                        "role" => $user["role"],
                        "is_validated" => $is_validated
                    ];
                    // You can choose how long the token is valid. 3600 = 1 hour
                    $jwt = generate_jwt($payload, $jwt_secret, 3600);

                    $response = [
                        "status" => "success",
                        "message" => "Login successful",
                        "token" => $jwt, // The client should store this token
                        "user" => [
                            "id" => $user["id"],
                            "email" => $user["email"],
                            "role" => $user["role"],
                            "is_validated" => $is_validated
                        ]
                    ];
                }
            } else {
                $response["message"] = "Invalid email or password";
            }
        } else {
            $response["message"] = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
