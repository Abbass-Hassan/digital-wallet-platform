<?php
// reset_password.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../models/PasswordResetsModel.php';

// Removed session_start() as it's not needed for public password reset endpoints

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
        // Initialize models
        $usersModel = new UsersModel();
        $passwordResetsModel = new PasswordResetsModel();

        // Retrieve token record
        $resetData = $passwordResetsModel->getResetByToken($token);

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

        // Retrieve user details for update
        $userData = $usersModel->getUserById($user_id);
        if (!$userData) {
            echo json_encode(["error" => "User not found"]);
            exit;
        }

        // Update user's password in the users table
        $updated = $usersModel->update(
            $user_id, 
            $userData['email'], 
            $hashed_password, 
            $userData['role']
        );

        if ($updated) {
            // Invalidate the token (delete from password_resets table)
            $passwordResetsModel->delete($resetData['id']);
            echo json_encode(["message" => "Password reset successful"]);
        } else {
            echo json_encode(["error" => "Failed to update password"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
