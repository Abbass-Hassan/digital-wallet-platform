<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Get the user's tier from the users table
try {
    $userStmt = $conn->prepare("SELECT tier FROM users WHERE id = :user_id LIMIT 1");
    $userStmt->execute(['user_id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $tier = $user ? $user['tier'] : 'regular';
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Get limits for the user's tier
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

// Calculate current outgoing transactions (withdrawals + transfers) for the sender
try {
    // Daily total
    $dailyStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE sender_id = :user_id 
          AND DATE(created_at) = CURDATE() 
          AND transaction_type IN ('withdrawal', 'transfer')
    ");
    $dailyStmt->execute(['user_id' => $userId]);
    $dailyUsed = floatval($dailyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Weekly total (using YEARWEEK, mode 1 for Monday as first day)
    $weeklyStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE sender_id = :user_id 
          AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) 
          AND transaction_type IN ('withdrawal', 'transfer')
    ");
    $weeklyStmt->execute(['user_id' => $userId]);
    $weeklyUsed = floatval($weeklyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    // Monthly total
    $monthlyStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE sender_id = :user_id 
          AND MONTH(created_at) = MONTH(CURDATE()) 
          AND YEAR(created_at) = YEAR(CURDATE()) 
          AND transaction_type IN ('withdrawal', 'transfer')
    ");
    $monthlyStmt->execute(['user_id' => $userId]);
    $monthlyUsed = floatval($monthlyStmt->fetch(PDO::FETCH_ASSOC)['total']);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Calculate remaining amounts
$dailyRemaining = max(floatval($limits['daily_limit']) - $dailyUsed, 0);
$weeklyRemaining = max(floatval($limits['weekly_limit']) - $weeklyUsed, 0);
$monthlyRemaining = max(floatval($limits['monthly_limit']) - $monthlyUsed, 0);

// Return the limits usage data
echo json_encode([
    "dailyUsed" => $dailyUsed,
    "dailyLimit" => floatval($limits['daily_limit']),
    "dailyRemaining" => $dailyRemaining,
    "weeklyUsed" => $weeklyUsed,
    "weeklyLimit" => floatval($limits['weekly_limit']),
    "weeklyRemaining" => $weeklyRemaining,
    "monthlyUsed" => $monthlyUsed,
    "monthlyLimit" => floatval($limits['monthly_limit']),
    "monthlyRemaining" => $monthlyRemaining
]);

$conn = null;
?>
