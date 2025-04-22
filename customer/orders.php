<?php
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Get specific order details if ID is provided
if (isset($_GET['id'])) {
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
    
    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT pd.*, m.nama as menu_name, m.harga as menu_price
            FROM pesanan_detail pd
            JOIN menu m ON pd.id_menu = m.id
            WHERE pd.id_pesanan = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

// Get all orders if no specific ID or order not found
if (!isset($order)) {
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(pd.id) as total_items
        FROM pesanan p
        LEFT JOIN pesanan_detail pd ON p.id = pd.id_pesanan
        WHERE p.id_customer = ?
        GROUP BY p.id
        ORDER BY p.waktu_pemesanan DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}
?>

<div class="container mx-auto px-4 py-8">
    <?php if (isset($order)): ?>
        <!-- Order Detail View -->
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Order #<?= $order['id'] ?></h1>
                <a href="../customer/orders.php" class="text-orange-600 hover:underline inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                </a>
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
                
                <!-- Feedback Section - Only show when status is "Telah Sampai" -->
                <?php if ($order['status'] === 'Telah Sampai'): ?>
                    <?php
                    // Check if feedback already exists
                    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id_pesanan = ?");
                    $stmt->execute([$order['id']]);
                    $feedback = $stmt->fetch();
                    ?>
                    <div class="md:col-span-3" id="feedback">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold mb-4">
                                <?= $feedback ? 'Your Feedback' : 'Rate Your Order' ?>
                            </h2>
                            
                            <?php if ($feedback): ?>
                                <!-- Display existing feedback -->
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
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Feedback Form -->
                                <form method="POST" action="submit_feedback.php">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    
                                    <div class="mb-4">
                                        <label class="block text-gray-700 mb-2">Rating</label>
                                        <div class="flex space-x-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div>
                                                <input type="radio" name="rating" id="rating-<?= $i ?>" value="<?= $i ?>" class="hidden peer" <?= $i === 5 ? 'checked' : '' ?>>
                                                <label for="rating-<?= $i ?>" class="cursor-pointer text-2xl peer-checked:text-yellow-400 text-gray-300 hover:text-yellow-400">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="comment" class="block text-gray-700 mb-2">Comments (Optional)</label>
                                        <textarea id="comment" name="comment" rows="3" class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Share your experience with this order..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition">
                                        Submit Feedback
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif;?>
                
                <!-- Order Items and Payment Summary -->
                <div class="md:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold mb-4">Order Items</h2>
                        <div class="space-y-4">
                            <?php foreach ($order_items as $item): ?>
                            <div class="flex justify-between items-center border-b pb-4">
                                <div>
                                    <p class="font-medium"><?= htmlspecialchars($item['menu_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></p>
                                </div>
                                <p class="font-medium">Rp <?= number_format($item['jumlah'] * $item['harga_satuan'], 0, ',', '.') ?></p>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="pt-2 space-y-2">
                                <div class="flex justify-between items-center text-sm text-gray-600">
                                    <p>Subtotal</p>
                                    <?php
                                    $subtotal = 0;
                                    foreach ($order_items as $item) {
                                        $subtotal += $item['jumlah'] * $item['harga_satuan'];
                                    }
                                    $tax = $order['total_harga'] - $subtotal;
                                    ?>
                                    <p>Rp <?= number_format($subtotal, 0, ',', '.') ?></p>
                                </div>
                                <div class="flex justify-between items-center text-sm text-gray-600">
                                    <p>Tax</p>
                                    <p>Rp <?= number_format($tax, 0, ',', '.') ?></p>
                                </div>
                                <div class="flex justify-between items-center font-bold pt-2 border-t mt-2">
                                    <p>Total</p>
                                    <p>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer and Payment Information -->
                <div class="md:col-span-1">
                    <!-- Payment Information -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold mb-4">Payment</h2>
                        <div class="space-y-3">
                            <p><span class="text-gray-600 font-medium">Total:</span> 
                               <span class="font-bold">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                            </p>
                            
                            <div class="pt-2">
                                <?php if (!empty($order['bukti_pembayaran'])): ?>
                                <p class="text-green-600 mb-3"><i class="fas fa-check-circle mr-1"></i> Payment Confirmed</p>
                                <div>
                                    <p class="text-gray-600 mb-2">Payment Proof:</p>
                                    <img src="../assets/images/uploads/<?= htmlspecialchars($order['bukti_pembayaran']) ?>" 
                                         alt="Payment Proof" 
                                         class="w-full rounded-md border">
                                </div>
                                <?php else: ?>
                                <p class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> No payment proof uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-4">Delivery Information</h2>
                        <div class="space-y-2">
                            <p><span class="text-gray-600">Name:</span> <?= htmlspecialchars($order['nama_pemesan']) ?></p>
                            <p><span class="text-gray-600">Address:</span> <?= nl2br(htmlspecialchars($order['alamat_pemesan'])) ?></p>
                            
                            <!-- Display notes if available -->
                            <?php if (!empty($order['notes'])): ?>
                            <div class="mt-2 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                                <p class="font-medium text-gray-700">Special Instructions:</p>
                                <p class="text-gray-600"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Display Map if coordinates are available -->
                            <?php if (!empty($order['latitude']) && !empty($order['longitude'])): ?>
                            <div class="mt-4">
                                <p class="text-gray-600 mb-2">Delivery Location:</p>
                                <div id="map" class="w-full h-48 rounded-md border"></div>
                            </div>
                            
                            <!-- Leaflet Map -->
                            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const deliveryLocation = [<?= (float)$order['latitude'] ?>, <?= (float)$order['longitude'] ?>];
                                
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
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Orders List View -->
        <h1 class="text-3xl font-bold mb-8">My Orders</h1>
        
        <?php if (count($orders) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Find this section in orders.php where the order cards are displayed -->
                <?php foreach ($orders as $order): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <a href="?id=<?= $order['id'] ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold">Order #<?= $order['id'] ?></h3>
                                <p class="text-sm text-gray-500"><?= date('d M Y H:i', strtotime($order['waktu_pemesanan'])) ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                <?php
                                switch ($order['status']) {
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
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600"><?= $order['total_items'] ?> items</p>
                                <p class="font-medium">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                            </div>
                            <div class="text-orange-600">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Chat button inside the order box -->
                    <div class="border-t mt-4 pt-4 flex justify-between items-center">
                        <?php
                        // Check if a chat conversation exists for this order
                        $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id_pesanan = ?");
                        $stmt->execute([$order['id']]);
                        $conversation = $stmt->fetch();
                        
                        if ($conversation) {
                            // If conversation exists, show "Continue Chat" button
                            echo '<a href="chat.php?conversation_id=' . $conversation['id'] . '" class="text-orange-600 hover:underline flex items-center">';
                            echo '<i class="fas fa-comment mr-1"></i> Continue Chat</a>';
                        } else {
                            // If no conversation, show "Start Chat" button that creates a conversation directly
                            echo '<a href="javascript:void(0)" onclick="startChat(' . $order['id'] . ')" class="text-orange-600 hover:underline flex items-center">';
                            echo '<i class="fas fa-comment mr-1"></i> Start Chat</a>';
                        }
                        ?>
                        
                        <?php if ($order['status'] === 'Telah Sampai'): ?>
                            <?php
                            // Check if feedback already exists
                            $stmt = $pdo->prepare("SELECT id FROM feedback WHERE id_pesanan = ?");
                            $stmt->execute([$order['id']]);
                            $feedback = $stmt->fetch();
                            
                            if ($feedback) {
                                // If feedback exists, show "View Feedback" button
                                echo '<a href="?id=' . $order['id'] . '#feedback" class="text-green-600 hover:underline flex items-center">';
                                echo '<i class="fas fa-star mr-1"></i> View Feedback</a>';
                            } else {
                                // If no feedback, show "Rate Order" button
                                echo '<a href="?id=' . $order['id'] . '#feedback" class="text-yellow-600 hover:underline flex items-center">';
                                echo '<i class="fas fa-star mr-1"></i> Rate Order</a>';
                            }
                            ?>
                        <?php else: ?>
                            <a href="?id=<?= $order['id'] ?>" class="text-orange-600 hover:underline flex items-center">
                                <i class="fas fa-eye mr-1"></i> View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-shopping-bag text-5xl"></i>
                </div>
                <h2 class="text-2xl font-semibold mb-4">No orders yet</h2>
                <p class="text-gray-600 mb-6">You haven't placed any orders yet.</p>
                <a href="../customer/menu.php" class="bg-orange-600 text-white py-2 px-6 rounded-md hover:bg-orange-700 transition inline-block">
                    Browse Menu
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Star rating functionality
    const starLabels = document.querySelectorAll('label[for^="rating-"]');
    
    starLabels.forEach(label => {
        label.addEventListener('mouseover', function() {
            const currentRating = parseInt(this.getAttribute('for').split('-')[1]);
            
            // Highlight stars on hover
            starLabels.forEach((star, index) => {
                const starNumber = index + 1;
                if (starNumber <= currentRating) {
                    star.classList.add('text-yellow-400');
                    star.classList.remove('text-gray-300');
                } else {
                    star.classList.add('text-gray-300');
                    star.classList.remove('text-yellow-400');
                }
            });
        });
        
        label.addEventListener('click', function() {
            const currentRating = parseInt(this.getAttribute('for').split('-')[1]);
            
            // Set the radio button
            document.getElementById(`rating-${currentRating}`).checked = true;
            
            // Update visual state
            starLabels.forEach((star, index) => {
                const starNumber = index + 1;
                if (starNumber <= currentRating) {
                    star.classList.add('text-yellow-400');
                    star.classList.remove('text-gray-300');
                } else {
                    star.classList.add('text-gray-300');
                    star.classList.remove('text-yellow-400');
                }
            });
        });
    });
    
    // Reset stars when mouse leaves the rating area
    const ratingContainer = document.querySelector('.flex.space-x-2');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            // Find which star is selected
            const checkedInput = document.querySelector('input[name="rating"]:checked');
            const checkedValue = checkedInput ? parseInt(checkedInput.value) : 0;
            
            // Reset stars based on selection
            starLabels.forEach((star, index) => {
                const starNumber = index + 1;
                if (starNumber <= checkedValue) {
                    star.classList.add('text-yellow-400');
                    star.classList.remove('text-gray-300');
                } else {
                    star.classList.add('text-gray-300');
                    star.classList.remove('text-yellow-400');
                }
            });
        });
    }
});

// Function to start a new chat conversation
function startChat(orderId) {
    // Show a modal to get the initial message
    Swal.fire({
        title: 'Start Conversation',
        html: `
            <form id="chatForm">
                <textarea id="initialMessage" class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                          rows="4" placeholder="Type your message to customer service..."></textarea>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Send Message',
        confirmButtonColor: '#ea580c',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const message = document.getElementById('initialMessage').value.trim();
            if (!message) {
                Swal.showValidationMessage('Please enter a message');
                return false;
            }
            return message;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form to submit the data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'create_chat.php';
            
            // Add order ID
            const orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'order_id';
            orderIdInput.value = orderId;
            form.appendChild(orderIdInput);
            
            // Add message
            const messageInput = document.createElement('input');
            messageInput.type = 'hidden';
            messageInput.name = 'message';
            messageInput.value = result.value;
            form.appendChild(messageInput);
            
            // Submit the form
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>