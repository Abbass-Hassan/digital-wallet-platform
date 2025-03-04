<?php
// get_verification_status.php
require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Initialize VerificationsModel
    $verificationsModel = new VerificationsModel();

    // Fetch verification status
    $verification = $verificationsModel->getVerificationByUserId($userId);

    if (!$verification) {
        // If there's no verification row, return a default (like 0)
        echo json_encode(['is_validated' => 0]);
        exit;
    }

    echo json_encode(['is_validated' => (int)$verification['is_validated']]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
