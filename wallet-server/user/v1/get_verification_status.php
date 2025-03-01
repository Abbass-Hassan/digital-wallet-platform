<?php
// get_verification_status.php
require_once __DIR__ . '/../../connection/db.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT is_validated FROM verifications WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        // If there's no verification row, return a default (like 0)
        echo json_encode(['is_validated' => 0]);
        exit;
    }

    echo json_encode(['is_validated' => (int)$verification['is_validated']]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
