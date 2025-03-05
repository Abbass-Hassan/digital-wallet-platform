<?php
header("Content-Type: application/json");

// Remove session_start(); not needed with JWT

require_once __DIR__ . '/../../../connection/db.php';
require_once __DIR__ . '/../../../models/UsersModel.php';

/**
 * Minimal JWT generation function.
 * For production use, consider using a library like firebase/php-jwt.
 */
function generate_jwt(array $payload, string $secret, int $expiry_in_seconds = 3600): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $issuedAt = time();
    $expire   = $issuedAt + $expiry_in_seconds;

    // Merge standard claims into the payload
    $payload = array_merge($payload, [
        'iat' => $issuedAt,
        'exp' => $expire
    ]);

    // Encode header and payload to Base64Url
    $base64Header  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    // Create signature
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

// Replace this with your secure secret key (preferably via environment variable)
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY";

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        // Initialize UsersModel
        $usersModel = new UsersModel();

        // Fetch user by email
        $user = null;
        $allUsers = $usersModel->getAllUsers();
        foreach ($allUsers as $u) {
            if ($u['email'] === $email) {
                $user = $u;
                break;
            }
        }

        // Check if user exists and is an admin (role == 1)
        if ($user && $user['role'] == 1) {
            if (password_verify($password, $user['password'])) {
                // Generate JWT token for admin login
                $payload = [
                    "id"    => $user["id"],
                    "email" => $user["email"],
                    "role"  => $user["role"]
                ];
                $token = generate_jwt($payload, $jwt_secret, 3600); // token valid for 1 hour

                $response = ["status" => "success", "message" => "Login successful", "token" => $token];
            } else {
                $response["message"] = "Invalid email or password";
            }
        } else {
            $response["message"] = "Access denied. Only admins can log in.";
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
