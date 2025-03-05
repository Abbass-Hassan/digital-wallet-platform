<?php
header("Content-Type: application/json");

// Remove session_start() and session checks
// session_start();
// if (!isset($_SESSION['user_id'])) ...

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';

// Include your JWT verification helper
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path if needed

// Get the Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'No authorization header']);
    exit;
}

// Expected format: "Bearer <token>"
list($bearer, $jwt) = explode(' ', $headers['Authorization']);
if ($bearer !== 'Bearer' || !$jwt) {
    echo json_encode(['error' => 'Invalid token format']);
    exit;
}

// Verify/Decode the JWT
$jwt_secret = "CHANGE_THIS_TO_A_RANDOM_SECRET_KEY";
$decoded = verify_jwt($jwt, $jwt_secret);
if (!$decoded) {
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Extract user ID from the token payload
$userId = $decoded['id'];

try {
    // Initialize WalletsModel
    $walletsModel = new WalletsModel();

    // Fetch wallet balance
    $wallet = $walletsModel->getWalletByUserId($userId);

    if (!$wallet) {
        // If there's no wallet record, return 0
        echo json_encode(['balance' => 0]);
        exit;
    }

    echo json_encode(['balance' => floatval($wallet['balance'])]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
