<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Get all orders in transit
$stmt = $pdo->prepare("
    SELECT p.*, a.email, a.no_telp,
           GROUP_CONCAT(m.nama SEPARATOR ', ') as menu_items
    FROM pesanan p
    JOIN akun a ON p.id_customer = a.id
    JOIN pesanan_detail pd ON p.id = pd.id_pesanan
    JOIN menu m ON pd.id_menu = m.id
    WHERE p.status = 'Diperjalanan'
    GROUP BY p.id
    ORDER BY p.waktu_pemesanan DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    setAlert('success', 'Order status updated successfully');
    redirect('driver_orders.php');
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Driver Dashboard</h1>
        <a href="dashboard.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Orders In Transit</h2>
        
        <?php if (empty($orders)): ?>
        <div class="text-center py-8">
            <i class="fas fa-truck text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">No orders currently in transit</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left">Order ID</th>
                        <th class="py-3 px-4 text-left">Customer</th>
                        <th class="py-3 px-4 text-left">Delivery Address</th>
                        <th class="py-3 px-4 text-left">Items</th>
                        <th class="py-3 px-4 text-left">Total</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4">#<?= $order['id'] ?></td>
                        <td class="py-4 px-4">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($order['nama_pemesan']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($order['email']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($order['no_telp']) ?></p>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="max-w-xs">
                                <p><?= nl2br(htmlspecialchars($order['alamat_pemesan'])) ?></p>
                                <?php if (!empty($order['latitude']) && !empty($order['longitude'])): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>" 
                                   target="_blank" 
                                   class="text-blue-600 hover:underline inline-flex items-center mt-2">
                                    <i class="fas fa-directions mr-1"></i> Get Directions
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="max-w-xs truncate" title="<?= htmlspecialchars($order['menu_items']) ?>">
                                <?= htmlspecialchars($order['menu_items']) ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="Telah Sampai">
                                    <button type="submit" name="update_status" 
                                            class="text-green-600 hover:text-green-900"
                                            onclick="return confirm('Mark this order as delivered?')">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Delivery Tips</h2>
        <ul class="list-disc pl-5 space-y-2">
            <li>Always verify the customer's identity before handing over the order</li>
            <li>Check that all items are included in the package before leaving</li>
            <li>Use the map directions for the most efficient route</li>
            <li>Mark orders as "Delivered" only after successful handover</li>
            <li>Contact the restaurant if you encounter any issues during delivery</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>