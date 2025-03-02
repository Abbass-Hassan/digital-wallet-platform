<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
session_start();

// Ensure the sender is logged in.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit;
}

$sender_id = $_SESSION['user_id'];

// Read the JSON input.
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['recipient_email']) || !isset($data['amount'])) {
    echo json_encode(["error" => "Invalid input."]);
    exit;
}

$recipient_email = trim($data['recipient_email']);
$amount = floatval($data['amount']);

if ($amount <= 0) {
    echo json_encode(["error" => "Invalid transfer amount."]);
    exit;
}

// Check if the recipient exists (search only by email).
$stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->bindParam(":email", $recipient_email);
$stmt->execute();
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipient) {
    echo json_encode(["error" => "Recipient not found."]);
    exit;
}

$recipient_id = $recipient['id'];

// Prevent transferring to self.
if ($recipient_id == $sender_id) {
    echo json_encode(["error" => "You cannot transfer funds to yourself."]);
    exit;
}

// Check the sender's wallet balance.
$stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id LIMIT 1");
$stmt->bindParam(":user_id", $sender_id);
$stmt->execute();
$sender_wallet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sender_wallet || floatval($sender_wallet['balance']) < $amount) {
    echo json_encode(["error" => "Insufficient funds."]);
    exit;
}

// Fetch sender's tier
try {
    $userStmt = $conn->prepare("SELECT tier FROM users WHERE id = :user_id LIMIT 1");
    $userStmt->execute(['user_id' => $sender_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $tier = $user ? $user['tier'] : 'regular';
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Fetch limits for this tier
try {
    $limitStmt = $conn->prepare("SELECT daily_limit, weekly_limit, monthly_limit FROM transaction_limits WHERE tier = :tier LIMIT 1");
    $limitStmt->execute(['tier' => $tier]);
    $limits = $limitStmt->fetch(PDO::FETCH_ASSOC);
    if (!$limits) {
        echo json_encode(["error" => "Transaction limits not defined for your tier"]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Calculate current totals for withdrawals & transfers (outgoing) where the user is sender
try {
    // Daily total
    $dailyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND DATE(created_at) = CURDATE() AND transaction_type IN ('withdrawal','transfer')");
    $dailyStmt->execute(['user_id' => $sender_id]);
    $dailyTotal = floatval($dailyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Weekly total
    $weeklyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND transaction_type IN ('withdrawal','transfer')");
    $weeklyStmt->execute(['user_id' => $sender_id]);
    $weeklyTotal = floatval($weeklyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Monthly total
    $monthlyStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE sender_id = :user_id AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND transaction_type IN ('withdrawal','transfer')");
    $monthlyStmt->execute(['user_id' => $sender_id]);
    $monthlyTotal = floatval($monthlyStmt->fetch(PDO::FETCH_ASSOC)['total']);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Check limits: For transfers, we apply limits like withdrawals.
if (($dailyTotal + $amount) > floatval($limits['daily_limit'])) {
    echo json_encode(["error" => "Daily transfer/withdrawal limit exceeded"]);
    exit;
}
if (($weeklyTotal + $amount) > floatval($limits['weekly_limit'])) {
    echo json_encode(["error" => "Weekly transfer/withdrawal limit exceeded"]);
    exit;
}
if (($monthlyTotal + $amount) > floatval($limits['monthly_limit'])) {
    echo json_encode(["error" => "Monthly transfer/withdrawal limit exceeded"]);
    exit;
}

// Begin a database transaction to ensure atomic updates.
$conn->beginTransaction();

try {
    // Deduct the transfer amount from the sender's wallet.
    $stmt = $conn->prepare("UPDATE wallets SET balance = balance - :amount WHERE user_id = :user_id");
    $stmt->execute(["amount" => $amount, "user_id" => $sender_id]);

    // Add the transfer amount to the recipient's wallet.
    $stmt = $conn->prepare("UPDATE wallets SET balance = balance + :amount WHERE user_id = :user_id");
    $stmt->execute(["amount" => $amount, "user_id" => $recipient_id]);

    // Log the transaction in the transactions table.
    $stmt = $conn->prepare("INSERT INTO transactions (sender_id, recipient_id, amount, transaction_type) VALUES (:sender_id, :recipient_id, :amount, 'transfer')");
    $stmt->execute([
        "sender_id" => $sender_id,
        "recipient_id" => $recipient_id,
        "amount" => $amount
    ]);

    $conn->commit();
    // Calculate new sender balance (deducted)
    $newBalance = floatval($sender_wallet['balance']) - $amount;
    echo json_encode(["message" => "Transfer successful.", "new_balance" => $newBalance]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(["error" => "Transfer failed: " . $e->getMessage()]);
}

$conn = null;
?>
