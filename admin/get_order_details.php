<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    die('Unauthorized access');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid order ID');
}

$order_id = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT p.*, a.email, a.no_telp
        FROM pesanan p
        JOIN akun a ON p.id_customer = a.id
        WHERE p.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die('Order not found');
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT pd.*, m.nama as menu_name, m.harga as menu_price
        FROM pesanan_detail pd
        JOIN menu m ON pd.id_menu = m.id
        WHERE pd.id_pesanan = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    // Check if it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON for AJAX requests
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
'data' => [
    'order' => [
        'id' => $order['id'],
        'customer' => [
            'email' => $order['email'],
            'phone' => $order['no_telp'],
            'name' => $order['nama_pemesan'],
            'address' => $order['alamat_pemesan']
        ],
        'orderDate' => date('d M Y H:i', strtotime($order['waktu_pemesanan'])),
        'status' => $order['status'],
        'total' => number_format($order['total_harga'], 0, ',', '.')
    ],
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['menu_name'],
                        'price' => number_format($item['menu_price'], 0, ',', '.'),
                        'quantity' => $item['jumlah'],
                        'subtotal' => number_format($item['menu_price'] * $item['jumlah'], 0, ',', '.')
                    ];
                }, $items)
            ]
        ]);
        exit;
    }

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}?>

<div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
    <div class="sticky top-0 bg-white p-4 border-b flex justify-between items-center">
        <h2 class="text-2xl font-bold">Order #<?= $order_id ?></h2>
        <div class="flex space-x-2">
            <button onclick="closeOrderModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded-md text-sm">
                Cancel
            </button>
            <button onclick="closeOrderModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <?php
    
    // Return HTML for direct access
    include '../admin/order_details.php';
    ?>
</div>
