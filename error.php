<?php
// Get error code from URL parameter or default to 400
$error_code = isset($_GET['code']) ? intval($_GET['code']) : 400;

// Set the HTTP response code
http_response_code($error_code);

// Define error messages for common codes
$error_messages = [
    400 => 'Permintaan Tidak Valid',
    401 => 'Tidak Terotentikasi',
    403 => 'Akses Ditolak',
    404 => 'Halaman Tidak Ditemukan',
    405 => 'Metode Tidak Diizinkan',
    408 => 'Waktu Permintaan Habis',
    429 => 'Terlalu Banyak Permintaan',
    500 => 'Kesalahan Server Internal',
    502 => 'Gateway Tidak Valid',
    503 => 'Layanan Tidak Tersedia',
    504 => 'Waktu Gateway Habis'
];

// Get error message or use generic message
$error_message = isset($error_messages[$error_code]) 
    ? $error_messages[$error_code] 
    : 'Terjadi Kesalahan';

require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <div class="text-center max-w-lg mx-auto">
        <div class="mb-8">
            <span class="text-orange-600 text-8xl font-bold"><?= $error_code ?></span>
        </div>
        <h1 class="text-4xl font-bold mb-4"><?= $error_message ?></h1>
        <p class="text-gray-600 mb-8">
            <?php if ($error_code >= 500): ?>
                Maaf, terjadi kesalahan pada server kami. Tim teknis kami sedang bekerja untuk memperbaikinya.
            <?php elseif ($error_code == 404): ?>
                Maaf, halaman yang Anda cari tidak dapat ditemukan atau telah dipindahkan.
            <?php elseif ($error_code == 403): ?>
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            <?php else: ?>
                Maaf, terjadi kesalahan saat memproses permintaan Anda.
            <?php endif; ?>
        </p>
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