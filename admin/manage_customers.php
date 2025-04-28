<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Handle customer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $customer_id = (int)$_POST['customer_id'];
    
    // Don't allow deleting if customer has active orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pesanan WHERE id_customer = ?");
    $stmt->execute([$customer_id]);
    $has_orders = $stmt->fetch()['count'] > 0;
    
    if ($has_orders) {
        setAlert('error', 'Cannot delete customer with active orders');
    } else {
        $stmt = $pdo->prepare("DELETE FROM akun WHERE id = ? AND admin_value = 0");
        if ($stmt->execute([$customer_id])) {
            setAlert('success', 'Customer deleted successfully');
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $customer_id = (int)$_POST['customer_id'];
    $new_password = clean($_POST['new_password']);
    
    if (strlen($new_password) < 6) {
        setAlert('error', 'Password must be at least 6 characters long');
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE akun SET password = ? WHERE id = ? AND admin_value = 0");
        if ($stmt->execute([$hashed_password, $customer_id])) {
            setAlert('success', 'Password reset successfully');
        } else {
            setAlert('error', 'Failed to reset password');
        }
    }
}

// Get customer details if viewing a specific customer
$customer_detail = null;
$customer_orders = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = (int)$_GET['id'];
    
    // Get customer details
    $stmt = $pdo->prepare("SELECT * FROM akun WHERE id = ? AND admin_value = 0");
    $stmt->execute([$customer_id]);
    $customer_detail = $stmt->fetch();
    
    if ($customer_detail) {
        // Get customer orders
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COUNT(pd.id) as total_items,
                   (SELECT GROUP_CONCAT(m.nama SEPARATOR ', ') 
                    FROM pesanan_detail pd2 
                    JOIN menu m ON pd2.id_menu = m.id 
                    WHERE pd2.id_pesanan = p.id) as menu_items
            FROM pesanan p
            LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
            WHERE p.id_customer = ?
            GROUP BY p.id
            ORDER BY p.waktu_pemesanan DESC
        ");
        $stmt->execute([$customer_id]);
        $customer_orders = $stmt->fetchAll();
    }
}

// Get all customers if not viewing a specific customer
if (!$customer_detail) {
    $stmt = $pdo->query("
        SELECT a.*, 
               COUNT(p.id) as order_count,
               SUM(p.total_harga) as total_spent,
               MAX(p.waktu_pemesanan) as last_order_date
        FROM akun a
        LEFT JOIN pesanan p ON a.id = p.id_customer
        WHERE a.admin_value = 0
        GROUP BY a.id
        ORDER BY a.email
    ");
    $customers = $stmt->fetchAll();
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?= isset($customer_detail) ? 'Customer Details' : 'Manage Customers' ?></h1>
        <a href="<?= isset($customer_detail) ? 'manage_customers.php' : 'dashboard.php' ?>" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke <?= isset($customer_detail) ? 'Customers List' : 'Dashboard' ?>
        </a>
    </div>
    
    <?php if (isset($customer_detail)): ?>
        <!-- Customer Detail View -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Customer Information -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Informasi Pelanggan</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-medium"><?= htmlspecialchars($customer_detail['email']) ?></p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Nomor Telepon</p>
                            <p class="font-medium"><?= htmlspecialchars($customer_detail['no_telp'] ?? 'Not provided') ?></p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Telah Registrasi Pada</p>
                            <p class="font-medium"><?= date('d M Y H:i', strtotime($customer_detail['created_at'])) ?></p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Total Pemesanan</p>
                            <p class="font-medium"><?= count($customer_orders) ?></p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Total Pembelanjaan</p>
                            <p class="font-medium">Rp <?= number_format(array_sum(array_column($customer_orders, 'total_harga')), 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Reset Password Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Reset Password</h2>
                    
                    <form action="manage_customers.php?id=<?= $customer_detail['id'] ?>" method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="customer_id" value="<?= $customer_detail['id'] ?>">
                        
                        <div class="mb-4">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                required
                                minlength="6"
                            >
                            <p class="text-xs text-gray-500 mt-1">Paling sedikit 6 karakter</p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition">
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order History -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Histori Pemesanan</h2>
                    
                    <?php if (count($customer_orders) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pemesanan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($customer_orders as $order): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">#<?= $order['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d M Y H:i', strtotime($order['waktu_pemesanan'])) ?></td>
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
                                                        case 'Telah Sampai':
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'Dibatalkan':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                        default:
                                                            echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="../admin/order_details.php?id=<?= $order['id'] ?>" class="text-orange-600 hover:text-orange-900">
                                                    Lihat Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Tidak ada pesanan untuk akun ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Customers List View -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 border-b">
                <input type="text" id="customerSearch" placeholder="Search customers..." class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full" id="customersTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tlp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pesanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pembelanjaan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pesanan Terakhir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $customer['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($customer['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($customer['no_telp'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $customer['order_count'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= $customer['total_spent'] ? 'Rp ' . number_format($customer['total_spent'], 0, ',', '.') : 'Rp 0' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= $customer['last_order_date'] ? date('d M Y', strtotime($customer['last_order_date'])) : 'Never' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                <a href="manage_customers.php?id=<?= $customer['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <button onclick="deleteCustomer(<?= $customer['id'] ?>, <?= $customer['order_count'] ?>)"
                                        class="text-red-600 hover:text-red-900 ml-2">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteCustomer(id, orderCount) {
    if (orderCount > 0) {
        alert('Cannot delete customer with active orders');
        return;
    }
    
    if (confirm('Are you sure you want to delete this customer?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="customer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Customer search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('customersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const email = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const phone = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                
                if (email.includes(searchTerm) || phone.includes(searchTerm)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>