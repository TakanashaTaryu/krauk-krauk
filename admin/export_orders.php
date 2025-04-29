<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    die('Unauthorized access');
}

// Build query with search conditions
$query = "SELECT p.*, a.email as customer_email, a.no_telp as customer_phone,
          GROUP_CONCAT(CONCAT(m.nama, ' (', pd.jumlah, ' pcs)') SEPARATOR ', ') as menu_items
          FROM pesanan p 
          JOIN akun a ON p.id_customer = a.id 
          LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
          LEFT JOIN menu m ON pd.id_menu = m.id
          WHERE 1=1";

$params = [];

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = $_GET['search'];
    $searchBy = $_GET['searchBy'] ?? 'email';
    
    switch ($searchBy) {
        case 'email':
            $query .= " AND a.email LIKE ?";
            $params[] = "%$search%";
            break;
        case 'amount':
            $query .= " AND p.total_harga = ?";
            $params[] = $search;
            break;
        case 'id':
            $query .= " AND p.id = ?";
            $params[] = $search;
            break;
    }
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $query .= " AND p.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $query .= " AND DATE(p.waktu_pemesanan) >= ?";
    $params[] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $query .= " AND DATE(p.waktu_pemesanan) <= ?";
    $params[] = $_GET['date_to'];
}

$query .= " GROUP BY p.id ORDER BY p.waktu_pemesanan DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Clear any previous output
ob_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_report_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Order ID',
    'Customer Email',
    'Customer Phone',
    'Customer Name',
    'Delivery Address',
    'Items',
    'Total (Rp)',
    'Status',
    'Order Time'
]);

// Add data
$total_revenue = 0;
foreach ($orders as $order) {
    fputcsv($output, [
        '#' . $order['id'],
        $order['customer_email'],
        $order['customer_phone'],
        $order['nama_pemesan'],
        $order['alamat_pemesan'],
        $order['menu_items'],
        number_format($order['total_harga'], 0, ',', '.'),
        $order['status'],
        date('d/m/Y H:i', strtotime($order['waktu_pemesanan']))
    ]);
    $total_revenue += $order['total_harga'];
}

// Add total row
fputcsv($output, [
    '',
    '',
    '',
    'Total Revenue:',
    number_format($total_revenue, 0, ',', '.'),
    '',
    '',
    ''
]);

fclose($output);
exit;