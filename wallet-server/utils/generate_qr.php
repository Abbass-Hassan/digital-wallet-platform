<?php
// generate_qr.php

require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

// Get parameters from the query string
$recipientId = isset($_GET['recipient_id']) ? (int) $_GET['recipient_id'] : 0;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 10.0;

// Build the receive_payment URL
$data = "http://localhost/digital-wallet-platform/wallet-server/user/v1/receive_payment.php?recipient_id={$recipientId}&amount={$amount}";

// Create the QR code
$qrCode = new QrCode(
    data: $data,
    errorCorrectionLevel: ErrorCorrectionLevel::High,
    size: 300,
    margin: 10
);

$writer = new PngWriter();
$result = $writer->write($qrCode);

// Output as PNG
header('Content-Type: ' . $result->getMimeType());
echo $result->getString();
exit;
