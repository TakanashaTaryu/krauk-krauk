<?php
// Turn off output buffering and disable error display to prevent HTML in JSON responses
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../includes/header.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Prevent admin access
if (isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get action and validate parameters
$action = $_GET['action'] ?? '';
$cart_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify cart item belongs to current user
if ($cart_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM keranjang WHERE id = ? AND id_customer = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    if ($stmt->fetchColumn() == 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        exit;
    }
}

// Helper function to get cart data
function getCartData($user_id, $pdo) {
    // Get cart items
    $stmt = $pdo->prepare("
        SELECT k.id, k.jumlah, m.id as menu_id, m.harga 
        FROM keranjang k
        JOIN menu m ON k.id_menu = m.id
        WHERE k.id_customer = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    // Calculate total
    $total = 0;
    $count = count($items);
    
    foreach ($items as $item) {
        $item_total = $item['harga'] * $item['jumlah'];
        
        // Add add-ons price
        $stmt = $pdo->prepare("
            SELECT ma.harga 
            FROM keranjang_add_ons ka
            JOIN menu_add_ons ma ON ka.id_add_on = ma.id
            WHERE ka.id_keranjang = ?
        ");
        $stmt->execute([$item['id']]);
        $addons = $stmt->fetchAll();
        
        foreach ($addons as $addon) {
            $item_total += $addon['harga'] * $item['jumlah'];
        }
        
        $total += $item_total;
    }
    
    return [
        'count' => $count,
        'total' => $total
    ];
}

// Process actions
try {
    // Clear any previous output
    ob_clean();
    
    switch ($action) {
        case 'remove':
            // Remove cart item and its add-ons
            $pdo->beginTransaction();
            
            // Delete add-ons first
            $stmt = $pdo->prepare("DELETE FROM keranjang_add_ons WHERE id_keranjang = ?");
            $stmt->execute([$cart_id]);
            
            // Delete cart item
            $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_customer = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Get updated cart count and total
            $cart_data = getCartData($_SESSION['user_id'], $pdo);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Item removed from cart',
                'count' => $cart_data['count'],
                'total' => $cart_data['total']
            ]);
            break;
            
        case 'update':
            // Update cart item quantity
            $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 0;
            
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero');
            }
            
            // Get menu item details to check stock
            $stmt = $pdo->prepare("
                SELECT m.stok, m.harga, k.id_menu 
                FROM keranjang k
                JOIN menu m ON k.id_menu = m.id
                WHERE k.id = ?
            ");
            $stmt->execute([$cart_id]);
            $menu_item = $stmt->fetch();
            
            if (!$menu_item) {
                throw new Exception('Menu item not found');
            }
            
            if ($quantity > $menu_item['stok']) {
                throw new Exception('Requested quantity exceeds available stock');
            }
            
            // Update quantity
            $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_id]);
            
            // Calculate item subtotal with add-ons
            $item_subtotal = $menu_item['harga'] * $quantity;
            
            // Get add-ons for this cart item
            $stmt = $pdo->prepare("
                SELECT ma.harga 
                FROM keranjang_add_ons ka
                JOIN menu_add_ons ma ON ka.id_add_on = ma.id
                WHERE ka.id_keranjang = ?
            ");
            $stmt->execute([$cart_id]);
            $addons = $stmt->fetchAll();
            
            foreach ($addons as $addon) {
                $item_subtotal += $addon['harga'] * $quantity;
            }
            
            // Get updated cart data
            $cart_data = getCartData($_SESSION['user_id'], $pdo);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Quantity updated',
                'item_subtotal' => $item_subtotal,
                'base_price' => $menu_item['harga'] * $quantity,
                'count' => $cart_data['count'],
                'total' => $cart_data['total']
            ]);
            break;
            
        case 'update_addon':
            // Update cart add-ons
            $addon_id = isset($_GET['addon_id']) ? (int)$_GET['addon_id'] : 0;
            $checked = isset($_GET['checked']) ? (int)$_GET['checked'] : 0;
            
            // Verify add-on exists and belongs to the menu item
            $stmt = $pdo->prepare("
                SELECT ma.*, k.id_menu, k.jumlah
                FROM menu_add_ons ma
                JOIN keranjang k ON ma.id_menu = k.id_menu
                WHERE ma.id = ? AND k.id = ?
            ");
            $stmt->execute([$addon_id, $cart_id]);
            $addon = $stmt->fetch();
            
            if (!$addon) {
                throw new Exception('Invalid add-on for this menu item');
            }
            
            $pdo->beginTransaction();
            
            if ($checked) {
                // Check if the add-on already exists to avoid duplicates
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM keranjang_add_ons 
                    WHERE id_keranjang = ? AND id_add_on = ?
                ");
                $stmt->execute([$cart_id, $addon_id]);
                $exists = $stmt->fetchColumn();
                
                if (!$exists) {
                    // Add the add-on to cart
                    $stmt = $pdo->prepare("
                        INSERT INTO keranjang_add_ons (id_keranjang, id_add_on)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$cart_id, $addon_id]);
                }
            } else {
                // Remove the add-on from cart
                $stmt = $pdo->prepare("
                    DELETE FROM keranjang_add_ons 
                    WHERE id_keranjang = ? AND id_add_on = ?
                ");
                $stmt->execute([$cart_id, $addon_id]);
            }
            
            $pdo->commit();
            
            // Calculate item subtotal with all add-ons
            $stmt = $pdo->prepare("
                SELECT m.harga, k.jumlah
                FROM keranjang k
                JOIN menu m ON k.id_menu = m.id
                WHERE k.id = ?
            ");
            $stmt->execute([$cart_id]);
            $item = $stmt->fetch();
            
            $base_price = $item['harga'] * $item['jumlah'];
            $item_subtotal = $base_price;
            
            // Get all add-ons for this cart item
            $stmt = $pdo->prepare("
                SELECT ma.harga 
                FROM keranjang_add_ons ka
                JOIN menu_add_ons ma ON ka.id_add_on = ma.id
                WHERE ka.id_keranjang = ?
            ");
            $stmt->execute([$cart_id]);
            $addons = $stmt->fetchAll();
            
            foreach ($addons as $addon) {
                $item_subtotal += $addon['harga'] * $item['jumlah'];
            }
            
            // Get updated cart data
            $cart_data = getCartData($_SESSION['user_id'], $pdo);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Add-on updated',
                'item_subtotal' => $item_subtotal,
                'base_price' => $base_price,
                'count' => $cart_data['count'],
                'total' => $cart_data['total']
            ]);
            break;
            
        case 'add':
            // Add item to cart
            $menu_id = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : 0;
            $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
            
            if ($menu_id <= 0) {
                throw new Exception('Invalid menu item');
            }
            
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero');
            }
            
            // Check if menu item exists and has enough stock
            $stmt = $pdo->prepare("SELECT id, stok FROM menu WHERE id = ?");
            $stmt->execute([$menu_id]);
            $menu_item = $stmt->fetch();
            
            if (!$menu_item) {
                throw new Exception('Menu item not found');
            }
            
            if ($quantity > $menu_item['stok']) {
                throw new Exception('Requested quantity exceeds available stock');
            }
            
            // Check if item already exists in cart
            $stmt = $pdo->prepare("
                SELECT id, jumlah FROM keranjang 
                WHERE id_customer = ? AND id_menu = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $menu_id]);
            $existing_item = $stmt->fetch();
            
            $pdo->beginTransaction();
            
            if ($existing_item) {
                // Update quantity if item already in cart
                $new_quantity = $existing_item['jumlah'] + $quantity;
                
                if ($new_quantity > $menu_item['stok']) {
                    throw new Exception('Total quantity exceeds available stock');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE keranjang SET jumlah = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$new_quantity, $existing_item['id']]);
                
                $cart_id = $existing_item['id'];
                $message = 'Item quantity updated in cart';
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare("
                    INSERT INTO keranjang (id_customer, id_menu, jumlah) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $menu_id, $quantity]);
                
                $cart_id = $pdo->lastInsertId();
                $message = 'Item added to cart';
            }
            
            // Add selected add-ons if any
            if (isset($_GET['addons']) && is_array($_GET['addons'])) {
                foreach ($_GET['addons'] as $addon_id) {
                    // Verify add-on belongs to this menu
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM menu_add_ons 
                        WHERE id = ? AND id_menu = ?
                    ");
                    $stmt->execute([(int)$addon_id, $menu_id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $stmt = $pdo->prepare("
                            INSERT INTO keranjang_add_ons (id_keranjang, id_add_on) 
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$cart_id, (int)$addon_id]);
                    }
                }
            }
            
            $pdo->commit();
            
            // Get updated cart data
            $cart_data = getCartData($_SESSION['user_id'], $pdo);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'count' => $cart_data['count'],
                'total' => $cart_data['total']
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Clear any previous output
    ob_clean();
    
    // Set proper HTTP status code for errors
    http_response_code(400);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit; // Make sure to exit after sending the error response
}

// End output buffering and flush
ob_end_flush();
?>