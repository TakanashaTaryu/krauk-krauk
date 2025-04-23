<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Search parameters
$search = $_GET['search'] ?? '';
$searchBy = $_GET['searchBy'] ?? 'email';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Handle order status updates and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $order_id = (int)$_POST['order_id'];
                $status = clean($_POST['status']);
                
                $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $order_id])) {
                    setAlert('success', 'Order status updated successfully');
                }
                break;
                
            case 'delete':
                $order_id = (int)$_POST['order_id'];
                
                $stmt = $pdo->prepare("DELETE FROM pesanan_detail WHERE id_pesanan = ?");
                $stmt->execute([$order_id]);
                
                $stmt = $pdo->prepare("DELETE FROM pesanan WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    setAlert('success', 'Order deleted successfully');
                }
                break;
        }
    }
}

// Build query with search conditions
$query = "SELECT p.*, a.email as customer_email, 
          GROUP_CONCAT(m.nama SEPARATOR ', ') as menu_items
          FROM pesanan p 
          JOIN akun a ON p.id_customer = a.id 
          LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
          LEFT JOIN menu m ON pd.id_menu = m.id
          WHERE 1=1";

$params = [];

if ($search !== '') {
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

if ($status_filter !== '') {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}

if ($date_from !== '') {
    $query .= " AND DATE(p.waktu_pemesanan) >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $query .= " AND DATE(p.waktu_pemesanan) <= ?";
    $params[] = $date_to;
}

$query .= " GROUP BY p.id ORDER BY p.waktu_pemesanan DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$status_options = ['Menunggu Konfirmasi', 'Diterima', 'Diproses', 'Diperjalanan', 'Telah Sampai', 'Dibatalkan Olen Penjual', 'Gagal'];
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Manage Orders</h1>
        <div class="flex space-x-2">
            <button onclick="exportToExcel()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                <i class="fas fa-file-excel mr-2"></i>Export to Excel
            </button>
            <button onclick="printOrders()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="w-full border rounded-md px-3 py-2" 
                       placeholder="Search...">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search By</label>
                <select name="searchBy" class="w-full border rounded-md px-3 py-2">
                    <option value="email" <?= $searchBy === 'email' ? 'selected' : '' ?>>Customer Email</option>
                    <option value="amount" <?= $searchBy === 'amount' ? 'selected' : '' ?>>Amount</option>
                    <option value="id" <?= $searchBy === 'id' ? 'selected' : '' ?>>Order ID</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border rounded-md px-3 py-2">
                    <option value="">All Status</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                       class="w-full border rounded-md px-3 py-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                       class="w-full border rounded-md px-3 py-2">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <a href="manage_orders.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Orders Summary -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-800">Total Orders</h3>
                <p class="text-2xl font-bold text-blue-600"><?= count($orders) ?></p>
            </div>
            <?php
            $stmt = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE status = 'Telah Sampai'");
            $totalRevenue = $stmt->fetch()['total'] ?? 0;
            //$total_revenue = array_sum(array_column($orders, 'total_harga'));
            $pending_orders = count(array_filter($orders, fn($o) => $o['status'] === 'Menunggu Konfirmasi'));
            $processing_orders = count(array_filter($orders, fn($o) => $o['status'] === 'Diproses'));
            ?>
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-green-800">Total Revenue</h3>
                <p class="text-2xl font-bold text-green-600">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-yellow-800">Pending Orders</h3>
                <p class="text-2xl font-bold text-yellow-600"><?= $pending_orders ?></p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-purple-800">Processing Orders</h3>
                <p class="text-2xl font-bold text-purple-600"><?= $processing_orders ?></p>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Update Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
    <td class="py-3 px-4">
        <div class="flex flex-col">
            <span class="font-medium">#<?= $order['id'] ?></span>
            <span class="text-sm text-gray-500"><?= $order['customer_email'] ?></span>
            <span class="text-sm text-gray-500"><?= htmlspecialchars($order['nama_pemesan']) ?></span>
        </div>
    </td>
                        <td class="px-6 py-4">
                            <div class="truncate max-w-xs" title="<?= htmlspecialchars($order['menu_items']) ?>">
                                <?= htmlspecialchars($order['menu_items']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-sm inline-block
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
                                <?= $order['status'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select onchange="updateStatus(<?= $order['id'] ?>, this.value)"
                                    class="border rounded px-2 py-1">
                                <?php foreach ($status_options as $status): ?>
                                    <option value="<?= $status ?>" 
                                            <?= $order['status'] === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= date('Y-m-d H:i:s', strtotime($order['waktu_pemesanan'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <button onclick="viewDetails(<?= $order['id'] ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="deleteOrder(<?= $order['id'] ?>)"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg max-w-2xl mx-auto mt-20 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Order Details</h2>
            <button onclick="hideDetailsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="orderDetails" class="mb-4">
            <div class="animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add modal structure -->
<div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div id="orderDetailsContent" class="relative max-w-4xl mx-auto mt-10">
        <!-- Content will be loaded here -->
    </div>
</div>

<script>
function updateStatus(orderId, status) {
    Swal.fire({
        title: 'Update Order Status',
        text: `Are you sure you want to update this order to "${status}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="${orderId}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function deleteOrder(orderId) {
    Swal.fire({
        title: 'Delete Order',
        text: "This action cannot be undone. Are you sure?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="order_id" value="${orderId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function showOrderDetails(orderId) {
    // Redirect to the order details page
    window.location.href = `order_details.php?id=${orderId}`;
}

function viewDetails(orderId) {
    // Redirect to the order details page instead of showing a modal
    window.location.href = `order_details.php?id=${orderId}`;
}

// Remove or comment out the old viewDetails function that was using AJAX
/*
function viewDetails(orderId) {
    const modal = document.getElementById('detailsModal');
    const detailsContainer = document.getElementById('orderDetails');
    
    modal.classList.remove('hidden');
    detailsContainer.innerHTML = '<div class="animate-pulse"><div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div><div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div><div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div></div>';
    
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.data.order;
                const items = data.data.items;
                
                let html = `
                    <div class="mb-4">
                        <h3 class="font-semibold">Customer Information</h3>
                        <p>Email: ${order.customer.email}</p>
                        <p>Phone: ${order.customer.phone}</p>
                        <p>Name: ${order.customer.name}</p>
                        <p>Address: ${order.customer.address}</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="font-semibold">Order Information</h3>
                        <p>Status: ${order.status}</p>
                        <p>Total: Rp ${new Intl.NumberFormat('id-ID').format(order.total)}</p>
                        <p>Date: ${new Date(order.date).toLocaleString()}</p>
                    </div>
                    <div>
                        <h3 class="font-semibold">Items</h3>
                        <ul class="list-disc pl-5">
                            ${items.map(item => `<li>${item.name} (${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)} = Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)})</li>`).join('')}
                        </ul>
                    </div>
                `;
                
                detailsContainer.innerHTML = html;
            } else {
                detailsContainer.innerHTML = `<p class="text-red-500">${data.message}</p>`;
            }
        })
        .catch(error => {
            detailsContainer.innerHTML = `<p class="text-red-500">Error loading order details</p>`;
            console.error(error);
        });
}
*/

function hideDetailsModal() {
    const modal = document.getElementById('detailsModal');
    modal.classList.add('hidden');
}

function exportToExcel() {
    window.location.href = 'export_orders.php' + window.location.search;
}

function printOrders() {
    window.print();
}

// Close modal when clicking outside
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDetailsModal();
    }
});

function closeOrderModal() {
    const modal = document.getElementById('orderDetailsModal');
    modal.classList.add('hidden');
}

// Add event listener for clicking outside modal
document.addEventListener('click', function(event) {
    const modal = document.getElementById('orderDetailsModal');
    const modalContent = document.getElementById('orderDetailsContent');
    
    if (event.target === modal) {
        closeOrderModal();
    }
});

// Prevent modal close when clicking inside modal content
document.querySelector('#detailsModal > div').addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>



<?php require_once '../includes/footer.php'; ?>