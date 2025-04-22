<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['message'])) {
    setAlert('error', 'Invalid request');
    redirect('orders.php');
}

$order_id = (int)$_POST['order_id'];
$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

// Validate the order belongs to this customer
$stmt = $pdo->prepare("SELECT id FROM pesanan WHERE id = ? AND id_customer = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    setAlert('error', 'Order not found');
    redirect('orders.php');
}

// Check if a conversation already exists
$stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id_pesanan = ?");
$stmt->execute([$order_id]);
$conversation = $stmt->fetch();

if ($conversation) {
    // If conversation exists, just redirect to it
    $conversation_id = $conversation['id'];
} else {
    // Create new conversation
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (id_pesanan) VALUES (?)");
    $stmt->execute([$order_id]);
    $conversation_id = $pdo->lastInsertId();
}

// Add the message
if (!empty($message)) {
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (id_conversation, sender_id, is_admin, message)
        VALUES (?, ?, 0, ?)
    ");
    $stmt->execute([$conversation_id, $user_id, $message]);
}

// Redirect to chat page
redirect("chat.php?conversation_id=$conversation_id");