<?php
// Start session and include required files at the very top
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle different actions
if ($action === 'remove') {
    // Remove item from cart
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_customer = ?");
    $result = $stmt->execute([$id, $_SESSION['user_id']]);
    
    if ($result) {
        // Get updated cart info
        $cartInfo = getCartInfo($pdo, $_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Item removed successfully',
            'total' => $cartInfo['total'],
            'count' => $cartInfo['count']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
    exit;
} elseif ($action === 'update') {
    // Update item quantity
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // First check if the item exists and get its details
    $stmt = $pdo->prepare("
        SELECT k.id, m.stok, m.harga 
        FROM keranjang k
        JOIN menu m ON k.id_menu = m.id
        WHERE k.id = ? AND k.id_customer = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
    
    if (!$item) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    // Make sure quantity doesn't exceed stock
    if ($quantity > $item['stok']) {
        $quantity = $item['stok'];
    }
    
    // Update quantity
    $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ? AND id_customer = ?");
    $result = $stmt->execute([$quantity, $id, $_SESSION['user_id']]);
    
    if ($result) {
        // Calculate item subtotal
        $itemSubtotal = $item['harga'] * $quantity;
        
        // Get updated cart info
        $cartInfo = getCartInfo($pdo, $_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated successfully',
            'total' => $cartInfo['total'],
            'count' => $cartInfo['count'],
            'item_subtotal' => $itemSubtotal
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
    }
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Helper function to get cart information
function getCartInfo($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT SUM(m.harga * k.jumlah) as total, COUNT(k.id) as count
        FROM keranjang k
        JOIN menu m ON k.id_menu = m.id
        WHERE k.id_customer = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    return [
        'total' => $result['total'] ?? 0,
        'count' => $result['count'] ?? 0
    ];
}