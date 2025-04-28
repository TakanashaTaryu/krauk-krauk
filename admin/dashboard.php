<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Get summary data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM akun WHERE admin_value = 0");
$totalCustomers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM menu");
$totalMenuItems = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Menunggu Konfirmasi'");
$pendingOrders = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan");
$totalOrders = $stmt->fetch()['total'];

// Get orders in transit count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Diperjalanan'");
$inTransitOrders = $stmt->fetch()['total'];

// Get financial data
$stmt = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status IN ('Diterima','Telah Sampai','Diproses','Diperjalanan','Telah Samapai')");
$totalRevenue = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status IN ('Diterima','Telah Sampai','Diproses','Diperjalanan','Telah Samapai') AND DATE(waktu_pemesanan) = CURDATE()");
$todayRevenue = $stmt->fetch()['total'] ?? 0;

// Get this week's revenue
$stmt = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan 
                     WHERE status IN ('Diterima','Telah Sampai','Diproses','Diperjalanan','Telah Samapai') 
                     AND YEARWEEK(waktu_pemesanan, 1) = YEARWEEK(CURDATE(), 1)");
$thisWeekRevenue = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE DATE(waktu_pemesanan) = CURDATE()");
$todayOrders = $stmt->fetch()['total'];

// Get top selling items
$stmt = $pdo->query("
    SELECT m.nama, COUNT(*) as total_orders, SUM(dp.jumlah) as total_quantity
    FROM pesanan_detail dp
    JOIN menu m ON dp.id_menu = m.id
    JOIN pesanan p ON dp.id_pesanan = p.id
    WHERE p.status = 'Diterima' OR p.status = 'Diproses' OR p.status = 'Diperjalanan' OR p.status = 'Telah Sampai'
    GROUP BY dp.id_menu
    ORDER BY total_quantity DESC
    LIMIT 5
");
$topItems = $stmt->fetchAll();

// Get recent orders
$stmt = $pdo->query("
    SELECT p.id, a.email as customer_email, p.total_harga, p.status, p.waktu_pemesanan,
           GROUP_CONCAT(m.nama SEPARATOR ', ') as menu_items
    FROM pesanan p
    JOIN akun a ON p.id_customer = a.id
    JOIN pesanan_detail dp ON p.id = dp.id_pesanan
    JOIN menu m ON dp.id_menu = m.id
    GROUP BY p.id
    ORDER BY p.waktu_pemesanan DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll();

// Get monthly revenue data
$stmt = $pdo->query("
    SELECT DATE_FORMAT(waktu_pemesanan, '%Y-%m') as month,
           SUM(total_harga) as monthly_total
    FROM pesanan
    WHERE status = 'Diterima' OR status = 'Diproses' OR status = 'Diperjalanan' OR status = 'Telah Sampai'
    GROUP BY DATE_FORMAT(waktu_pemesanan, '%Y-%m')
    ORDER BY month DESC
");
$monthlyRevenue = $stmt->fetchAll();

// Get weekly revenue data - updating this query to properly group by week
$stmt = $pdo->query("
    SELECT 
        YEARWEEK(waktu_pemesanan, 1) as week_number,
        MIN(DATE(waktu_pemesanan)) as week_start,
        DATE_ADD(MIN(DATE(waktu_pemesanan)), INTERVAL 6 DAY) as week_end,
        SUM(total_harga) as weekly_total
    FROM pesanan
    WHERE status IN ('Diterima', 'Diproses', 'Diperjalanan', 'Telah Sampai')
    GROUP BY YEARWEEK(waktu_pemesanan, 1)
    ORDER BY week_number DESC
    LIMIT 8
");
$weeklyRevenue = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8 text-gray-800">Admin Dashboard</h1>
    
    <!-- Revenue Section -->
     <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 mb-8 text-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <h3 class="text-lg opacity-90 mb-2">Total Revenue</h3>
                <p class="text-4xl font-bold">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></p>
            </div>
            <div>
                <h3 class="text-lg opacity-90 mb-2">Today's Revenue</h3>
                <p class="text-4xl font-bold">Rp <?= number_format($todayRevenue, 0, ',', '.') ?></p>
            </div>
            <div>
                <h3 class="text-lg opacity-90 mb-2">This Week Revenue</h3>
                <p class="text-4xl font-bold">Rp <?= number_format($thisWeekRevenue, 0, ',', '.') ?></p>
            </div>
            <div>
                <h3 class="text-lg opacity-90 mb-2">Weekly Growth</h3>
                <p class="text-4xl font-bold">
                    <?php
                    if (count($weeklyRevenue) >= 2) {
                        $currentWeek = $weeklyRevenue[0]['weekly_total'];
                        $lastWeek = $weeklyRevenue[1]['weekly_total'];
                        $growth = $lastWeek != 0 ? (($currentWeek - $lastWeek) / $lastWeek) * 100 : 0;
                        echo round($growth, 1) . '%';
                        
                        // Add an icon to indicate growth direction
                        if ($growth > 0) {
                            echo ' <i class="fas fa-arrow-up"></i>';
                        } elseif ($growth < 0) {
                            echo ' <i class="fas fa-arrow-down"></i>';
                        }
                    } else {
                        echo "N/A";
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500">Total Customers</h3>
                <i class="fas fa-users text-2xl text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $totalCustomers ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500">Menu Items</h3>
                <i class="fas fa-utensils text-2xl text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $totalMenuItems ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500">Pending Orders</h3>
                <i class="fas fa-clock text-2xl text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $pendingOrders ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500">Today's Orders</h3>
                <i class="fas fa-shopping-bag text-2xl text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $todayOrders ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500">In Transit</h3>
                <i class="fas fa-truck text-2xl text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-800"><?= $inTransitOrders ?></p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Selling Items -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Top Selling Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($topItems as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($item['nama']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $item['total_orders'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $item['total_quantity'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Recent Orders</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentOrders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">#<?= $order['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['customer_email']) ?></td>
                            <td class="px-6 py-4">
                                <div class="truncate max-w-xs" title="<?= htmlspecialchars($order['menu_items']) ?>">
                                    <?= htmlspecialchars($order['menu_items']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch($order['status']) {
                                        case 'Menunggu Konfirmasi':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'Diterima':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'Diproses':
                                            echo 'bg-purple-100 text-purple-800';
                                            break;
                                        case 'Diperjalanan':
                                            echo 'bg-orange-100 text-orange-800';
                                            break;
                                        case 'Selesai':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'Dibatalkan':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        case 'Telah Sampai':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'Gagal':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        case 'Dibatalkan Olen Penjual':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <a href="manage_menu.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Manage Menu</h3>
                <i class="fas fa-book-open text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">Add, edit, or remove menu items</p>
        </a>
        
        <a href="manage_customers.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Manage Customers</h3>
                <i class="fas fa-user-friends text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">View and manage customer accounts</p>
        </a>
        
        <a href="manage_orders.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Manage Orders</h3>
                <i class="fas fa-clipboard-list text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">View and process customer orders</p>
        </a>
        
        <a href="kitchen_orders.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Kitchen Dashboard</h3>
                <i class="fas fa-utensils text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">View and manage orders in preparation</p>
        </a>
        
        <a href="driver_orders.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Driver Dashboard</h3>
                <i class="fas fa-truck text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">View and manage deliveries in transit</p>
        </a>
        
        <!-- Add this to the Quick Links section in dashboard.php -->
        <a href="manage_payment_methods.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Payment Methods</h3>
                <i class="fas fa-credit-card text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">Manage payment options for customers</p>
        </a>

        <a href="manage_qris.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Manage Qris</h3>
                <i class="fas fa-qrcode text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">Manage The Qris Payment</p>
        </a>
        
        <!-- Add Manage Admin link -->
        <a href="manage_admin.php" class="bg-white rounded-lg shadow-lg p-6 hover:bg-orange-50 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">Manage Admin</h3>
                <i class="fas fa-user-shield text-2xl text-orange-500"></i>
            </div>
            <p class="text-gray-600">Add, edit, or remove admin accounts</p>
        </a>
    </div>
</div>



<!-- Weekly Revenue Chart -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold mb-4 text-gray-800">Weekly Revenue</h2>
    <div class="h-64">
        <canvas id="weeklyRevenueChart"></canvas>
    </div>
</div>

<!-- Add this before the footer include -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weekly Revenue Chart
    const weeklyRevenueCtx = document.getElementById('weeklyRevenueChart').getContext('2d');
    
    const weeklyRevenueData = {
        labels: [
            <?php 
            $labels = [];
            foreach (array_reverse($weeklyRevenue) as $week) {
                // Format dates to show proper week ranges
                $week_start = date('d M', strtotime($week['week_start']));
                $week_end = date('d M', strtotime($week['week_end']));
                $labels[] = "'" . $week_start . " - " . $week_end . "'";
            }
            echo implode(', ', $labels);
            ?>
        ],
        datasets: [{
            label: 'Weekly Revenue',
            data: [
                <?php 
                $data = [];
                foreach (array_reverse($weeklyRevenue) as $week) {
                    $data[] = $week['weekly_total'];
                }
                echo implode(', ', $data);
                ?>
            ],
            backgroundColor: 'rgba(237, 137, 54, 0.2)',
            borderColor: 'rgba(237, 137, 54, 1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    };
    
    new Chart(weeklyRevenueCtx, {
        type: 'line',
        data: weeklyRevenueData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>