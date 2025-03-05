<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../connection/db.php';
require_once __DIR__ . '/../../models/VerificationsModel.php';
require_once __DIR__ . '/../../utils/verify_jwt.php'; // Adjust path if necessary

// Get the Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'No authorization header provided']);
    exit;
}

// Expecting header format: "Bearer <token>"
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
