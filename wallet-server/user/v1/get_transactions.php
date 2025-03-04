<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/TransactionsModel.php';
require_once __DIR__ . '/../../models/UsersModel.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];

$date = isset($_GET['date']) ? $_GET['date'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    // Initialize models
    $transactionsModel = new TransactionsModel();
    $usersModel = new UsersModel();

    // Fetch all transactions
    $transactions = $transactionsModel->getAllTransactions();

    // Filter transactions manually
    $filteredTransactions = [];

    foreach ($transactions as $transaction) {
        // Check if the user is the sender or recipient
        if ($transaction['sender_id'] != $userId && $transaction['recipient_id'] != $userId) {
            continue;
        }

        // Filter by type if provided
        if ($type && $transaction['transaction_type'] !== $type) {
            continue;
        }

        // Filter by date if provided
        if ($date && date('Y-m-d', strtotime($transaction['created_at'])) !== $date) {
            continue;
        }

        // Get sender email (null if sender_id is null)
        $transaction['sender_email'] = $transaction['sender_id']
            ? $usersModel->getUserById($transaction['sender_id'])['email']
            : null;

        // Get recipient email (null if recipient_id is null)
        $transaction['recipient_email'] = $transaction['recipient_id']
            ? $usersModel->getUserById($transaction['recipient_id'])['email']
            : null;

        $filteredTransactions[] = $transaction;
    }

    echo json_encode(["transactions" => $filteredTransactions, "userId" => $userId]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
