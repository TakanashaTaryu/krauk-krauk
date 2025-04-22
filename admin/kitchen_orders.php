<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Get all orders in process
$stmt = $pdo->prepare("
    SELECT p.*, a.email, a.no_telp,
           GROUP_CONCAT(CONCAT(m.nama, ' (', pd.jumlah, ')') SEPARATOR ', ') as menu_items
    FROM pesanan p
    JOIN akun a ON p.id_customer = a.id
    JOIN pesanan_detail pd ON p.id = pd.id_pesanan
    JOIN menu m ON pd.id_menu = m.id
    WHERE p.status = 'Diproses'
    GROUP BY p.id
    ORDER BY p.waktu_pemesanan ASC
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
    redirect('kitchen_orders.php');
}

// Get orders count by status for kitchen
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Diproses'");
$inProcessOrders = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Menunggu Konfirmasi'");
$pendingOrders = $stmt->fetch()['total'];
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Kitchen Dashboard</h1>
        <a href="dashboard.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Kitchen Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-gray-500">Orders In Preparation</h2>
                    <p class="text-3xl font-bold text-gray-800"><?= $inProcessOrders ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <i class="fas fa-utensils text-2xl text-orange-500"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-gray-500">Pending Orders</h2>
                    <p class="text-3xl font-bold text-gray-800"><?= $pendingOrders ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock text-2xl text-yellow-500"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Orders In Preparation</h2>
        
        <?php if (empty($orders)): ?>
        <div class="text-center py-8">
            <i class="fas fa-utensils text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">No orders currently in preparation</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left">Order ID</th>
                        <th class="py-3 px-4 text-left">Time</th>
                        <th class="py-3 px-4 text-left">Customer</th>
                        <th class="py-3 px-4 text-left">Items</th>
                        <th class="py-3 px-4 text-left">Notes</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-4 px-4 font-medium">#<?= $order['id'] ?></td>
                        <td class="py-4 px-4">
                            <?= date('H:i', strtotime($order['waktu_pemesanan'])) ?>
                            <div class="text-xs text-gray-500">
                                <?= date('d M Y', strtotime($order['waktu_pemesanan'])) ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($order['nama_pemesan'] ?? '') ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($order['no_telp'] ?? '') ?></p>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="max-w-xs">
                                <?php 
                                $items = explode(', ', $order['menu_items'] ?? '');
                                foreach ($items as $item): 
                                ?>
                                <div class="mb-1 bg-gray-100 px-2 py-1 rounded-md inline-block mr-1">
                                    <?= htmlspecialchars($item ?? '') ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <?php if (!empty($order['catatan'])): ?>
                            <div class="max-w-xs text-sm bg-yellow-50 p-2 rounded-md border border-yellow-200">
                                <?= nl2br(htmlspecialchars($order['catatan'] ?? '')) ?>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400">No notes</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="Diperjalanan">
                                    <button type="submit" name="update_status" 
                                            class="text-green-600 hover:text-green-900"
                                            onclick="return confirm('Mark this order as ready for delivery?')">
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
        <h2 class="text-xl font-bold mb-4">Kitchen Tips</h2>
        <ul class="list-disc pl-5 space-y-2">
            <li>Prioritize orders based on their arrival time (oldest first)</li>
            <li>Check for any special instructions or notes from customers</li>
            <li>Ensure all items in an order are prepared before marking it ready</li>
            <li>Communicate with delivery staff when orders are almost ready</li>
            <li>Mark orders as "Ready for Delivery" only when they are completely prepared and packaged</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>