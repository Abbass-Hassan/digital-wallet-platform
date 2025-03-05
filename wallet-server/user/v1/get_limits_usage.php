<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../models/TransactionLimitsModel.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path as needed

// Get the Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["error" => "No authorization header provided."]);
    exit;
}

// Expected format: "Bearer <token>"
list($bearer, $jwt) = explode(' ', $headers['Authorization']);
if ($bearer !== 'Bearer' || !$jwt) {
    echo json_encode(["error" => "Invalid token format."]);
    exit;
}

// Verify and decode the JWT
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY"; // Use a secure secret in production
$decoded = verify_jwt($jwt, $jwt_secret);
if (!$decoded) {
    echo json_encode(["error" => "Invalid or expired token."]);
    exit;
}

// Extract the user ID from the token payload
$userId = $decoded['id'];

try {
    // Initialize models
    $usersModel = new UsersModel();
    $transactionLimitsModel = new TransactionLimitsModel();

    // Get the user's tier from the users table
    $user = $usersModel->getUserById($userId);
    $tier = $user ? $user['tier'] : 'regular';

    // Get limits for the user's tier
    $limits = $transactionLimitsModel->getTransactionLimitByTier($tier);
    if (!$limits) {
        echo json_encode(["error" => "Transaction limits not defined for your tier"]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// Keep calculations unchanged
try {
    $dailyStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE sender_id = :user_id 
          AND DATE(created_at) = CURDATE() 
          AND transaction_type IN ('withdrawal', 'transfer')
    ");
    $dailyStmt->execute(['user_id' => $userId]);
    $dailyUsed = floatval($dailyStmt->fetch(PDO::FETCH_ASSOC)['total']);

    $weeklyStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total 
        FROM transactions 
        WHERE sender_id = :user_id 
          AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) 
          AND transaction_type IN ('withdrawal', 'transfer')
    ");
    $weeklyStmt->execute(['user_id' => $userId]);
    $weeklyUsed = floatval($weeklyStmt->fetch(PDO::FETCH_ASSOC)['total']);

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
