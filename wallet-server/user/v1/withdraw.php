<?php
// withdraw.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/TransactionsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../models/TransactionLimitsModel.php';
require_once __DIR__ . '/../../utils/MailService.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path as needed

// Remove session_start() and use JWT for authentication
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

// Replace with your secure secret key
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY";
$decoded = verify_jwt($jwt, $jwt_secret);
if (!$decoded) {
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Extract user ID from the JWT payload
$userId = $decoded['id'];

try {
    // Initialize models
    $walletsModel = new WalletsModel();
    $verificationsModel = new VerificationsModel();
    $transactionsModel = new TransactionsModel();
    $usersModel = new UsersModel();
    $transactionLimitsModel = new TransactionLimitsModel();

    // Check verification status
    $verification = $verificationsModel->getVerificationByUserId($userId);
    if (!$verification || $verification['is_validated'] != 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot withdraw.']);
        exit;
    }

    // Get withdrawal amount from input
    $data = json_decode(file_get_contents("php://input"), true);
    $amount = floatval($data['amount']);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid withdrawal amount']);
        exit;
    }

    // Fetch user's tier
    $user = $usersModel->getUserById($userId);
    $tier = $user ? $user['tier'] : 'regular';

    // Fetch limits for this tier
    $limits = $transactionLimitsModel->getTransactionLimitByTier($tier);
    if (!$limits) {
        echo json_encode(['error' => 'Transaction limits not defined for your tier']);
        exit;
    }

    // Calculate current usage
    try {
        $dailyStmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total 
            FROM transactions 
            WHERE sender_id = :user_id 
              AND DATE(created_at) = CURDATE() 
              AND transaction_type IN ('withdrawal', 'transfer')
        ");
        $dailyStmt->execute(['user_id' => $userId]);
        $dailyTotal = floatval($dailyStmt->fetch(PDO::FETCH_ASSOC)['total']);

        $weeklyStmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total 
            FROM transactions 
            WHERE sender_id = :user_id 
              AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) 
              AND transaction_type IN ('withdrawal', 'transfer')
        ");
        $weeklyStmt->execute(['user_id' => $userId]);
        $weeklyTotal = floatval($weeklyStmt->fetch(PDO::FETCH_ASSOC)['total']);

        $monthlyStmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total 
            FROM transactions 
            WHERE sender_id = :user_id 
              AND MONTH(created_at) = MONTH(CURDATE()) 
              AND YEAR(created_at) = YEAR(CURDATE()) 
              AND transaction_type IN ('withdrawal', 'transfer')
        ");
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
    $wallet = $walletsModel->getWalletByUserId($userId);
    if (!$wallet) {
        echo json_encode(['error' => 'Wallet not found']);
        exit;
    }
    if (floatval($wallet['balance']) < $amount) {
        echo json_encode(['error' => 'Insufficient funds']);
        exit;
    }

    $newBalance = floatval($wallet['balance']) - $amount;
    $walletsModel->update($wallet['id'], $userId, $newBalance);

    // Insert transaction record for withdrawal (recipient_id is NULL)
    $transactionsModel->create($userId, NULL, 'withdrawal', $amount);

    // Fetch user's email for confirmation
    $userEmail = $user ? $user['email'] : null;

    // Send email confirmation if email is available
    if ($userEmail) {
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
?>
