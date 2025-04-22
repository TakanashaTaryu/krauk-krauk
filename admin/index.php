<!-- Add this to the admin dashboard stats section -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Existing stat cards -->
    
    <!-- New Messages Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <?php
        // Get unread messages count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.id_conversation = cc.id
            WHERE cm.is_admin = 0 AND cm.is_read = 0
        ");
        $stmt->execute();
        $unread_messages = $stmt->fetch()['count'];
        ?>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500">Unread Messages</p>
                <h3 class="text-2xl font-bold"><?= $unread_messages ?></h3>
            </div>
            <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                <i class="fas fa-comment text-xl"></i>
            </div>
        </div>
        <a href="chat.php" class="text-orange-600 hover:underline text-sm mt-4 inline-block">View Messages →</a>
    </div>
</div>