<?php
// request_password_reset.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
session_start();

// Get email from POST data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["error" => "Invalid email format"]);
        exit;
    }

    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Always return the same message for security,
        // but proceed only if the user exists.
        $responseMessage = "If an account with that email exists, a password reset link has been sent.";

        if ($user) {
            $user_id = $user['id'];
            // Generate a secure token (32 characters hex string)
            $token = bin2hex(random_bytes(16));
            // Set expiration to 1 hour from now
            $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Insert token into password_resets table
            $insertStmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
            $insertStmt->execute([
                'user_id' => $user_id,
                'token' => $token,
                'expires_at' => $expires_at
            ]);

            // Build the reset link (update the URL to match your local environment)
$resetLink = "http://localhost/digital-wallet-platform/wallet-client/reset_password.html?token=" . urlencode($token);


            // Send email using MailService
            require_once __DIR__ . '/../../utils/MailService.php';
            $mailer = new MailService();
            $subject = "Password Reset Request";
            $body = "
                <h1>Password Reset</h1>
                <p>We received a request to reset your password.</p>
                <p>Please click the link below to reset your password (this link will expire in 1 hour):</p>
                <p><a href='{$resetLink}'>Reset Password</a></p>
                <p>If you did not request a password reset, please ignore this email.</p>
            ";
            $mailer->sendMail($email, $subject, $body);
        }
        
        echo json_encode(["message" => $responseMessage]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}

$conn = null;
?>
