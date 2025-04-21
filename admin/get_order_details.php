<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    die('Unauthorized access');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order ID'
    ]);
    exit;
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
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT pd.*, m.nama as menu_name
        FROM pesanan_detail pd
        JOIN menu m ON pd.id_menu = m.id
        WHERE pd.id_pesanan = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    echo json_encode([
        'success' => true,
        'data' => [
            'order' => [
                'id' => $order['id'],
                'customer' => [
                    'email' => $order['email'],
                    'phone' => $order['no_telp'],
                    'name' => $order['nama_pemesan'],
                    'address' => $order['alamat_pemesan'],
                    'latitude' => $order['latitude'],
                    'longitude' => $order['longitude']
                ],
                'status' => $order['status'],
                'total' => $order['total_harga'],
                'date' => $order['waktu_pemesanan'],
                'payment_proof' => $order['bukti_pembayaran']
            ],
            'items' => array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['menu_name'],
                    'price' => $item['harga_satuan'],
                    'quantity' => $item['jumlah'],
                    'subtotal' => $item['harga_satuan'] * $item['jumlah']
                ];
            }, $order_items)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
