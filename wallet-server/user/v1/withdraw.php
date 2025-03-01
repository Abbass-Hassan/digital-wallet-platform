<?php
// withdraw.php
require_once __DIR__ . '/../../connection/db.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Check verification status
    $verifyStmt = $conn->prepare("SELECT is_validated FROM verifications WHERE user_id = :user_id LIMIT 1");
    $verifyStmt->execute(['user_id' => $userId]);
    $verification = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification || $verification['is_validated'] != 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot withdraw.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$amount = floatval($data['amount']);

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid withdrawal amount']);
    exit;
}

try {
    // Retrieve the user's wallet
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        echo json_encode(['error' => 'Wallet not found']);
        exit;
    }

    if ($wallet['balance'] < $amount) {
        echo json_encode(['error' => 'Insufficient funds']);
        exit;
    }

    $newBalance = $wallet['balance'] - $amount;
    $stmt = $conn->prepare("UPDATE wallets SET balance = :balance WHERE user_id = :user_id");
    $stmt->execute(['balance' => $newBalance, 'user_id' => $userId]);

    // Insert a record into transactions table
    $transStmt = $conn->prepare("
        INSERT INTO transactions (sender_id, recipient_id, amount, transaction_type)
        VALUES (:user_id, NULL, :amount, 'withdrawal')
    ");
    $transStmt->execute(['user_id' => $userId, 'amount' => $amount]);

    echo json_encode(['newBalance' => $newBalance, 'message' => 'Withdrawal successful']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>
