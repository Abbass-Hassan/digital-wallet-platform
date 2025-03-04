<?php
session_start();
header("Content-Type: application/json");

// Include the DB connection and the models
require_once __DIR__ . '/../../../connection/db.php';
require_once __DIR__ . '/../../../models/UsersModel.php';
require_once __DIR__ . '/../../../models/VerificationsModel.php';

$response = ["status" => "error", "message" => "Something went wrong"];

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
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = $user["role"]; // 0 = User, 1 = Admin

                    // ✅ Store user session data in a cookie for frontend persistence
                    setcookie("user_id", $user["id"], time() + 86400, "/"); // Expires in 1 day
                    setcookie("user_email", $user["email"], time() + 86400, "/");
                    setcookie("user_role", $user["role"], time() + 86400, "/");

                    $response = [
                        "status" => "success",
                        "message" => "Login successful",
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
