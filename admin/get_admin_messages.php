<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation ID']);
    exit;
}

// Check for new messages
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM chat_messages
    WHERE id_conversation = ? AND is_admin = 0 AND is_read = 0
");
$stmt->execute([$conversation_id]);
$result = $stmt->fetch();

// If there are new messages, tell the client to refresh
echo json_encode(['refresh' => $result['count'] > 0]);