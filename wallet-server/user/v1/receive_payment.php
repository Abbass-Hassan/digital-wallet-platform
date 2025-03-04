<?php
// receive_payment.php

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/TransactionsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../utils/MailService.php';

header('Content-Type: application/json');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$loggedInUserId = $_SESSION['user_id'];

// Check if the recipient_id is present in the URL
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

    // Extract amount from the query, default to 10 if not provided
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

    // Record the transaction (sender_id is NULL since the funds come via QR code)
    $transactionsModel->create(null, $loggedInUserId, 'qr_payment', $amount);

    // Fetch user's email for confirmation
    $user = $usersModel->getUserById($loggedInUserId);
    $userEmail = $user ? $user['email'] : null;

    // Send an email notification if an email exists
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
