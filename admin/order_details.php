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


// Get add-ons for each order item
$order_addons = [];
foreach ($order_items as $item) {
    $stmt = $pdo->prepare("
        SELECT * FROM pesanan_detail_add_ons
        WHERE id_pesanan_detail = ?
    ");
    $stmt->execute([$item['id']]);
    $order_addons[$item['id']] = $stmt->fetchAll();
}

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
    <!-- Add this near the top of the order details page, in the order header section -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Order #<?= $order['id'] ?></h1>
        <div class="flex space-x-4">
            <a href="chat.php?order_id=<?= $order['id'] ?>" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition flex items-center">
                <i class="fas fa-comment mr-2"></i> Chat with Customer
            </a>
            <a href="manage_orders.php" class="text-orange-600 hover:underline inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Orders
            </a>
        </div>
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
            </div>
        </div>

                <!-- Customer Feedback Section - Only show when feedback exists -->
                <?php
        // Check if feedback exists
        $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id_pesanan = ?");
        $stmt->execute([$order_id]);
        $feedback = $stmt->fetch();
        
        if ($feedback):
        ?>
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Customer Feedback</h2>
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="ml-2 text-gray-600"><?= date('d M Y', strtotime($feedback['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($feedback['komentar'])): ?>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($feedback['komentar'])) ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No comments provided</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Customer Information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Customer Information</h2>
                <div class="space-y-2">
                    <p><span class="text-gray-600">Email:</span> <?= htmlspecialchars($order['email']) ?></p>
                    <p><span class="text-gray-600">Phone:</span> <?= htmlspecialchars($order['no_telp'] ?? 'Not provided') ?></p>
                    <p><span class="text-gray-600">Recipient:</span> <?= htmlspecialchars($order['nama_pemesan']) ?></p>
                    <p><span class="text-gray-600">Address:</span> <?= nl2br(htmlspecialchars($order['alamat_pemesan'])) ?></p>
                    
                    <!-- Display notes if available -->
                    <?php if (!empty($order['notes'])): ?>
                    <div class="mt-3 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                        <p class="font-medium text-gray-700 mb-1">Special Instructions:</p>
                        <p class="text-gray-600"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
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
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Order Items</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-3 px-4 text-left">Item</th>
                                <th class="py-3 px-4 text-right">Price</th>
                                <th class="py-3 px-4 text-right">Quantity</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php 
                            $subtotal = 0;
                            $addon_total_all = 0;
                            foreach ($order_items as $item): 
                                $item_subtotal = $item['jumlah'] * $item['harga_satuan'];
                                $subtotal += $item_subtotal;
                            ?>
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?= htmlspecialchars($item['menu_name']) ?></div>
                                    
                                    <?php if (isset($order_addons[$item['id']]) && !empty($order_addons[$item['id']])): ?>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-600">Add-ons:</p>
                                        <ul class="pl-4 text-sm">
                                            <?php 
                                            $addon_total = 0;
                                            foreach ($order_addons[$item['id']] as $addon): 
                                                $addon_total += $addon['harga'] * $item['jumlah'];
                                                $addon_total_all += $addon['harga'] * $item['jumlah'];
                                            ?>
                                            <li class="text-gray-600">
                                                <?= htmlspecialchars($addon['nama']) ?> 
                                                (+Rp <?= number_format($addon['harga'], 0, ',', '.') ?>)
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                <td class="py-3 px-4 text-right"><?= $item['jumlah'] ?></td>
                                <td class="py-3 px-4 text-right">
                                    <div>Rp <?= number_format($item_subtotal, 0, ',', '.') ?></div>
                                    
                                    <?php if (isset($order_addons[$item['id']]) && !empty($order_addons[$item['id']])): ?>
                                    <div class="text-sm text-gray-600 mt-1">
                                        + Rp <?= number_format($addon_total, 0, ',', '.') ?> (add-ons)
                                    </div>
                                    <div class="font-medium mt-1 pt-1 border-t">
                                        Rp <?= number_format($item_subtotal + $addon_total, 0, ',', '.') ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-right font-medium">Subtotal</td>
                                <td class="py-3 px-4 text-right font-medium">Rp <?= number_format($subtotal + $addon_total_all, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-right font-medium">Tax</td>
                                <td class="py-3 px-4 text-right font-medium">Rp <?= number_format($order['total_harga'] - ($subtotal + $addon_total_all), 0, ',', '.') ?></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td colspan="3" class="py-3 px-4 text-right font-bold">Total</td>
                                <td class="py-3 px-4 text-right font-bold">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Update Status Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Update Status</h2>
                <form method="POST" class="mt-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="w-full md:w-auto">
                            <select name="status" class="border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="Menunggu Konfirmasi" <?= $order['status'] === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                <option value="Diterima" <?= $order['status'] === 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                                <option value="Diproses" <?= $order['status'] === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="Diperjalanan" <?= $order['status'] === 'Diperjalanan' ? 'selected' : '' ?>>Diperjalanan</option>
                                <option value="Telah Sampai" <?= $order['status'] === 'Telah Sampai' ? 'selected' : '' ?>>Telah Sampai</option>
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
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


<?php
// Add this function near the top of the file after the order data is fetched

// Check if a chat conversation exists for this order
$stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id_pesanan = ?");
$stmt->execute([$order_id]);
$conversation = $stmt->fetch();

// If no conversation exists and admin wants to start one
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_conversation'])) {
    // Create new conversation
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (id_pesanan) VALUES (?)");
    $stmt->execute([$order_id]);
    $conversation_id = $pdo->lastInsertId();
    
    // Add initial message
    $message = trim($_POST['initial_message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (id_conversation, sender_id, is_admin, message)
            VALUES (?, ?, 1, ?)
        ");
        $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    }
    
    // Redirect to chat page
    redirect("chat.php?conversation_id=$conversation_id");
}
?>

<!-- Add this modal at the bottom of the file, before the closing body tag -->
<div id="chatModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Start Conversation</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Initial Message</label>
                <textarea name="initial_message" rows="4" class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Type your message to the customer..."></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-md mr-2">Cancel</button>
                <button type="submit" name="start_conversation" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('chatModal').classList.remove('hidden');
    document.getElementById('chatModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('chatModal').classList.add('hidden');
    document.getElementById('chatModal').classList.remove('flex');
}

// Update the chat button to either open modal or go to existing chat
document.addEventListener('DOMContentLoaded', function() {
    const chatButton = document.querySelector('[href^="chat.php?order_id="]');
    <?php if ($conversation): ?>
    // If conversation exists, keep the link as is
    chatButton.href = "chat.php?conversation_id=<?= $conversation['id'] ?>";
    <?php else: ?>
    // If no conversation, open modal instead
    chatButton.href = "javascript:void(0)";
    chatButton.addEventListener('click', function(e) {
        e.preventDefault();
        openModal();
    });
    <?php endif; ?>
});
</script>