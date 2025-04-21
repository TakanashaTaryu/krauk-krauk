<?php
require_once '../includes/header.php';

// Remove any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    try {
        $pdo->beginTransaction();

        if ($_GET['action'] === 'remove') {
            $cart_id = (int)$_GET['id'];
            
            // Verify item belongs to user
            $stmt = $pdo->prepare("SELECT id FROM keranjang WHERE id = ? AND id_customer = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                // Delete item
                $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ?");
                $stmt->execute([$cart_id]);
                
                // Get updated cart info
                $stmt = $pdo->prepare("
                    SELECT SUM(k.jumlah * m.harga) as total,
                           COUNT(*) as item_count
                    FROM keranjang k 
                    JOIN menu m ON k.id_menu = m.id 
                    WHERE k.id_customer = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $result = $stmt->fetch();
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'newTotal' => $result['total'] ?? 0,
                    'isEmpty' => ($result['item_count'] ?? 0) === 0,
                    'itemCount' => $result['item_count'] ?? 0
                ]);
                exit;
            } else {
                throw new Exception('Item not found in cart');
            }
        }
        
        elseif ($_GET['action'] === 'update') {
            $cart_id = (int)$_GET['id'];
            $quantity = (int)$_GET['quantity'];
            
            if ($quantity < 1) {
                throw new Exception('Invalid quantity');
            }
            
            // Verify item belongs to user and check stock
            $stmt = $pdo->prepare("
                SELECT k.id, m.stok 
                FROM keranjang k
                JOIN menu m ON k.id_menu = m.id
                WHERE k.id = ? AND k.id_customer = ?
            ");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            $item = $stmt->fetch();
            
            if (!$item) {
                throw new Exception('Item not found in cart');
            }
            
            if ($quantity > $item['stok']) {
                throw new Exception('Requested quantity exceeds available stock');
            }
            
            // Update quantity
            $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_id]);
            
            // Get updated cart info
            $stmt = $pdo->prepare("
                SELECT SUM(k.jumlah * m.harga) as total,
                       COUNT(*) as item_count
                FROM keranjang k 
                JOIN menu m ON k.id_menu = m.id 
                WHERE k.id_customer = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'newTotal' => $result['total'] ?? 0,
                'isEmpty' => ($result['item_count'] ?? 0) === 0,
                'itemCount' => $result['item_count'] ?? 0
            ]);
            exit;
        }
        
        else {
            throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);