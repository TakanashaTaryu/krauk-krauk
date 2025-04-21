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

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $menu_id = clean($_POST['menu_id']);
    $quantity = clean($_POST['quantity']);
    
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
            // Cek apakah menu sudah ada di keranjang
            $stmt = $pdo->prepare("SELECT id, jumlah FROM keranjang WHERE id_customer = ? AND id_menu = ?");
            $stmt->execute([$_SESSION['user_id'], $menu_id]);
            $cart_item = $stmt->fetch();
            
            if ($cart_item) {
                // Update jumlah
                $new_quantity = $cart_item['jumlah'] + $quantity;
                if ($new_quantity > $menu['stok']) {
                    setAlert('error', 'Stok tidak mencukupi');
                } else {
                    $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
                    $stmt->execute([$new_quantity, $cart_item['id']]);
                    setAlert('success', 'Menu berhasil ditambahkan ke keranjang');
                    redirect('../customer/menu.php');
                }
            } else {
                // Tambah baru
                $stmt = $pdo->prepare("INSERT INTO keranjang (id_customer, id_menu, jumlah) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $menu_id, $quantity]);
                setAlert('success', 'Menu berhasil ditambahkan ke keranjang');
                redirect('../customer/menu.php');
            }
        }
    }
}

// Get all menu items
$stmt = $pdo->query("SELECT * FROM menu WHERE stok > 0 ORDER BY id");
$menu_items = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Menu Makanan</h1>
    
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
                
                <form method="POST" action="" class="flex items-center">
                    <input type="hidden" name="menu_id" value="<?= $item['id'] ?>">
                    <input 
                        type="number" 
                        name="quantity" 
                        value="1" 
                        min="1" 
                        max="<?= $item['stok'] ?>" 
                        class="w-20 px-2 py-1 border rounded-md mr-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    >
                    <button 
                        type="submit" 
                        name="add_to_cart" 
                        class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transition flex-grow"
                    >
                        Tambah ke Keranjang
                    </button>
                </form>
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

<?php
require_once '../includes/footer.php';
?>