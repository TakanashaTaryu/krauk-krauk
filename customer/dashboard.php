<?php
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Get customer's recent orders
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(pd.id) as total_items
    FROM pesanan p
    LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
    WHERE p.id_customer = ?
    GROUP BY p.id
    ORDER BY p.waktu_pemesanan DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get customer's cart items count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM keranjang 
    WHERE id_customer = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetch()['count'];

// Get unread messages count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM chat_messages cm
    JOIN chat_conversations cc ON cm.id_conversation = cc.id
    JOIN pesanan p ON cc.id_pesanan = p.id
    WHERE p.id_customer = ? AND cm.is_admin = 1 AND cm.is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$unread_messages = $stmt->fetch()['count'] ?? 0;
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Welcome Back!</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-4">Your Cart</h3>
                <p class="text-3xl font-bold text-orange-600"><?= $cart_count ?> items</p>
                <a href="cart.php" class="inline-block mt-4 text-orange-600 hover:underline">View Cart →</a>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="menu.php" class="block text-orange-600 hover:underline">Browse Menu</a>
                    <a href="orders.php" class="block text-orange-600 hover:underline">View All Orders</a>
                    <a href="profile.php" class="block text-orange-600 hover:underline">Update Profile</a>
                    <a href="chat.php" class="flex items-center text-orange-600 hover:underline">
                        Messages
                        <?php if ($unread_messages > 0): ?>
                        <span class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?= $unread_messages ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">Recent Orders</h2>
            
            <?php if (empty($recent_orders)): ?>
            <p class="text-gray-500">You haven't placed any orders yet.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recent_orders as $order): ?>
                <div class="border-b pb-4 last:border-b-0 last:pb-0">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-bold">Order #<?= $order['id'] ?></p>
                            <p class="text-sm text-gray-500">
                                <?= date('F j, Y g:i A', strtotime($order['waktu_pemesanan'])) ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm 
                            <?= $order['status'] === 'menunggu konfirmasi' ? 'bg-yellow-100 text-yellow-800' : 
                                ($order['status'] === 'diterima' ? 'bg-green-100 text-green-800' : 
                                ($order['status'] === 'diproses' ? 'bg-blue-100 text-blue-800' : 
                                'bg-purple-100 text-purple-800')) ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-gray-600"><?= $order['total_items'] ?> items</p>
                        <p class="font-bold">Rp<?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <a href="orders.php?id=<?= $order['id'] ?>" class="text-sm text-orange-600 hover:underline">
                            View Details →
                        </a>
                        <a href="chat.php?order_id=<?= $order['id'] ?>" class="text-sm text-orange-600 hover:underline flex items-center">
                            <i class="fas fa-comment mr-1"></i> Chat with Admin
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <a href="orders.php" class="text-orange-600 hover:underline">View All Orders →</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>