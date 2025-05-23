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
    WHERE p.status IN ('Diterima','Diproses')
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
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status IN ('Diterima','Diproses')");
$inProcessOrders = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Menunggu Konfirmasi'");
$pendingOrders = $stmt->fetch()['total'];

// Get total menu items ordered with status "Diterima" or "Diproses"
$stmt = $pdo->query("
    SELECT m.id, m.nama, SUM(pd.jumlah) as total_ordered
    FROM pesanan_detail pd
    JOIN menu m ON pd.id_menu = m.id
    JOIN pesanan p ON pd.id_pesanan = p.id
    WHERE p.status IN ('Diterima', 'Diproses')
    GROUP BY m.id, m.nama
    ORDER BY total_ordered DESC
");
$menuTotals = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Kitchen Dashboard</h1>
        <a href="dashboard.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Halaman Utama
        </a>
    </div>
    
    <!-- Kitchen Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-gray-500">Pesanan dalam Persiapan</h2>
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
                    <h2 class="text-gray-500">Pesanan Tertunda</h2>
                    <p class="text-3xl font-bold text-gray-800"><?= $pendingOrders ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock text-2xl text-yellow-500"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Menu Items to Prepare -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Total menu yang ingin dibuat</h2>
        
        <?php if (empty($menuTotals)): ?>
        <div class="text-center py-8">
            <i class="fas fa-hamburger text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">Tidak ada menu yang ingin dibuat</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($menuTotals as $item): ?>
            <div class="bg-gray-50 rounded-lg p-4 flex items-center">
                <div class="bg-orange-100 p-3 rounded-full mr-3">
                    <i class="fas fa-utensils text-orange-500"></i>
                </div>
                <div>
                    <h3 class="font-medium"><?= htmlspecialchars($item['nama']) ?></h3>
                    <p class="text-2xl font-bold text-orange-600"><?= $item['total_ordered'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Pesanan yang igin dibuat</h2>
        
        <?php if (empty($orders)): ?>
        <div class="text-center py-8">
            <i class="fas fa-utensils text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">Tidak ada pesanan yang ingin dibuat</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left">ID Pesanan</th>
                        <th class="py-3 px-4 text-left">Waktu</th>
                        <th class="py-3 px-4 text-left">Pelanggan</th>
                        <th class="py-3 px-4 text-left">Items</th>
                        <th class="py-3 px-4 text-left">Catatan</th>
                        <th class="py-3 px-4 text-left">Aksi</th>
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
                            <?php if (!empty($order['notes'])): ?>
                            <div class="max-w-xs text-sm bg-yellow-50 p-2 rounded-md border border-yellow-200">
                                <?= nl2br(htmlspecialchars($order['notes'] ?? '')) ?>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400">Tidak ada catatan</span>
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
</div>

<?php require_once '../includes/footer.php'; ?>