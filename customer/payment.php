<?php
require_once '../includes/header.php';
require_once '../includes/functions.php'; // Add this line to include functions.php

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
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Store form data in session for persistence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_order'])) {
    $_SESSION['preview_order'] = true;
    $_SESSION['nama_pemesan'] = $nama_pemesan;
    $_SESSION['alamat_pemesan'] = $alamat_pemesan;
    $_SESSION['latitude'] = $latitude;
    $_SESSION['longitude'] = $longitude;
    $_SESSION['notes'] = $notes;
    
    // Store selected add-ons if they exist
    if (isset($_POST['addons'])) {
        $_SESSION['selected_addons'] = $_POST['addons'];
    }
} else if (isset($_SESSION['preview_order'])) {
    // Retrieve from session if available
    $nama_pemesan = $_SESSION['nama_pemesan'] ?? '';
    $alamat_pemesan = $_SESSION['alamat_pemesan'] ?? '';
    $latitude = $_SESSION['latitude'] ?? null;
    $longitude = $_SESSION['longitude'] ?? null;
    $notes = $_SESSION['notes'] ?? '';
}

// Get cart items for preview
$stmt = $pdo->prepare("
    SELECT k.*, m.nama, m.harga, m.stok, m.gambar 
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

// Get add-ons for each cart item
$cart_addons = [];
foreach ($cart_items as $item) {
    $stmt = $pdo->prepare("
        SELECT ma.* 
        FROM keranjang_add_ons ka
        JOIN menu_add_ons ma ON ka.id_add_on = ma.id
        WHERE ka.id_keranjang = ?
    ");
    $stmt->execute([$item['id']]);
    $cart_addons[$item['id']] = $stmt->fetchAll();
}

// Calculate total
$subtotal = 0;
$items_with_addons = [];

foreach ($cart_items as $item) {
    $item_total = $item['harga'] * $item['jumlah'];
    $item_addons = $cart_addons[$item['id']] ?? [];
    
    // Add add-ons price
    foreach ($item_addons as $addon) {
        $item_total += $addon['harga'] * $item['jumlah'];
    }
    
    $subtotal += $item_total;
    
    // Store item with its add-ons for display
    $items_with_addons[] = [
        'item' => $item,
        'addons' => $item_addons,
        'subtotal' => $item_total
    ];
}

// Add taxes
$qris_tax = 500;
$app_tax = 300;
$tax_total = $qris_tax + $app_tax;
$grand_total = $subtotal + $tax_total;

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
    try {
        $pdo->beginTransaction();

        // Check file size (20MB limit)
        if ($_FILES['payment_proof']['size'] > 20 * 1024 * 1024) {
            throw new Exception('File size exceeds the 20MB limit');
        }

        // Upload payment proof
        $upload = uploadImage($_FILES['payment_proof'], '../assets/images/uploads/');
        if (!$upload['success']) {
            throw new Exception($upload['message']);
        }
        
        // Store the payment proof filename
        $payment_proof_filename = $upload['filename'];

        // Get form data
        $nama_pemesan = $_POST['nama_pemesan'];
        $alamat_pemesan = $_POST['alamat_pemesan'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $total_harga = $grand_total;

        // Create new order
        $stmt = $pdo->prepare("
            INSERT INTO pesanan (
                id_customer, nama_pemesan, alamat_pemesan, 
                latitude, longitude, total_harga, 
                bukti_pembayaran, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Konfirmasi')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $nama_pemesan,
            $alamat_pemesan,
            $latitude,
            $longitude,
            $total_harga,
            $payment_proof_filename,
            $notes
        ]);

        $order_id = $pdo->lastInsertId();

        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO pesanan_detail (
                    id_pesanan, id_menu, jumlah, harga_satuan
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $item['id_menu'],
                $item['jumlah'],
                $item['harga']
            ]);
            
            $order_detail_id = $pdo->lastInsertId();
            
            // Add order item add-ons if any
            if (isset($cart_addons[$item['id']]) && !empty($cart_addons[$item['id']])) {
                foreach ($cart_addons[$item['id']] as $addon) {
                    $stmt = $pdo->prepare("
                        INSERT INTO pesanan_detail_add_ons (
                            id_pesanan_detail, nama, harga
                        ) VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_detail_id,
                        $addon['nama'],
                        $addon['harga']
                    ]);
                }
            }
            
            // Update stock
            $stmt = $pdo->prepare("
                UPDATE menu 
                SET stok = stok - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['jumlah'], $item['id_menu']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM keranjang_add_ons WHERE id_keranjang IN (SELECT id FROM keranjang WHERE id_customer = ?)");
        $stmt->execute([$_SESSION['user_id']]);
        
        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id_customer = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Clear session data
        unset($_SESSION['preview_order']);
        unset($_SESSION['nama_pemesan']);
        unset($_SESSION['alamat_pemesan']);
        unset($_SESSION['latitude']);
        unset($_SESSION['longitude']);
        unset($_SESSION['notes']);
        unset($_SESSION['selected_addons']);

        $pdo->commit();

        setAlert('success', 'Order placed successfully! Your order is being processed.');
        redirect('../customer/orders.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        setAlert('error', 'Error: ' . $e->getMessage());
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Review Order</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Delivery Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-gray-600 text-sm">Name:</p>
                        <p class="font-medium"><?= htmlspecialchars($nama_pemesan) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Address:</p>
                        <p class="font-medium"><?= htmlspecialchars($alamat_pemesan) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($notes)): ?>
                <div class="mb-4">
                    <p class="text-gray-600 text-sm">Special Instructions:</p>
                    <p class="font-medium"><?= htmlspecialchars($notes) ?></p>
                </div>
                <?php endif; ?>
                
                <div id="map" class="w-full h-64 rounded-md border"></div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <h2 class="text-xl font-semibold p-6 pb-3">Order Items</h2>
                
                <div class="px-6">
                    <?php foreach ($items_with_addons as $index => $order_item): ?>
                    <div class="border-b py-4 <?= $index === 0 ? 'pt-0' : '' ?>">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-4">
                                <img src="../assets/images/menu/<?= htmlspecialchars($order_item['item']['gambar'] ?? 'default.jpg') ?>" 
                                     alt="<?= htmlspecialchars($order_item['item']['nama']) ?>" 
                                     class="w-16 h-16 object-cover rounded-md">
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between">
                                    <h3 class="font-medium"><?= htmlspecialchars($order_item['item']['nama']) ?></h3>
                                    <p class="font-medium">Rp <?= number_format($order_item['item']['harga'], 0, ',', '.') ?></p>
                                </div>
                                <p class="text-gray-600">Quantity: <?= $order_item['item']['jumlah'] ?></p>
                                
                                <?php if (!empty($order_item['addons'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">Add-ons:</p>
                                    <ul class="pl-4 text-sm">
                                        <?php foreach ($order_item['addons'] as $addon): ?>
                                        <li class="flex justify-between">
                                            <span><?= htmlspecialchars($addon['nama']) ?></span>
                                            <span>+Rp <?= number_format($addon['harga'], 0, ',', '.') ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-2 text-right">
                                    <p class="font-medium">Subtotal: Rp <?= number_format($order_item['subtotal'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>QRIS Tax</span>
                        <span>Rp <?= number_format($qris_tax, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>App Tax</span>
                        <span>Rp <?= number_format($app_tax, 0, ',', '.') ?></span>
                    </div>
                    <div class="border-t pt-2 mt-2 flex justify-between font-semibold">
                        <span>Total</span>
                        <span>Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Payment</h2>
                
                <!-- Pre-order Notice -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Pre-Order Information</h3>
                            <div class="mt-1 text-sm text-blue-700">
                                <p>Your order will be processed within 1-3 days after payment confirmation.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <!-- Add QRIS container and image elements -->
                        <div class="p-4 bg-gray-100 rounded-md mb-4">
                            <p class="font-medium mb-2">QRIS Payment</p>
                            <div id="qris-container" class="flex justify-center mb-2">
                                <div class="text-center">
                                    <div class="inline-block p-2 bg-white rounded-lg">
                                        <img id="qris-image" src="../assets/images/loading.gif" alt="QRIS Code" class="w-full max-w-xs mx-auto">
                                    </div>
                                    <p class="text-sm text-gray-600 mt-2">Total: Rp <?= number_format($grand_total, 0, ',', '.') ?></p>
                                    <p class="text-sm text-gray-600">Scan the QR code to pay</p>
                                </div>
                            </div>
                        </div>
                        
                        <form action="payment.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="nama_pemesan" value="<?= htmlspecialchars($nama_pemesan) ?>">
                            <input type="hidden" name="alamat_pemesan" value="<?= htmlspecialchars($alamat_pemesan) ?>">
                            <input type="hidden" name="latitude" value="<?= htmlspecialchars($latitude) ?>">
                            <input type="hidden" name="longitude" value="<?= htmlspecialchars($longitude) ?>">
                            <input type="hidden" name="notes" value="<?= htmlspecialchars($notes) ?>">
                            
                            <div class="mb-4">
                                <label for="payment_proof" class="block text-sm font-medium text-gray-700 mb-1">Upload Payment Proof</label>
                                <input type="file" 
                                       id="payment_proof" 
                                       name="payment_proof"
                                       accept="image/*"
                                       class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Upload a screenshot of your payment confirmation</p>
                            </div>
                            
                            <div class="flex space-x-4">
                                <a href="cart.php" class="flex-1 py-2 px-4 border border-gray-300 rounded-md text-center hover:bg-gray-50 transition">
                                    Back to Cart
                                </a>
                                <button type="submit" class="flex-1 bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition">
                                    Complete Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS and JS (Open Source Maps) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- QR Code Generator Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map with the selected location
    const latitude = <?= $latitude ?? 'null' ?>;
    const longitude = <?= $longitude ?? 'null' ?>;
    
    if (latitude && longitude) {
        const map = L.map('map').setView([latitude, longitude], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add marker at the delivery location
        L.marker([latitude, longitude]).addTo(map)
            .bindPopup('Delivery Location')
            .openPopup();
    } // Added missing closing bracket here
    
    // Generate dynamic QRIS code
    const grandTotal = <?= $grand_total ?>;
    const qrisContainer = document.getElementById('qris-container');
    const qrisImage = document.getElementById('qris-image');
    
    if (qrisContainer && qrisImage) {
        // Fetch dynamic QRIS from server
        fetch('../includes/generate_dynamic_qris.php?amount=' + grandTotal)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Generate QR code directly with QRCode.js
                    try {
                        const qr = qrcode(0, 'M');
                        qr.addData(data.qris_code);
                        qr.make();
                        
                        // Create image
                        qrisImage.src = qr.createDataURL(10, 0);
                    } catch (error) {
                        console.error('Error generating QR code:', error);
                        // Fallback to QR Server API
                        qrisImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.qris_code);
                    }
                } else {
                    qrisContainer.innerHTML = `
                        <div class="text-center p-4">
                            <p class="text-red-500">${data.message || 'Failed to generate QRIS code'}</p>
                            <p class="mt-2">Please contact admin or try another payment method.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error generating QRIS:', error);
                qrisContainer.innerHTML = `
                    <div class="text-center p-4">
                        <p class="text-red-500">Failed to generate QRIS code</p>
                        <p class="mt-2">Please contact admin or try another payment method.</p>
                    </div>
                `;
            });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>