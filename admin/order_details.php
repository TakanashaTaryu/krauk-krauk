<?php

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
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!-- Replace the entire container structure -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
    <div class="sticky top-0 bg-white p-4 border-b flex justify-between items-center">
    <h2 class="text-2xl font-bold">Order #<?= $order_id ?></h2>
    <div class="flex space-x-2">
        <button onclick="location.reload()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

        <div class="p-6 space-y-6">
            <!-- Customer Information -->
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-gray-600">Email</p>
                        <p class="font-medium"><?= htmlspecialchars($order['email']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Phone</p>
                        <p class="font-medium"><?= $order['no_telp'] ? htmlspecialchars($order['no_telp']) : '<span class="text-gray-400">Not provided</span>' ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Order Date</p>
                        <p class="font-medium"><?= date('d M Y H:i', strtotime($order['waktu_pemesanan'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Delivery Information</h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <p class="text-gray-600">Nama Pemesan</p>
                        <p class="font-medium"><?= htmlspecialchars($order['nama_pemesan']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Alamat Pengiriman</p>
                        <p class="font-medium whitespace-pre-line"><?= htmlspecialchars($order['alamat_pemesan']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Order Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left">Item</th>
                                <th class="px-4 py-2 text-right">Price</th>
                                <th class="px-4 py-2 text-right">Quantity</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?= htmlspecialchars($item['menu_name']) ?></td>
                                <td class="px-4 py-2 text-right">Rp <?= number_format($item['menu_price'], 0, ',', '.') ?></td>
                                <td class="px-4 py-2 text-right"><?= $item['jumlah'] ?></td>
                                <td class="px-4 py-2 text-right">Rp <?= number_format($item['menu_price'] * $item['jumlah'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="font-bold">
                                <td colspan="3" class="px-4 py-2 text-right">Total:</td>
                                <td class="px-4 py-2 text-right">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Proof -->
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Payment Proof</h3>
                <?php if ($order['bukti_pembayaran']): ?>
                    <div class="max-w-md">
                        <img src="/kwu/assets/images/uploads/<?= htmlspecialchars($order['bukti_pembayaran']) ?>" 
                             alt="Payment Proof" 
                             class="w-full rounded-lg shadow cursor-pointer"
                             onclick="showFullImage(this.src)"
                             title="Click to view full size">
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">No payment proof uploaded yet</p>
                <?php endif; ?>
            </div>

            <!-- Order Status -->
            <div class="pb-4">
                <h3 class="font-semibold text-lg mb-4">Order Status</h3>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 rounded-full text-sm <?= getStatusClass($order['status']) ?>">
                        <?= $order['status'] ?>
                    </span>
                    <select id="orderStatus" class="border rounded-md px-3 py-1" onchange="updateStatus(this.value)">
                        <option value="Menunggu Konfirmasi" <?= $order['status'] == 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                        <option value="Diterima" <?= $order['status'] == 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="Diproses" <?= $order['status'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="Diperjalanan" <?= $order['status'] == 'Diperjalanan' ? 'selected' : '' ?>>Diperjalanan</option>
                        <option value="Selesai" <?= $order['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
            </div>
        </div>
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
?>

<script>




function showFullImage(src) {
    Swal.fire({
        imageUrl: src,
        imageAlt: 'Payment Proof',
        width: '80%',
        confirmButtonText: 'Close'
    });
}

function updateStatus(status) {
    // Add your status update logic here
}
</script>