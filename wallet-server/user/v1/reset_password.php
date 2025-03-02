<?php
// reset_password.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = trim($_POST["token"]);
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Basic validation
    if (strlen($new_password) < 6) {
        echo json_encode(["error" => "Password must be at least 6 characters"]);
        exit;
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(["error" => "Passwords do not match"]);
        exit;
    }
    if (empty($token)) {
        echo json_encode(["error" => "Invalid token"]);
        exit;
    }

    try {
        // Retrieve token record
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetData) {
            echo json_encode(["error" => "Invalid or expired token"]);
            exit;
        }

        // Check if token is expired
        if (strtotime($resetData['expires_at']) < time()) {
            echo json_encode(["error" => "Token has expired"]);
            exit;
        }

        // Get user_id from the reset record
        $user_id = $resetData['user_id'];

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update user's password in the users table
        $updateStmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
        $updateStmt->execute([
            'password' => $hashed_password,
            'user_id' => $user_id
        ]);

        // Invalidate the token (delete from password_resets table)
        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
        $deleteStmt->execute(['token' => $token]);

        echo json_encode(["message" => "Password reset successful"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}

$conn = null;
?>
