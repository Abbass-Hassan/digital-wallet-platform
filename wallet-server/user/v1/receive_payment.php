<?php
// receive_payment.php

require_once __DIR__ . '/../../connection/db.php';
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

// Optional: Enforce that the logged-in user must match the recipient in the QR code
if ($loggedInUserId != $recipientId) {
    echo json_encode(['error' => 'You are not the intended recipient for this payment']);
    exit;
}

// Check if user is verified
try {
    $verifyStmt = $conn->prepare("SELECT is_validated FROM verifications WHERE user_id = :user_id LIMIT 1");
    $verifyStmt->execute(['user_id' => $loggedInUserId]);
    $verification = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification || (int)$verification['is_validated'] !== 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot receive payment.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// The amount to be credited when the user scans the QR code
$amount = 10.0;

try {
    // Check if the wallet exists for the user
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $loggedInUserId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // Create a wallet record if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (:user_id, :balance)");
        $stmt->execute(['user_id' => $loggedInUserId, 'balance' => $amount]);
        $newBalance = $amount;
    } else {
        // Update the existing wallet balance
        $currentBalance = (float)$wallet['balance'];
        $newBalance = $currentBalance + $amount;
        $stmt = $conn->prepare("UPDATE wallets SET balance = :balance WHERE user_id = :user_id");
        $stmt->execute(['balance' => $newBalance, 'user_id' => $loggedInUserId]);
    }

    // Record the transaction (sender_id is NULL or 0 since the funds come via QR code)
    $transStmt = $conn->prepare("
        INSERT INTO transactions (sender_id, recipient_id, amount, transaction_type) 
        VALUES (NULL, :recipient_id, :amount, 'qr_payment')
    ");
    $transStmt->execute([
        'recipient_id' => $loggedInUserId,
        'amount'       => $amount
    ]);

    // Fetch user's email for confirmation
    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = :user_id LIMIT 1");
    $emailStmt->execute(['user_id' => $loggedInUserId]);
    $userData = $emailStmt->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userData ? $userData['email'] : null;

    // Send an email notification if an email exists
    if ($userEmail) {
        require_once __DIR__ . '/../../utils/MailService.php';
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

$conn = null;
