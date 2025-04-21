<?php
require_once '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('error', 'Invalid order ID');
    redirect('manage_orders.php');
}

$order_id = (int)$_GET['id'];

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
    setAlert('error', 'Order not found');
    redirect('manage_orders.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT pd.*, m.nama as menu_name
    FROM pesanan_detail pd
    JOIN menu m ON pd.id_menu = m.id
    WHERE pd.id_pesanan = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    setAlert('success', 'Order status updated successfully');
    redirect("order_details.php?id=$order_id");
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Order #<?= $order['id'] ?></h1>
        <a href="manage_orders.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Orders
        </a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Status -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-wrap justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold">Status</h2>
                        <p class="text-lg font-bold text-orange-600 mt-1"><?= htmlspecialchars($order['status']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-600">Order Date</p>
                        <p class="font-medium"><?= date('d M Y H:i', strtotime($order['waktu_pemesanan'])) ?></p>
                    </div>
                </div>
                
                <!-- Update Status Form -->
                <form method="POST" class="mt-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="w-full md:w-auto">
                            <select name="status" class="border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="Menunggu Konfirmasi" <?= $order['status'] === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                <option value="Diterima" <?= $order['status'] === 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                                <option value="Diproses" <?= $order['status'] === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="Diperjalanan" <?= $order['status'] === 'Diperjalanan' ? 'selected' : '' ?>>Diperjalanan</option>
                            </select>
                        </div>
                        <button type="submit" 
                                name="update_status" 
                                class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Customer Information</h2>
                <div class="space-y-2">
                    <p><span class="text-gray-600">Email:</span> <?= htmlspecialchars($order['email']) ?></p>
                    <p><span class="text-gray-600">Phone:</span> <?= htmlspecialchars($order['no_telp']) ?></p>
                    <p><span class="text-gray-600">Recipient:</span> <?= htmlspecialchars($order['nama_pemesan']) ?></p>
                    <p><span class="text-gray-600">Address:</span> <?= nl2br(htmlspecialchars($order['alamat_pemesan'])) ?></p>
                    
                    <!-- Display Map if coordinates are available -->
                    <?php if (!empty($order['latitude']) && !empty($order['longitude'])): ?>
                    <div class="mt-4">
                        <h4 class="font-semibold mb-2">Delivery Location</h4>
                        <div id="map" class="w-full h-64 rounded-md border mb-2"></div>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>" 
                           target="_blank" 
                           class="text-blue-600 hover:underline inline-flex items-center">
                            <i class="fas fa-directions mr-1"></i> Get Directions
                        </a>
                    </div>
                    
                    <!-- Leaflet Map -->
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const deliveryLocation = [<?= $order['latitude'] ?>, <?= $order['longitude'] ?>];
                        
                        const map = L.map('map').setView(deliveryLocation, 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                        }).addTo(map);
                        
                        // Add marker at delivery location
                        L.marker(deliveryLocation).addTo(map);
                    });
                    </script>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Payment Information</h2>
                <div class="space-y-4">
                    <p><span class="text-gray-600">Total Amount:</span> Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                    
                    <?php if ($order['bukti_pembayaran']): ?>
                    <div>
                        <p class="text-gray-600 mb-2">Payment Proof:</p>
                        <img src="../assets/images/uploads/<?= htmlspecialchars($order['bukti_pembayaran']) ?>" 
                             alt="Payment Proof" 
                             class="w-full rounded-md border">
                    </div>
                    <?php else: ?>
                    <p class="text-red-600">No payment proof uploaded</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Order Items</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-3 px-4 text-left">Item</th>
                                <th class="py-3 px-4 text-center">Price</th>
                                <th class="py-3 px-4 text-center">Quantity</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_subtotal = $item['harga_satuan'] * $item['jumlah'];
                                $subtotal += $item_subtotal;
                            ?>
                            <tr class="border-b">
                                <td class="py-4 px-4"><?= htmlspecialchars($item['menu_name']) ?></td>
                                <td class="py-4 px-4 text-center">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                <td class="py-4 px-4 text-center"><?= $item['jumlah'] ?></td>
                                <td class="py-4 px-4 text-right">Rp <?= number_format($item_subtotal, 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Calculate taxes -->
                            <?php
                            $tax = $order['total_harga'] - $subtotal;
                            ?>
                            
                            <tr class="bg-gray-50">
                                <td colspan="3" class="py-3 px-4 text-right">Subtotal:</td>
                                <td class="py-3 px-4 text-right">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td colspan="3" class="py-3 px-4 text-right">Tax & Fees:</td>
                                <td class="py-3 px-4 text-right">Rp <?= number_format($tax, 0, ',', '.') ?></td>
                            </tr>
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="3" class="py-3 px-4 text-right">Total:</td>
                                <td class="py-3 px-4 text-right">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>