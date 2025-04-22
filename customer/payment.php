<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    setAlert('error', 'Please login first');
    redirect('../auth/login.php');
}

if (isAdmin()) {
    setAlert('error', 'Access denied');
    redirect('../admin/dashboard.php');
}

// Check if this is a direct access or coming from cart
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_SESSION['preview_order'])) {
    setAlert('error', 'Please review your cart first');
    redirect('../customer/cart.php');
}

// Get customer information
$nama_pemesan = isset($_POST['nama_pemesan']) ? $_POST['nama_pemesan'] : '';
$alamat_pemesan = isset($_POST['alamat_pemesan']) ? $_POST['alamat_pemesan'] : '';
$latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

// Store form data in session for persistence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_order'])) {
    $_SESSION['preview_order'] = true;
    $_SESSION['nama_pemesan'] = $nama_pemesan;
    $_SESSION['alamat_pemesan'] = $alamat_pemesan;
    $_SESSION['latitude'] = $latitude;
    $_SESSION['longitude'] = $longitude;
    $_SESSION['notes'] = $_POST['notes'] ?? null;
} else if (isset($_SESSION['preview_order'])) {
    // Retrieve from session if available
    $nama_pemesan = $_SESSION['nama_pemesan'] ?? '';
    $alamat_pemesan = $_SESSION['alamat_pemesan'] ?? '';
    $latitude = $_SESSION['latitude'] ?? null;
    $longitude = $_SESSION['longitude'] ?? null;
    $notes = $_SESSION['notes'] ?? null;
}

// Get cart items for preview
$stmt = $pdo->prepare("
    SELECT k.*, m.nama, m.harga, m.stok 
    FROM keranjang k
    JOIN menu m ON k.id_menu = m.id
    WHERE k.id_customer = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    setAlert('error', 'Your cart is empty');
    redirect('../customer/cart.php');
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}

// Add taxes
$qris_tax = 500; // Rp. 500,00
$app_tax = 300;  // Rp. 300,00
$tax_total = $qris_tax + $app_tax;
$grand_total = $total + $tax_total;

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
    try {
        $pdo->beginTransaction();

        // Check file size (20MB limit)
        if ($_FILES['payment_proof']['size'] > 20 * 1024 * 1024) {
            throw new Exception('File size exceeds the 20MB limit');
        }

        // Upload payment proof with compression
        $upload = uploadImage($_FILES['payment_proof'], '../assets/images/uploads/', true);
        if (!$upload['success']) {
            throw new Exception($upload['message']);
        }
        
        // Store the payment proof filename
        $payment_proof_filename = $upload['filename'];

        // Get form data
        $nama_pemesan = $_POST['nama_pemesan'];
        $alamat_pemesan = $_POST['alamat_pemesan'];
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        
        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO pesanan (id_customer, nama_pemesan, alamat_pemesan, notes, latitude, longitude, total_harga, bukti_pembayaran, status, waktu_pemesanan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Konfirmasi', NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $nama_pemesan, $alamat_pemesan, $notes, $latitude, $longitude, $grand_total, $payment_proof_filename]);
        $order_id = $pdo->lastInsertId();

        // Create order details
        $stmt = $pdo->prepare("
            INSERT INTO pesanan_detail (
                id_pesanan, id_menu, jumlah, harga_satuan
            ) VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $stmt->execute([
                $order_id,
                $item['id_menu'],
                $item['jumlah'],
                $item['harga']
            ]);

            // Update stock
            $new_stock = $item['stok'] - $item['jumlah'];
            $stmt2 = $pdo->prepare("UPDATE menu SET stok = ? WHERE id = ?");
            $stmt2->execute([$new_stock, $item['id_menu']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id_customer = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Clear session data
        unset($_SESSION['preview_order']);
        unset($_SESSION['nama_pemesan']);
        unset($_SESSION['alamat_pemesan']);
        unset($_SESSION['latitude']);
        unset($_SESSION['longitude']);

        $pdo->commit();
        
        setAlert('success', 'Order placed successfully!');
        redirect('../customer/orders.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        setAlert('error', 'Failed to process order: ' . $e->getMessage());
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Review Order & Payment</h1>
    
    <div class="max-w-4xl mx-auto">
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Order Preview -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($item['nama']) ?></p>
                                <p class="text-sm text-gray-500"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                            </div>
                            <p class="font-medium">Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?></p>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <p>Subtotal</p>
                                <p>Rp <?= number_format($total, 0, ',', '.') ?></p>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600">
                                <p>QRIS Tax</p>
                                <p>Rp <?= number_format($qris_tax, 0, ',', '.') ?></p>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-600">
                                <p>App Tax</p>
                                <p>Rp <?= number_format($app_tax, 0, ',', '.') ?></p>
                            </div>
                            <div class="flex justify-between items-center font-bold pt-2">
                                <p>Total</p>
                                <p>Rp <?= number_format($grand_total, 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Delivery Information</h2>
                    <input type="hidden" name="nama_pemesan" value="<?= htmlspecialchars($nama_pemesan) ?>">
                    <input type="hidden" name="alamat_pemesan" value="<?= htmlspecialchars($alamat_pemesan) ?>">
                    <input type="hidden" name="latitude" value="<?= htmlspecialchars($latitude) ?>">
                    <input type="hidden" name="longitude" value="<?= htmlspecialchars($longitude) ?>">
                    
                    <!-- Add notes field (hidden) -->
                    <?php if (isset($_POST['notes'])): ?>
                    <input type="hidden" name="notes" value="<?= htmlspecialchars($_POST['notes']) ?>">
                    <?php endif; ?>
                    
                    <div class="space-y-2">
                        <p><span class="text-gray-600">Nama:</span> <?= htmlspecialchars($nama_pemesan) ?></p>
                        <p><span class="text-gray-600">Alamat:</span> <?= nl2br(htmlspecialchars($alamat_pemesan)) ?></p>
                        
                        <!-- Display notes if available -->
                        <?php if (isset($_POST['notes']) && !empty($_POST['notes'])): ?>
                        <p><span class="text-gray-600">Special Instructions:</span> <?= nl2br(htmlspecialchars($_POST['notes'])) ?></p>
                        <?php endif; ?>
                        
                        <!-- Display Map -->
                        <?php if ($latitude && $longitude): ?>
                        <div class="mt-3">
                            <p class="text-gray-600 mb-2">Lokasi Pengiriman:</p>
                            <div id="map" class="w-full h-48 rounded-md border"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Payment</h2>
                
                <!-- QRIS Code -->
                <div class="mb-6">
                    <p class="font-medium mb-4">Scan QRIS code below to pay Rp <?= number_format($grand_total, 0, ',', '.') ?>:</p>
                    <div class="bg-gray-100 p-4 rounded-lg flex justify-center">
                        <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-500">QRIS Code</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Proof Upload -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Payment Proof
                        </label>
                        <input type="file" 
                               name="payment_proof" 
                               accept="image/*"
                               required
                               class="w-full border rounded-md px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 8MB. Image will be compressed.</p>
                    </div>
                    
                    <button type="submit"
                            class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition">
                        Confirm Payment & Place Order
                    </button>
                    
                    <a href="../customer/cart.php" class="block text-center text-gray-600 hover:underline">
                        Back to Cart
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Leaflet CSS and JS for Payment Page -->
<?php if ($latitude && $longitude): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deliveryLocation = [<?= $latitude ?>, <?= $longitude ?>];
    
    const map = L.map('map').setView(deliveryLocation, 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add marker at delivery location
    L.marker(deliveryLocation).addTo(map);
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>