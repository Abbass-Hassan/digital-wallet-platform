<?php
// deposit.php
require_once __DIR__ . '/../../connection/db.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Check verification status
try {
    $verifyStmt = $conn->prepare("SELECT is_validated FROM verifications WHERE user_id = :user_id LIMIT 1");
    $verifyStmt->execute(['user_id' => $userId]);
    $verification = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    if (!$verification || $verification['is_validated'] != 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot deposit.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Get deposit amount from input
$data = json_decode(file_get_contents("php://input"), true);
$amount = floatval($data['amount']);

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid deposit amount']);
    exit;
}

try {
    // Check if the wallet exists for the user
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // Create wallet record if not exists
        $stmt = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (:user_id, :balance)");
        $stmt->execute(['user_id' => $userId, 'balance' => $amount]);
        $newBalance = $amount;
    } else {
        // Update wallet balance by adding deposit amount
        $newBalance = floatval($wallet['balance']) + $amount;
        $stmt = $conn->prepare("UPDATE wallets SET balance = :balance WHERE user_id = :user_id");
        $stmt->execute(['balance' => $newBalance, 'user_id' => $userId]);
    }

    // Insert transaction record for deposit (sender_id is NULL because funds come externally)
    $transStmt = $conn->prepare("INSERT INTO transactions (sender_id, recipient_id, amount, transaction_type) VALUES (NULL, :user_id, :amount, 'deposit')");
    $transStmt->execute(['user_id' => $userId, 'amount' => $amount]);

    echo json_encode(['newBalance' => $newBalance, 'message' => 'Deposit successful']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>
