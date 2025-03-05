<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../models/TransactionsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
require_once __DIR__ . '/../../utils/MailService.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path as needed

// Remove session_start() and check for JWT in the Authorization header
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

// Extract user ID from the token payload
$userId = $decoded['id'];

try {
    // Initialize models
    $walletsModel = new WalletsModel();
    $verificationsModel = new VerificationsModel();
    $transactionsModel = new TransactionsModel();
    $usersModel = new UsersModel();

    // Check verification status
    $verification = $verificationsModel->getVerificationByUserId($userId);
    if (!$verification || $verification['is_validated'] != 1) {
        echo json_encode(['error' => 'Your account is not verified. You cannot deposit.']);
        exit;
    }

    // Get deposit amount from input
    $data = json_decode(file_get_contents("php://input"), true);
    $amount = floatval($data['amount']);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid deposit amount']);
        exit;
    }

    // Check if the wallet exists
    $wallet = $walletsModel->getWalletByUserId($userId);

    if (!$wallet) {
        // Create a new wallet
        $walletsModel->create($userId, $amount);
        $newBalance = $amount;
    } else {
        // Update wallet balance
        $newBalance = floatval($wallet['balance']) + $amount;
        $walletsModel->update($wallet['id'], $userId, $newBalance);
    }

    // Insert transaction record for deposit
    // For deposits, sender_id is NULL and recipient_id is the user
    $transactionsModel->create(null, $userId, 'deposit', $amount, 'External Deposit');

    // Fetch user's email from the users table for confirmation
    $user = $usersModel->getUserById($userId);
    $userEmail = $user ? $user['email'] : null;

    // Send deposit confirmation email if an email exists
    if ($userEmail) {
        $mailer = new MailService();
        $subject = "Deposit Confirmation";
        $body = "
            <h1>Deposit Successful</h1>
            <p>You have deposited <strong>{$amount} USDT</strong> into your wallet.</p>
            <p>Your new balance is: <strong>{$newBalance} USDT</strong></p>
        ";
        $mailer->sendMail($userEmail, $subject, $body);
    }

    echo json_encode(['newBalance' => $newBalance, 'message' => 'Deposit successful']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
