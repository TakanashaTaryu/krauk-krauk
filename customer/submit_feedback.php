<?php
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['rating'])) {
    setAlert('error', 'Invalid request');
    redirect('orders.php');
}

$order_id = (int)$_POST['order_id'];
$rating = (int)$_POST['rating'];
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate rating
if ($rating < 1 || $rating > 5) {
    setAlert('error', 'Invalid rating');
    redirect("orders.php?id=$order_id");
}

// Verify the order belongs to this customer and has "Telah Sampai" status
$stmt = $pdo->prepare("
    SELECT id FROM pesanan 
    WHERE id = ? AND id_customer = ? AND status = 'Telah Sampai'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setAlert('error', 'Order not found or cannot be rated');
    redirect('orders.php');
}

// Check if feedback already exists
$stmt = $pdo->prepare("SELECT id FROM feedback WHERE id_pesanan = ?");
$stmt->execute([$order_id]);
if ($stmt->fetch()) {
    setAlert('error', 'You have already submitted feedback for this order');
    redirect("orders.php?id=$order_id");
}

// Insert feedback
$stmt = $pdo->prepare("
    INSERT INTO feedback (id_pesanan, rating, komentar) 
    VALUES (?, ?, ?)
");
$result = $stmt->execute([$order_id, $rating, $comment]);

if ($result) {
    setAlert('success', 'Thank you for your feedback!');
} else {
    setAlert('error', 'Failed to submit feedback. Please try again.');
}

redirect("orders.php?id=$order_id");