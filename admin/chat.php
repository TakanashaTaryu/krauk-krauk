<?php
require_once '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

$admin_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;

// If conversation_id is provided, load messages
if ($conversation_id) {
    // Get conversation details
    $stmt = $pdo->prepare("
        SELECT cc.*, p.id as order_id, p.status, p.id_customer, a.email as customer_email
        FROM chat_conversations cc
        JOIN pesanan p ON cc.id_pesanan = p.id
        JOIN akun a ON p.id_customer = a.id
        WHERE cc.id = ?
    ");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        setAlert('error', 'Conversation not found');
        redirect('manage_orders.php');
    }
    
    // Mark all customer messages as read
    $stmt = $pdo->prepare("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE id_conversation = ? AND is_admin = 0 AND is_read = 0
    ");
    $stmt->execute([$conversation_id]);
    
    // Load messages
    $stmt = $pdo->prepare("
        SELECT cm.*, a.email
        FROM chat_messages cm
        JOIN akun a ON cm.sender_id = a.id
        WHERE cm.id_conversation = ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();
}

// Get all conversations with unread counts
$stmt = $pdo->prepare("
    SELECT cc.id, p.id as order_id, p.status, a.email as customer_email,
           (SELECT COUNT(*) FROM chat_messages WHERE id_conversation = cc.id AND is_admin = 0 AND is_read = 0) as unread_count,
           (SELECT MAX(created_at) FROM chat_messages WHERE id_conversation = cc.id) as last_message_time
    FROM chat_conversations cc
    JOIN pesanan p ON cc.id_pesanan = p.id
    JOIN akun a ON p.id_customer = a.id
    ORDER BY unread_count DESC, last_message_time DESC
");
$stmt->execute();
$conversations = $stmt->fetchAll();

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    $conv_id = (int)$_POST['conversation_id'];
    
    if (empty($message)) {
        setAlert('error', 'Message cannot be empty');
    } else {
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (id_conversation, sender_id, is_admin, message)
            VALUES (?, ?, 1, ?)
        ");
        $stmt->execute([$conv_id, $admin_id, $message]);
        
        // Redirect to avoid form resubmission
        redirect("chat.php?conversation_id=$conv_id");
    }
}
?>

<div class="container mx-auto px-4 py-4 md:py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <h1 class="text-2xl md:text-3xl font-bold">Chat Pelanggan</h1>
            <a href="manage_orders.php" class="text-orange-600 hover:underline inline-flex items-center text-sm md:text-base">
                <i class="fas fa-arrow-left mr-1 md:mr-2"></i> Kembali ke Daftar Pesanan
            </a>
        </div>
        
        <!-- Mobile view: Conversation selector dropdown -->
        <div class="md:hidden mb-4">
            <select id="mobile-conversation-selector" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">Pilih Percakapan</option>
                <?php foreach ($conversations as $conv): ?>
                    <option value="<?= $conv['id'] ?>" <?= ($conversation_id == $conv['id']) ? 'selected' : '' ?>>
                        Pesanan #<?= $conv['order_id'] ?> - <?= $conv['customer_email'] ?>
                        <?= $conv['unread_count'] > 0 ? " ({$conv['unread_count']} new)" : "" ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
            <!-- Conversations List (hidden on mobile) -->
            <div class="hidden md:block md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-4">Semua Percakapan</h2>
                    
                    <?php if (empty($conversations)): ?>
                    <p class="text-gray-500 text-sm">Belum ada percakapan yang sedang berlangsung</p>
                    <?php else: ?>
                    <div class="space-y-2 max-h-[500px] overflow-y-auto">
                        <?php foreach ($conversations as $conv): ?>
                        <a href="?conversation_id=<?= $conv['id'] ?>" class="block p-3 rounded-md <?= ($conversation_id == $conv['id']) ? 'bg-orange-100' : 'hover:bg-gray-100' ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">Order #<?= $conv['order_id'] ?></p>
                                    <p class="text-xs text-gray-500"><?= $conv['customer_email'] ?></p>
                                    <p class="text-xs text-gray-500"><?= $conv['status'] ?></p>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1"><?= $conv['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Window -->
            <div class="md:col-span-3">
                <?php if (isset($conversation)): ?>
                <div class="bg-white rounded-lg shadow-md h-[calc(100vh-200px)] md:h-[600px] flex flex-col">
                    <!-- Chat Header -->
                    <div class="p-3 md:p-4 border-b">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-base md:text-lg font-semibold">Pesanan #<?= $conversation['order_id'] ?></h2>
                                <p class="text-xs md:text-sm text-gray-500">Nama Pemesan: <?= $conversation['customer_email'] ?></p>
                                <p class="text-xs md:text-sm text-gray-500">Status: <?= $conversation['status'] ?></p>
                            </div>
                            <div class="flex items-center">
                                <a href="order_details.php?id=<?= $conversation['order_id'] ?>" class="text-orange-600 hover:underline text-xs md:text-sm mr-3">
                                    Lihat Rincian Pesanan
                                </a>
                                <button id="show-conversations" class="md:hidden text-orange-600 p-1">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Messages -->
                    <div class="flex-1 overflow-y-auto p-3 md:p-4 space-y-3 md:space-y-4" id="chat-messages">
                        <?php if (empty($messages)): ?>
                        <div class="text-center text-gray-500 my-8">
                            <p>Belum ada konversasi</p>
                            <p class="text-sm mt-2">Mulailah konversasi dengan mengirim pesan berikut</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                            <div class="flex <?= $msg['is_admin'] ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[80%] <?= $msg['is_admin'] ? 'bg-orange-100' : 'bg-gray-200' ?> rounded-lg px-3 md:px-4 py-2">
                                    <div class="text-sm <?= $msg['is_admin'] ? 'text-gray-900' : 'text-gray-800' ?>">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?= $msg['is_admin'] ? 'You' : $msg['email'] ?> • <?= date('M j, g:i a', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="border-t p-2 md:p-4">
                        <form method="POST" class="flex">
                            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                            <textarea name="message" rows="2" class="flex-1 border rounded-l-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Type your message..."></textarea>
                            <button type="submit" name="send_message" class="bg-orange-600 text-white px-3 md:px-4 py-2 rounded-r-md hover:bg-orange-700 transition">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8 text-center">
                    <div class="text-gray-500 mb-4">
                        <i class="fas fa-comments text-4xl md:text-5xl"></i>
                    </div>
                    <h2 class="text-xl md:text-2xl font-semibold mb-4">Tidak ada percakapan yang dipilih</h2>
                    <p class="text-gray-600 mb-6">Pilih percakapan atau mulai percakapan baru di rincian pesanan</p>
                    <a href="manage_orders.php" class="bg-orange-600 text-white py-2 px-4 md:px-6 rounded-md hover:bg-orange-700 transition inline-block">
                        Lihat Daftar Pesanan
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mobile conversation list modal -->
<div id="mobile-conversations-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white w-full h-full md:w-80 md:h-auto md:rounded-lg md:mx-auto md:mt-20 p-4 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Semua Percakapan</h3>
            <button id="close-conversations" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if (empty($conversations)): ?>
            <p class="text-gray-500">Belum ada percakapan</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($conversations as $conv): ?>
                    <a href="?conversation_id=<?= $conv['id'] ?>" 
                       class="block p-3 rounded-md hover:bg-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">Pesanan #<?= $conv['order_id'] ?></p>
                                <p class="text-xs text-gray-500"><?= $conv['customer_email'] ?></p>
                                <p class="text-xs text-gray-500"><?= $conv['status'] ?></p>
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
            <a href="manage_orders.php" class="block w-full text-center bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
                Kembali ke Daftar Pesanan
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom of chat messages
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
    
    // Auto-refresh chat every 10 seconds
    if (chatMessages) {
        setInterval(function() {
            const conversationId = <?= $conversation_id ?? 0 ?>;
            if (conversationId > 0) {
                fetch('get_admin_messages.php?conversation_id=' + conversationId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.refresh) {
                            window.location.reload();
                        }
                    });
            }
        }, 10000);
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>