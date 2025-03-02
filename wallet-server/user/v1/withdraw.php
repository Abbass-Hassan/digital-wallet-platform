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

// Check verification status
try {
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

// Fetch user's tier
try {
    $userStmt = $conn->prepare("SELECT tier FROM users WHERE id = :user_id LIMIT 1");
    $userStmt->execute(['user_id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $tier = $user ? $user['tier'] : 'regular';
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Fetch limits for this tier
try {
    $limitStmt = $conn->prepare("SELECT daily_limit, weekly_limit, monthly_limit FROM transaction_limits WHERE tier = :tier LIMIT 1");
    $limitStmt->execute(['tier' => $tier]);
    $limits = $limitStmt->fetch(PDO::FETCH_ASSOC);
    if (!$limits) {
        echo json_encode(['error' => 'Transaction limits not defined for your tier']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Calculate current totals for withdrawals & transfers (outgoing) where the user is sender
try {
    // Daily total
    $dailyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND DATE(created_at) = CURDATE() AND transaction_type IN ('withdrawal','transfer')");
    $dailyStmt->execute(['user_id' => $userId]);
    $dailyTotal = floatval($dailyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Weekly total
    $weeklyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND transaction_type IN ('withdrawal','transfer')");
    $weeklyStmt->execute(['user_id' => $userId]);
    $weeklyTotal = floatval($weeklyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Monthly total
    $monthlyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND transaction_type IN ('withdrawal','transfer')");
    $monthlyStmt->execute(['user_id' => $userId]);
    $monthlyTotal = floatval($monthlyStmt->fetch(PDO::FETCH_ASSOC)['total']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Check if new withdrawal would exceed limits
if (($dailyTotal + $amount) > floatval($limits['daily_limit'])) {
    echo json_encode(['error' => 'Daily withdrawal limit exceeded']);
    exit;
}
if (($weeklyTotal + $amount) > floatval($limits['weekly_limit'])) {
    echo json_encode(['error' => 'Weekly withdrawal limit exceeded']);
    exit;
}
if (($monthlyTotal + $amount) > floatval($limits['monthly_limit'])) {
    echo json_encode(['error' => 'Monthly withdrawal limit exceeded']);
    exit;
}

// Check wallet balance and update
try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$wallet) {
        echo json_encode(['error' => 'Wallet not found']);
        exit;
    }
    if (floatval($wallet['balance']) < $amount) {
        echo json_encode(['error' => 'Insufficient funds']);
        exit;
    }

    $newBalance = floatval($wallet['balance']) - $amount;
    $stmt = $conn->prepare("UPDATE wallets SET balance = :balance WHERE user_id = :user_id");
    $stmt->execute(['balance' => $newBalance, 'user_id' => $userId]);

    // Insert transaction record for withdrawal (recipient_id is NULL)
    $transStmt = $conn->prepare("INSERT INTO transactions (sender_id, recipient_id, amount, transaction_type) VALUES (:user_id, NULL, :amount, 'withdrawal')");
    $transStmt->execute(['user_id' => $userId, 'amount' => $amount]);

    // Fetch user's email for confirmation
    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = :user_id LIMIT 1");
    $emailStmt->execute(['user_id' => $userId]);
    $userData = $emailStmt->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userData ? $userData['email'] : null;

    // Send email confirmation if email is available
    if ($userEmail) {
        require_once __DIR__ . '/../../utils/MailService.php';
        $mailer = new MailService();
        $subject = "Withdrawal Confirmation";
        $body = "
            <h1>Withdrawal Successful</h1>
            <p>You have withdrawn <strong>{$amount} USDT</strong> from your wallet.</p>
            <p>Your new balance is: <strong>{$newBalance} USDT</strong></p>
        ";
        $mailer->sendMail($userEmail, $subject, $body);
    }

    echo json_encode(['newBalance' => $newBalance, 'message' => 'Withdrawal successful']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>
