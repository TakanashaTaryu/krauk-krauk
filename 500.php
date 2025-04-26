<?php
// Set the HTTP response code
http_response_code(500);
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <div class="text-center max-w-lg mx-auto">
        <div class="mb-8">
            <span class="text-orange-600 text-8xl font-bold">500</span>
        </div>
        <h1 class="text-4xl font-bold mb-4">Kesalahan Server</h1>
        <p class="text-gray-600 mb-8">Maaf, terjadi kesalahan pada server kami. Tim teknis kami sedang bekerja untuk memperbaikinya.</p>
        <div class="flex justify-center">
            <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['admin_value'] == 1 ? 'admin/dashboard.php' : 'customer/menu.php') : 'index.php' ?>" 
               class="bg-orange-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-orange-700 transition">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>