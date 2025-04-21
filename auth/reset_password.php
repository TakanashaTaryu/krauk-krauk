<?php
// auth/reset_password.php
require_once '../includes/header.php';

// Step 1: Form untuk input email
// Step 2: Verifikasi email dan tampilkan form untuk password baru
// Step 3: Update password

$step = 1;
$email = '';

// Proses step 1 - Verifikasi email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 1) {
    $email = clean($_POST['email']);
    
    // Validasi
    if (empty($email)) {
        setAlert('error', 'Email harus diisi');
    } else {
        // Cek apakah email terdaftar
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM akun WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $step = 2;
            $_SESSION['reset_email'] = $email; // Simpan email di session untuk step 2
        } else {
            setAlert('error', 'Email tidak terdaftar');
        }
    }
}

// Proses step 2 - Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 2) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($password) || empty($confirm_password)) {
        setAlert('error', 'Semua field harus diisi');
    } elseif ($password !== $confirm_password) {
        setAlert('error', 'Password dan konfirmasi password tidak cocok');
    } elseif (strlen($password) < 6) {
        setAlert('error', 'Password minimal 6 karakter');
    } else {
        $email = $_SESSION['reset_email'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $pdo->prepare("UPDATE akun SET password = ? WHERE email = ?");
        if ($stmt->execute([$hashed_password, $email])) {
            unset($_SESSION['reset_email']); // Hapus session reset_email
            setAlert('success', 'Password berhasil diubah. Silakan login');
            redirect('../auth/login.php');
        } else {
            setAlert('error', 'Terjadi kesalahan. Silakan coba lagi');
        }
    }
}

// Jika ada session reset_email dan belum step 2, set step 2
if (isset($_SESSION['reset_email']) && $step == 1) {
    $step = 2;
    $email = $_SESSION['reset_email'];
}
?>

<div class="container mx-auto px-4 py-16">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Reset Password</h2>
        
        <?php if ($step == 1): ?>
        <!-- Step 1: Form untuk input email -->
        <form method="POST" action="">
            <input type="hidden" name="step" value="1">
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                    value="<?= htmlspecialchars($email) ?>"
                >
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition"
            >
                Lanjutkan
            </button>
        </form>
        <?php elseif ($step == 2): ?>
        <!-- Step 2: Form untuk password baru -->
        <form method="POST" action="">
            <input type="hidden" name="step" value="2">
            
            <div class="mb-4">
                <p class="text-gray-700 mb-4">Masukkan password baru untuk akun dengan email: <strong><?= htmlspecialchars($email) ?></strong></p>
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password Baru</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                    minlength="6"
                >
            </div>
            
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Konfirmasi Password Baru</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                    minlength="6"
                >
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition"
            >
                Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <p><a href="../auth/login.php" class="text-orange-600 hover:underline">Kembali ke halaman login</a></p>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>