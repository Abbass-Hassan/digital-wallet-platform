<?php
// get_balance.php
require_once __DIR__ . '/../../connection/db.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // If there's no wallet row, return a default 0 or an error
        echo json_encode(['balance' => 0]);
        exit;
    }

    echo json_encode(['balance' => floatval($wallet['balance'])]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>
