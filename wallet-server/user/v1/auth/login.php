<?php
session_start();
header("Content-Type: application/json"); // Set JSON response header
require_once __DIR__ . '/../../../connection/db.php'; // Include database connection

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        // 🔍 Check if the email exists
        $stmt = $conn->prepare("SELECT id, email, password, role, is_validated FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 🔐 Verify password
            if (password_verify($password, $user['password'])) {
                if ($user['role'] == 1 && $user['is_validated'] == 0) {
                    // 🚨 Admin needs validation
                    $response["message"] = "Admin account is not validated. Please contact support.";
                } else {
                    // ✅ Start Session and Store User Data
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = $user["role"]; // 0 = User, 1 = Admin

                    $response = [
                        "status" => "success",
                        "message" => "Login successful",
                        "user" => [
                            "id" => $user["id"],
                            "email" => $user["email"],
                            "role" => $user["role"]
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
?>
