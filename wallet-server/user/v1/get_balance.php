<?php
// get_balance.php
require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/WalletsModel.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Initialize WalletsModel
    $walletsModel = new WalletsModel();

    // Fetch wallet balance
    $wallet = $walletsModel->getWalletByUserId($userId);

    if (!$wallet) {
        // If there's no wallet row, return a default 0
        echo json_encode(['balance' => 0]);
        exit;
    }

    echo json_encode(['balance' => floatval($wallet['balance'])]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
