<?php
require_once '../config/database.php';
require_once 'qris_generator.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if amount is provided
if (!isset($_GET['amount']) || !is_numeric($_GET['amount'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid amount'
    ]);
    exit;
}

$amount = (int)$_GET['amount'];

// Get static QRIS code from database
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'qris_static'");
$stmt->execute();
$qris_static = $stmt->fetch(PDO::FETCH_COLUMN);

if (!$qris_static) {
    echo json_encode([
        'success' => false,
        'message' => 'QRIS code not configured'
    ]);
    exit;
}

try {
    // Convert static to dynamic QRIS
    $dynamic_qris = convertStaticToDynamicQRIS($qris_static, $amount);
    
    // Return only the QRIS code, let client generate QR
    echo json_encode([
        'success' => true,
        'qris_code' => $dynamic_qris
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}