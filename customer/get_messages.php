<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation ID']);
    exit;
}

// Check if conversation belongs to this user
$stmt = $pdo->prepare("
    SELECT cc.id
    FROM chat_conversations cc
    JOIN pesanan p ON cc.id_pesanan = p.id
    WHERE cc.id = ? AND p.id_customer = ?
");
$stmt->execute([$conversation_id, $user_id]);
$conversation = $stmt->fetch();

if (!$conversation) {
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

// Check for new messages
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM chat_messages
    WHERE id_conversation = ? AND is_admin = 1 AND is_read = 0
");
$stmt->execute([$conversation_id]);
$result = $stmt->fetch();

// If there are new messages, tell the client to refresh
echo json_encode(['refresh' => $result['count'] > 0]);