<?php
// customer/menu.php
require_once '../includes/header.php';

// Cek login
if (!isLoggedIn()) {
    setAlert('error', 'Silakan login terlebih dahulu');
    redirect('../auth/login.php');
}

// Cek jika user adalah admin
if (isAdmin()) {
    setAlert('error', 'Anda tidak memiliki akses');
    redirect('../admin/dashboard.php');
}

// Get all menu items
$stmt = $pdo->query("SELECT * FROM menu WHERE stok > 0 ORDER BY id");
$menu_items = $stmt->fetchAll();

// Get add-ons for each menu
$menu_addons = [];
foreach ($menu_items as $item) {
    $stmt = $pdo->prepare("
        SELECT * FROM menu_add_ons 
        WHERE id_menu = ? 
        ORDER BY nama
    ");
    $stmt->execute([$item['id']]);
    $menu_addons[$item['id']] = $stmt->fetchAll();
}

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $menu_id = clean($_POST['menu_id']);
    $quantity = clean($_POST['quantity']);
    $selected_addons = $_POST['addons'] ?? [];
    
    // Validasi
    if (empty($menu_id) || empty($quantity) || $quantity < 1) {
        setAlert('error', 'Jumlah harus minimal 1');
    } else {
        // Cek stok
        $stmt = $pdo->prepare("SELECT stok FROM menu WHERE id = ?");
        $stmt->execute([$menu_id]);
        $menu = $stmt->fetch();
        
        if (!$menu) {
            setAlert('error', 'Menu tidak ditemukan');
        } elseif ($menu['stok'] < $quantity) {
            setAlert('error', 'Stok tidak mencukupi');
        } else {
            try {
                $pdo->beginTransaction();
                
                // Tambah baru ke keranjang
                $stmt = $pdo->prepare("INSERT INTO keranjang (id_customer, id_menu, jumlah) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $menu_id, $quantity]);
                $cart_id = $pdo->lastInsertId();
                
                // Tambahkan add-ons jika ada
                if (!empty($selected_addons)) {
                    foreach ($selected_addons as $addon_id) {
                        $stmt = $pdo->prepare("
                            INSERT INTO keranjang_add_ons (id_keranjang, id_add_on, jumlah) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$cart_id, $addon_id, $quantity]);
                    }
                }
                
                $pdo->commit();
                setAlert('success', 'Menu berhasil ditambahkan ke keranjang');
                redirect('../customer/menu.php');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                setAlert('error', 'Gagal menambahkan ke keranjang: ' . $e->getMessage());
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Menu Makanan</h1>
    <!-- Pre-order Notice -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Informasi</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>Saat ini kami hanya melayani pemesanan di area Dayeuhkolot-Telkomuniversity-Bojongsoang</p>
                </div>
            </div>
        </div>
    </div>
    <br>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($menu_items as $item): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <img src="../assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($item['nama']) ?></h3>
                <p class="text-gray-600 mb-2"><?= htmlspecialchars($item['deskripsi']) ?></p>
                <div class="flex justify-between items-center mb-4">
                    <span class="font-bold text-orange-600">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                    <span class="text-sm text-gray-500">Stok: <?= $item['stok'] ?></span>
                </div>
                
                <button 
                    type="button" 
                    onclick="showAddToCartModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nama']) ?>', <?= $item['harga'] ?>, <?= $item['stok'] ?>)"
                    class="w-full bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition"
                >
                    Tambah ke Keranjang
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count($menu_items) == 0): ?>
        <div class="col-span-3 text-center py-8">
            <p class="text-gray-500">Tidak ada menu yang tersedia saat ini</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add to Cart Modal -->
<div id="addToCartModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold" id="modalTitle">Tambah ke Keranjang</h3>
            <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addToCartForm" method="POST" action="">
            <input type="hidden" name="menu_id" id="menu_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Jumlah:</label>
                <input 
                    type="number" 
                    name="quantity" 
                    id="quantity" 
                    value="1" 
                    min="1" 
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                >
                <p class="text-sm text-gray-500 mt-1">Stok tersedia: <span id="availableStock">0</span></p>
            </div>
            
            <div id="addonsContainer" class="mb-4">
                <label class="block text-gray-700 mb-2">Tambahan:</label>
                <div id="addonsList" class="space-y-2 max-h-40 overflow-y-auto">
                    <!-- Add-ons will be inserted here dynamically -->
                </div>
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-700">Total:</span>
                <span class="font-bold text-orange-600" id="totalPrice">Rp 0</span>
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="button" 
                    onclick="closeModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-md mr-2 hover:bg-gray-100"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    name="add_to_cart" 
                    class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700"
                >
                    Tambah
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let menuAddons = <?= json_encode($menu_addons) ?>;
    let currentMenuId = 0;
    let currentPrice = 0;
    let currentStock = 0;
    
    function showAddToCartModal(menuId, menuName, price, stock) {
        currentMenuId = menuId;
        currentPrice = price;
        currentStock = stock;
        
        // Set modal title and form values
        document.getElementById('modalTitle').textContent = 'Tambah ' + menuName;
        document.getElementById('menu_id').value = menuId;
        document.getElementById('quantity').value = 1;
        document.getElementById('quantity').max = stock;
        document.getElementById('availableStock').textContent = stock;
        
        // Clear and populate add-ons
        const addonsList = document.getElementById('addonsList');
        addonsList.innerHTML = '';
        
        if (menuAddons[menuId] && menuAddons[menuId].length > 0) {
            document.getElementById('addonsContainer').style.display = 'block';
            
            menuAddons[menuId].forEach(addon => {
                const addonItem = document.createElement('div');
                addonItem.className = 'flex items-center';
                addonItem.innerHTML = `
                    <input type="checkbox" 
                           id="addon_${addon.id}" 
                           name="addons[]" 
                           value="${addon.id}"
                           onchange="updateTotal()">
                    <label for="addon_${addon.id}" class="ml-2">
                        ${addon.nama} (+Rp ${formatNumber(addon.harga)})
                    </label>
                `;
                addonsList.appendChild(addonItem);
            });
        } else {
            document.getElementById('addonsContainer').style.display = 'none';
        }
        
        // Update total price
        updateTotal();
        
        // Show modal
        document.getElementById('addToCartModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('addToCartModal').classList.add('hidden');
    }
    
    function updateTotal() {
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        let total = currentPrice * quantity;
        
        // Add selected add-ons
        if (menuAddons[currentMenuId]) {
            menuAddons[currentMenuId].forEach(addon => {
                const checkbox = document.getElementById(`addon_${addon.id}`);
                if (checkbox && checkbox.checked) {
                    total += addon.harga * quantity;
                }
            });
        }
        
        document.getElementById('totalPrice').textContent = 'Rp ' + formatNumber(total);
    }
    
    function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
    
    // Update total when quantity changes
    document.getElementById('quantity').addEventListener('input', updateTotal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('addToCartModal');
        if (event.target === modal) {
            closeModal();
        }
    });
</script>

<?php
require_once '../includes/footer.php';
?>