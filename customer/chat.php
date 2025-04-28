<?php
include_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../auth/login.php');
}

// Check if user is not admin
if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

// Get all conversations for this user
$stmt = $pdo->prepare("
    SELECT cc.id, cc.created_at, p.id as order_id, p.status, p.total_harga,
    (SELECT COUNT(*) FROM chat_messages WHERE id_conversation = cc.id AND is_admin = 1 AND is_read = 0) as unread_count
    FROM chat_conversations cc
    JOIN pesanan p ON cc.id_pesanan = p.id
    WHERE p.id_customer = ?
    ORDER BY cc.created_at DESC
");
$stmt->execute([$user_id]);
$conversations = $stmt->fetchAll();

// If conversation_id is provided, get messages for that conversation
$messages = [];
$current_conversation = null;
$order_details = null;


if ($conversation_id > 0) {
    // Mark all messages as read
    $stmt = $pdo->prepare("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE id_conversation = ? AND is_admin = 1 AND is_read = 0
    ");
    $stmt->execute([$conversation_id]);
    
    // Get conversation details
    $stmt = $pdo->prepare("
        SELECT cc.*, p.id as order_id, p.status, p.total_harga, p.waktu_pemesanan
        FROM chat_conversations cc
        JOIN pesanan p ON cc.id_pesanan = p.id
        WHERE cc.id = ? AND p.id_customer = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    $current_conversation = $stmt->fetch();
    
    if (!$current_conversation) {
        setAlert('error', 'Conversation not found');
        redirect('chat.php');
    }
    
    // Get messages for this conversation
    $stmt = $pdo->prepare("
        SELECT cm.*, a.email
        FROM chat_messages cm
        JOIN akun a ON cm.sender_id = a.id
        WHERE cm.id_conversation = ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT pd.jumlah, pd.harga_satuan, m.nama as menu_name
        FROM pesanan_detail pd
        JOIN menu m ON pd.id_menu = m.id
        WHERE pd.id_pesanan = ?
    ");
    $stmt->execute([$current_conversation['order_id']]);
    $order_details = $stmt->fetchAll();
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $conversation_id > 0) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (id_conversation, sender_id, is_admin, message)
            VALUES (?, ?, 0, ?)
        ");
        $stmt->execute([$conversation_id, $user_id, $message]);
        
        // Redirect to avoid form resubmission
        redirect("chat.php?conversation_id=$conversation_id");
    }
}

$page_title = "Chat";
?>

<div class="container mx-auto px-4 py-4 md:py-8">
    <h1 class="text-2xl md:text-3xl font-bold mb-4 md:mb-6">Chat Support</h1>
    
    <!-- Mobile view: Conversation selector dropdown (visible only on mobile) -->
    <div class="md:hidden mb-4">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-lg font-semibold">Konversasi</h2>
            <a href="orders.php" class="text-orange-600 hover:underline flex items-center text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
        
        <select id="mobile-conversation-selector" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
            <option value="">Pilih Percakapan</option>
            <?php foreach ($conversations as $conv): ?>
                <option value="<?= $conv['id'] ?>" <?= ($conversation_id == $conv['id']) ? 'selected' : '' ?>>
                    Pesanan #<?= $conv['order_id'] ?> - <?= $conv['status'] ?>
                    <?= $conv['unread_count'] > 0 ? " ({$conv['unread_count']} new)" : "" ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="flex flex-col md:flex-row gap-4 md:gap-6">
        <!-- Conversations List (hidden on mobile) -->
        <div class="hidden md:block w-full md:w-1/3 bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Percakapan Anda</h2>
                <a href="orders.php" class="text-orange-600 hover:underline flex items-center text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
            
            <?php if (empty($conversations)): ?>
                <p class="text-gray-500">Pilih percakapan dari list atau mulai baru dari list pesanan</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($conversations as $conv): ?>
                        <a href="chat.php?conversation_id=<?= $conv['id'] ?>" 
                           class="block p-3 rounded-md <?= ($conversation_id == $conv['id']) ? 'bg-orange-100' : 'hover:bg-gray-100' ?>">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">Pesanan #<?= htmlspecialchars($conv['order_id']) ?></p>
                                    <p class="text-sm text-gray-500">Status: <?= htmlspecialchars($conv['status']) ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?= date('M d, Y H:i', strtotime($conv['created_at'])) ?>
                                    </p>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1">
                                        <?= $conv['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Chat Window -->
        <div class="w-full md:w-2/3">
            <?php if ($current_conversation): ?>
                <div class="bg-white rounded-lg shadow-md h-[calc(100vh-200px)] md:h-[600px] flex flex-col">
                    <!-- Chat Header -->
                    <div class="p-3 md:p-4 border-b">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold">Pesanan #<?= $current_conversation['order_id'] ?></h3>
                                <p class="text-xs md:text-sm text-gray-500">
                                    Status: <?= $current_conversation['status'] ?> | 
                                    Total: Rp <?= number_format($current_conversation['total_harga'], 0, ',', '.') ?>
                                </p>
                            </div>
                            <div class="md:hidden">
                                <button id="show-conversations" class="text-orange-600 p-2">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Messages -->
                    <div class="flex-1 p-3 md:p-4 overflow-y-auto" id="chat-messages">
                        <?php foreach ($messages as $msg): ?>
                            <div class="mb-3 <?= $msg['is_admin'] ? 'pl-2 md:pl-4' : 'pr-2 md:pr-4 flex justify-end' ?>">
                                <div class="<?= $msg['is_admin'] ? 'bg-gray-200' : 'bg-orange-100' ?> rounded-lg p-2 md:p-3 inline-block max-w-[85%] md:max-w-[80%]">
                                    <p class="text-xs md:text-sm font-semibold">
                                        <?= $msg['is_admin'] ? 'Admin' : 'You' ?>
                                    </p>
                                    <p class="break-words text-sm md:text-base"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= date('M d, H:i', strtotime($msg['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="p-2 md:p-4 border-t">
                        <form method="POST" class="flex">
                            <textarea name="message" rows="2" 
                                      class="flex-1 border rounded-l-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                                      placeholder="Type your message here..."></textarea>
                            <button type="submit" 
                                    class="bg-orange-600 text-white px-3 md:px-4 py-2 rounded-r-md hover:bg-orange-700">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8 text-center">
                    <i class="fas fa-comments text-orange-600 text-4xl md:text-5xl mb-4"></i>
                    <h3 class="text-lg md:text-xl font-semibold mb-2">Tidak ada pesanan terpilih</h3>
                    <p class="text-gray-500 mb-4">Pilih percakapan dari list atau mulai baru dari list pesanan.</p>
                    <a href="orders.php" class="inline-block bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
                        Kembali ke Pesanan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile conversation list modal -->
<div id="mobile-conversations-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white w-full h-full md:w-80 md:h-auto md:rounded-lg md:mx-auto md:mt-20 p-4 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Percakapan Anda</h3>
            <button id="close-conversations" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if (empty($conversations)): ?>
            <p class="text-gray-500">Belum ada percakapan.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($conversations as $conv): ?>
                    <a href="chat.php?conversation_id=<?= $conv['id'] ?>" 
                       class="block p-3 rounded-md hover:bg-gray-100">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">Order #<?= $conv['order_id'] ?></p>
                                <p class="text-sm text-gray-500">Status: <?= $conv['status'] ?></p>
                                <p class="text-sm text-gray-500">
                                    <?= date('M d, Y H:i', strtotime($conv['created_at'])) ?>
                                </p>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1">
                                    <?= $conv['unread_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 pt-4 border-t">
            <a href="orders.php" class="block w-full text-center bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
                Kembali ke Pesanan
            </a>
        </div>
    </div>
</div>

<script>
    // Auto-scroll to bottom of chat messages
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Mobile conversation selector
        const mobileSelector = document.getElementById('mobile-conversation-selector');
        if (mobileSelector) {
            mobileSelector.addEventListener('change', function() {
                if (this.value) {
                    window.location.href = 'chat.php?conversation_id=' + this.value;
                }
            });
        }
        
        // Mobile conversations modal
        const showConversationsBtn = document.getElementById('show-conversations');
        const closeConversationsBtn = document.getElementById('close-conversations');
        const conversationsModal = document.getElementById('mobile-conversations-modal');
        
        if (showConversationsBtn && conversationsModal) {
            showConversationsBtn.addEventListener('click', function() {
                conversationsModal.classList.remove('hidden');
            });
        }
        
        if (closeConversationsBtn && conversationsModal) {
            closeConversationsBtn.addEventListener('click', function() {
                conversationsModal.classList.add('hidden');
            });
        }
        
        // Auto-refresh every 10 seconds to check for new messages
        <?php if ($conversation_id > 0): ?>
        setInterval(function() {
            fetch('get_messages.php?conversation_id=<?= $conversation_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.refresh) {
                        window.location.reload();
                    }
                });
        }, 10000);
        <?php endif; ?>
    });
</script>

<?php include_once '../includes/footer.php'; ?>