<?php
// index.php
require_once 'includes/header.php';
?>

<!-- Hero Section with Animation -->
<div class="hero-section transition-fade" style="background-image: url('../assets/images/hero-food.jpg');">
    <div class="hero-overlay flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 animate__animated animate__zoomIn">Krauk-Krauk</h1>
            <p class="text-xl md:text-2xl text-white mb-8 animate__animated animate__fadeInUp animate__delay-1s">Temukan dan pesan makanan favorit Anda dengan mudah</p>
            <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
               class="bg-orange-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-orange-700 transition animate__animated animate__pulse animate__infinite animate__slower">
                <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Dashboard Admin' : 'Pesan Sekarang' ?>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold text-center mb-12 scroll-animation" data-animation="animate__fadeIn">Menu Favorit</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
        <?php
        // Menampilkan 3 menu teratas
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY id LIMIT 2");
        $delay = 1;
        while ($menu = $stmt->fetch()) {
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 scroll-animation" data-animation="animate__fadeInUp">
            <img src="../assets/images/menu/<?= htmlspecialchars($menu['gambar']) ?>" alt="<?= htmlspecialchars($menu['nama']) ?>" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($menu['nama']) ?></h3>
                <p class="text-gray-600 mb-4"><?= htmlspecialchars($menu['deskripsi']) ?></p>
                <div class="flex justify-between items-center">
                    <span class="font-bold text-orange-600">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></span>
                    <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
                       class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 transform hover:scale-105 transition-transform">
                        <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Dashboard' : 'Pesan' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
            $delay += 0.5;
        }
        ?>
    </div>
    
    <div class="text-center mt-8">
        <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
        class="text-orange-600 font-semibold hover:underline hover:text-orange-700 transition-colors scroll-animation" data-animation="animate__heartBeat">
            <?= isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1 ? 'Ke Dashboard' : 'Lihat Semua Menu' ?>
        </a>
    </div>
</div>

<div class="bg-orange-50 py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 scroll-animation" data-animation="animate__fadeIn">Mengapa Memilih Kami?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-4 scroll-animation" data-animation="animate__fadeInLeft">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 hover:bg-orange-700 transition-colors transform hover:scale-110 transition-transform">
                    <i class="fas fa-utensils text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Makanan Berkualitas</h3>
                <p class="text-gray-600">Kami hanya menyajikan makanan dengan bahan-bahan terbaik dan segar setiap hari.</p>
            </div>
            
            <div class="text-center p-4 scroll-animation" data-animation="animate__fadeInUp">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 hover:bg-orange-700 transition-colors transform hover:scale-110 transition-transform">
                    <i class="fas fa-truck text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Pengiriman Cepat</h3>
                <p class="text-gray-600">Makanan Anda akan dikirim dengan cepat dan dalam kondisi terbaik.</p>
            </div>
            
            <div class="text-center p-4 scroll-animation" data-animation="animate__fadeInRight">
                <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 hover:bg-orange-700 transition-colors transform hover:scale-110 transition-transform">
                    <i class="fas fa-tags text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Harga Terjangkau</h3>
                <p class="text-gray-600">Nikmati makanan enak dan berkualitas dengan harga yang ramah di kantong.</p>
            </div>
        </div>
    </div>
</div>

<!-- Customer Reviews Section -->
<div class="py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 scroll-animation" data-animation="animate__fadeIn">Apa Kata Pelanggan Kami</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Review 1 -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-all duration-300 scroll-animation" data-animation="animate__fadeInLeft">
                <div class="flex items-center mb-4">
                    <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-4">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold">Budi Santoso</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Makanan di Krauk-Krauk sangat lezat! Saya selalu memesan Tahu Cabai Garam mereka setiap open pre order. Pengiriman cepat dan pelayanan ramah. Sangat direkomendasikan!"</p>
            </div>
            
            <!-- Review 2 -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-all duration-300 scroll-animation" data-animation="animate__fadeInUp">
                <div class="flex items-center mb-4">
                    <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-4">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold">Siti Rahayu</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Saya sangat suka minuman Orange Punch. Manisnya pas dan rasanya autentik. Harganya juga sangat terjangkau untuk kualitas sebagus ini."</p>
            </div>
            
            <!-- Review 3 -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-all duration-300 scroll-animation" data-animation="animate__fadeInRight">
                <div class="flex items-center mb-4">
                    <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-4">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold">Ahmad Hidayat</h4>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 italic">"Pertama kali pesan karena rekomendasi teman, dan sekarang saya jadi pelanggan tetap! Makanan selalu datang dalam keadaan hangat dan segar. Layanan pelanggan juga sangat responsif."</p>
            </div>
        </div>
        
        <div class="text-center mt-8">
            <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? '../admin/dashboard.php' : '../customer/menu.php') : '../auth/login.php' ?>" 
               class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700 transition scroll-animation" data-animation="animate__pulse">
                Pesan Sekarang
            </a>
        </div>
    </div>
</div>

<!-- Add Animate.css library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<!-- Improved scroll animation script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all elements with scroll-animation class
    const animatedElements = document.querySelectorAll('.scroll-animation');
    
    // Create intersection observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // If element is in view
            if (entry.isIntersecting) {
                // Get animation class from data attribute
                const animationClass = entry.target.dataset.animation;
                
                // Add animation classes
                entry.target.classList.add('animate__animated', animationClass);
                
                // Stop observing after animation is triggered
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null, // viewport
        threshold: 0.1, // 10% of element must be visible
        rootMargin: '0px 0px -50px 0px' // Trigger slightly before element comes into view
    });
    
    // Observe each element
    animatedElements.forEach(element => {
        observer.observe(element);
    });
    
    // Add fade-in effect to page load
    document.body.classList.add('fade-in');
});
</script>

<!-- Add custom styles for page transitions -->
<style>
body {
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

body.fade-in {
    opacity: 1;
}

.scroll-animation {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.animate__animated {
    opacity: 1;
}

/* Smoother transitions */
.transition-all {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<?php
require_once 'includes/footer.php';
?>