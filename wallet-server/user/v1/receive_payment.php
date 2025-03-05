<?php
// receive_payment.php

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/TransactionsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../utils/MailService.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path as needed

header('Content-Type: application/json');
// Removed session_start()

// Get the Authorization header and verify JWT
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'No authorization header provided']);
    exit;
}

// Expected header format: "Bearer <token>"
list($bearer, $jwt) = explode(' ', $headers['Authorization']);
if ($bearer !== 'Bearer' || !$jwt) {
    echo json_encode(['error' => 'Invalid token format']);
    exit;
}

$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY"; // Replace with your secure key
$decoded = verify_jwt($jwt, $jwt_secret);
if (!$decoded) {
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Extract the logged-in user ID from the JWT payload
$loggedInUserId = $decoded['id'];

// Check if the recipient_id is present in the URL (if required)
if (!isset($_GET['recipient_id'])) {
    echo json_encode(['error' => 'No recipient specified']);
    exit;
}

$recipientId = $_GET['recipient_id'];

try {
    // Initialize models
    $walletsModel = new WalletsModel();
    $verificationsModel = new VerificationsModel();
    $transactionsModel = new TransactionsModel();
    $usersModel = new UsersModel();

    // Check if user is verified
    $verification = $verificationsModel->getVerificationByUserId($loggedInUserId);
    if (!$verification || (int)$verification['is_validated'] !== 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot receive payment.']);
        exit;
    }

    // Set the default amount to add (e.g., 10 USDT)
    $amount = 10.0;

    // Check if the wallet exists for the user
    $wallet = $walletsModel->getWalletByUserId($loggedInUserId);

    if (!$wallet) {
        // Create a wallet record if it doesn't exist
        $walletsModel->create($loggedInUserId, $amount);
        $newBalance = $amount;
    } else {
        // Update the existing wallet balance
        $newBalance = floatval($wallet['balance']) + $amount;
        $walletsModel->update($wallet['id'], $loggedInUserId, $newBalance);
    }

    // Record the transaction (sender_id is NULL since funds come via QR code)
    $transactionsModel->create(null, $loggedInUserId, 'qr_payment', $amount);

    // Fetch user's email for confirmation
    $user = $usersModel->getUserById($loggedInUserId);
    $userEmail = $user ? $user['email'] : null;

    // Send email notification if an email exists
    if ($userEmail) {
        $mailer = new MailService();
        $subject = "Payment Received";
        $body = "
            <h1>Payment Received</h1>
            <p>You have received <strong>{$amount} USDT</strong> into your wallet via QR code.</p>
            <p>Your new balance is: <strong>{$newBalance} USDT</strong></p>
        ";
        $mailer->sendMail($userEmail, $subject, $body);
    }

    echo json_encode([
        'status'      => 'success',
        'message'     => "Payment of {$amount} credits has been added to your wallet.",
        'user_id'     => $loggedInUserId,
        'new_balance' => $newBalance
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
