<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/../../../connection/db.php';

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        $stmt = $conn->prepare("
            SELECT 
                users.id, users.email, users.password, users.role, 
                COALESCE(verifications.is_validated, 0) AS is_validated
            FROM users
            LEFT JOIN verifications ON users.id = verifications.user_id
            WHERE users.email = :email
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['role'] == 1 && $user['is_validated'] == 0) {
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
                            "is_validated" => $user["is_validated"]
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
