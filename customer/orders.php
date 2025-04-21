<?php
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('/kwu/auth/login.php');
}

// Get specific order details if ID is provided
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    // Get order info
    $stmt = $pdo->prepare("
        SELECT p.*, a.email 
        FROM pesanan p
        JOIN akun a ON p.id_customer = a.id
        WHERE p.id = ? AND p.id_customer = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT pd.*, m.nama as menu_name, m.harga as menu_price
            FROM pesanan_detail pd
            JOIN menu m ON pd.id_menu = m.id
            WHERE pd.id_pesanan = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

// Get all orders if no specific ID or order not found
if (!isset($order)) {
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(pd.id) as total_items
        FROM pesanan p
        LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
        WHERE p.id_customer = ?
        GROUP BY p.id
        ORDER BY p.waktu_pemesanan DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <?php if (isset($order)): ?>
        <!-- Single Order Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-2xl font-bold">Order #<?= $order['id'] ?></h1>
                <span class="px-3 py-1 rounded-full text-sm <?= getStatusClass($order['status']) ?>">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
            
            <div class="mb-6">
                <p class="text-gray-600">
                    Ordered on <?= date('F j, Y g:i A', strtotime($order['waktu_pemesanan'])) ?>
                </p>
            </div>

            <!-- Delivery Information -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold mb-3">Delivery Information</h3>
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <p class="text-gray-600">Recipient Name:</p>
                        <p class="font-medium"><?= htmlspecialchars($order['nama_pemesan']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Delivery Address:</p>
                        <p class="font-medium whitespace-pre-line"><?= htmlspecialchars($order['alamat_pemesan']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Proof -->
            <div class="mb-6">
                <h3 class="font-bold mb-2">Payment Proof</h3>
                <?php if ($order['bukti_pembayaran']): ?>
                    <img src="/kwu/assets/images/uploads/<?= htmlspecialchars($order['bukti_pembayaran']) ?>" 
                         alt="Payment Proof" 
                         class="max-w-sm rounded-lg shadow cursor-pointer"
                         onclick="showFullImage(this.src)"
                         title="Click to view full size">
                <?php else: ?>
                    <p class="text-gray-500 italic">No payment proof uploaded yet</p>
                <?php endif; ?>
            </div>
            
            <div class="mb-6">
                <h3 class="font-bold mb-4">Order Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Item</th>
                                <th class="text-right py-2">Price</th>
                                <th class="text-right py-2">Quantity</th>
                                <th class="text-right py-2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr class="border-b">
                                <td class="py-2"><?= htmlspecialchars($item['menu_name']) ?></td>
                                <td class="text-right py-2">Rp <?= number_format($item['menu_price'], 0, ',', '.') ?></td>
                                <td class="text-right py-2"><?= $item['jumlah'] ?></td>
                                <td class="text-right py-2">Rp <?= number_format($item['menu_price'] * $item['jumlah'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="font-bold">
                                <td colspan="3" class="text-right py-2">Total:</td>
                                <td class="text-right py-2">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-center">
                <a href="orders.php" class="text-orange-600 hover:underline">← Back to Orders</a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Orders List -->
        <h1 class="text-3xl font-bold mb-8">Your Orders</h1>
        
        <?php if (empty($orders)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
            <a href="menu.php" class="text-orange-600 hover:underline">Browse Menu →</a>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold">Order #<?= $order['id'] ?></h3>
                        <p class="text-sm text-gray-500">
                            <?= date('F j, Y g:i A', strtotime($order['waktu_pemesanan'])) ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            <?= htmlspecialchars($order['nama_pemesan']) ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm <?= getStatusClass($order['status']) ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                
                <div class="flex justify-between items-center mb-4">
                    <p class="text-gray-600"><?= $order['total_items'] ?> items</p>
                    <p class="font-bold">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                </div>
                
                <div class="text-right">
                    <a href="?id=<?= $order['id'] ?>" class="text-orange-600 hover:underline">
                        View Details →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
function getStatusClass($status) {
    switch($status) {
        case 'Menunggu Konfirmasi':
            return 'bg-yellow-100 text-yellow-800';
        case 'Diterima':
            return 'bg-green-100 text-green-800';
        case 'Diproses':
            return 'bg-blue-100 text-blue-800';
        case 'Diperjalanan':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

require_once '../includes/footer.php';
?>