<?php
// index.php
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section transition-fade" style="background-image: url('../assets/images/hero-food.jpg');">
    <div class="hero-overlay flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">Krauk-Krauk</h1>
            <p class="text-xl md:text-2xl text-white mb-8">Temukan dan pesan makanan favorit Anda dengan mudah</p>
            <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
               class="bg-orange-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-orange-700 transition">
                <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Dashboard Admin' : 'Pesan Sekarang' ?>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold text-center mb-12">Menu Favorit</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php
        // Menampilkan 3 menu teratas
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY id LIMIT 3");
        while ($menu = $stmt->fetch()) {
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <img src="../assets/images/menu/<?= htmlspecialchars($menu['gambar']) ?>" alt="<?= htmlspecialchars($menu['nama']) ?>" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($menu['nama']) ?></h3>
                <p class="text-gray-600 mb-4"><?= htmlspecialchars($menu['deskripsi']) ?></p>
                <div class="flex justify-between items-center">
                    <span class="font-bold text-orange-600">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></span>
                    <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
                       class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
                        <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Dashboard' : 'Pesan' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
    
    <div class="text-center mt-8">
        <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
        class="text-orange-600 font-semibold hover:underline">
            <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Ke Dashboard' : 'Lihat Semua Menu' ?>
        </a>
    </div>
</div>

<div class="bg-orange-50 py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8">Mengapa Memilih Kami?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-4">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-utensils text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Makanan Berkualitas</h3>
                <p class="text-gray-600">Kami hanya menyajikan makanan dengan bahan-bahan terbaik dan segar setiap hari.</p>
            </div>
            
            <div class="text-center p-4">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-truck text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Pengiriman Cepat</h3>
                <p class="text-gray-600">Makanan Anda akan dikirim dengan cepat dan dalam kondisi terbaik.</p>
            </div>
            
            <div class="text-center p-4">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tags text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Harga Terjangkau</h3>
                <p class="text-gray-600">Nikmati makanan enak dengan harga yang ramah di kantong.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>