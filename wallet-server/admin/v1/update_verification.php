<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../utils/MailService.php'; // Make sure this path is correct

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
        // Update verification status
        $stmt = $conn->prepare("UPDATE verifications SET is_validated = :is_validated WHERE user_id = :user_id");
        $stmt->bindParam(":is_validated", $is_validated, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response["status"] = "success";
            $response["message"] = ($is_validated == 1)
                ? "User verified successfully!"
                : "Verification request rejected.";

            // If approved, send a welcome email with the QR link
            if ($is_validated == 1) {
                // Fetch the user's email
                $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = :user_id LIMIT 1");
                $emailStmt->execute(['user_id' => $user_id]);
                $userData = $emailStmt->fetch(PDO::FETCH_ASSOC);

                if ($userData && !empty($userData['email'])) {
                    $userEmail = $userData['email'];

                    // Link to generate_qr.php, which embeds a link to receive_payment.php
                    // Adjust domain/paths as needed for your environment
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
