<?php
include_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Get order ID from URL
if (!isset($_GET['id'])) {
    setAlert('error', 'Order ID is required');
    redirect('orders.php');
}

$order_id = (int)$_GET['id'];

// Get order info
$stmt = $pdo->prepare("
    SELECT p.*, a.email 
    FROM pesanan p
    JOIN akun a ON p.id_customer = a.id
    WHERE p.id = ? AND p.id_customer = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setAlert('error', 'Order not found');
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT pd.*, m.nama as menu_name, m.harga as menu_price
    FROM pesanan_detail pd
    JOIN menu m ON pd.id_menu = m.id
    WHERE pd.id_pesanan = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Check if a chat conversation exists for this order
$stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id_pesanan = ?");
$stmt->execute([$order_id]);
$conversation = $stmt->fetch();

// If no conversation exists and customer wants to start one
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
            VALUES (?, ?, 0, ?)
        ");
        $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    }
    
    // Redirect to chat page
    redirect("chat.php?conversation_id=$conversation_id");
}

$page_title = "Order Details #" . $order_id;
?>

<div class="container mx-auto px-4 py-8">
    <!-- Order Detail View -->
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Order #<?= $order['id'] ?></h1>
            <div class="flex space-x-4">
                <?php if ($conversation): ?>
                    <a href="chat.php?conversation_id=<?= $conversation['id'] ?>" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition flex items-center">
                        <i class="fas fa-comment mr-2"></i> Continue Chat
                    </a>
                <?php else: ?>
                    <button onclick="openChatModal()" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition flex items-center">
                        <i class="fas fa-comment mr-2"></i> Start Chat
                    </button>
                <?php endif; ?>
                <a href="orders.php" class="text-orange-600 hover:underline inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                </a>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Order Status -->
            <div class="md:col-span-3">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex flex-wrap justify-between items-center mb-4">
                        <div>
                            <h2 class="text-xl font-semibold">Status</h2>
                            <p class="text-lg font-bold text-orange-600 mt-1"><?= htmlspecialchars($order['status']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">Order Date</p>
                            <p class="font-medium"><?= date('d M Y H:i', strtotime($order['waktu_pemesanan'])) ?></p>
                        </div>
                    </div>
                    
                    <!-- Order Progress Tracker -->
                    <div class="relative pt-8">
                        <?php
                        $statuses = ['Menunggu Konfirmasi', 'Diterima', 'Diproses', 'Diperjalanan', 'Telah Sampai'];
                        $currentStatusIndex = array_search($order['status'], $statuses);
                        ?>
                        <div class="flex justify-between mb-2">
                            <?php foreach ($statuses as $index => $status): ?>
                            <div class="text-center flex-1">
                                <div class="<?= $index <= $currentStatusIndex ? 'text-orange-600' : 'text-gray-400' ?> text-xs md:text-sm">
                                    <?= $status ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 flex rounded bg-gray-200">
                            <?php
                            $progressWidth = ($currentStatusIndex + 1) * 20;
                            ?>
                            <div style="width: <?= $progressWidth ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-orange-600"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rest of the order details code... -->
            <!-- (Include the same order details content as in orders.php when viewing a specific order) -->
        </div>
    </div>
</div>

<!-- Chat Modal -->
<div id="chatModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Start Conversation</h3>
            <button onclick="closeChatModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Initial Message</label>
                <textarea name="initial_message" rows="4" class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Type your message to customer service..."></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeChatModal()" class="px-4 py-2 border rounded-md mr-2">Cancel</button>
                <button type="submit" name="start_conversation" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
function openChatModal() {
    document.getElementById('chatModal').classList.remove('hidden');
    document.getElementById('chatModal').classList.add('flex');
}

function closeChatModal() {
    document.getElementById('chatModal').classList.add('hidden');
    document.getElementById('chatModal').classList.remove('flex');
}
</script>

<?php include_once '../includes/footer.php'; ?>