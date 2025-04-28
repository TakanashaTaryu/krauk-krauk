<?php
require_once '../includes/header.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || isAdmin()) {
    redirect('../auth/login.php');
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM akun WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile
        $no_telp = clean($_POST['no_telp']);
        
        $stmt = $pdo->prepare("UPDATE akun SET no_telp = ? WHERE id = ?");
        if ($stmt->execute([$no_telp, $_SESSION['user_id']])) {
            setAlert('success', 'Profile updated successfully');
            redirect('../customer/profile.php');
        }
    } elseif (isset($_POST['update_password'])) {
        // Update password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!password_verify($current_password, $user['password'])) {
            setAlert('error', 'Current password is incorrect');
        } elseif ($new_password !== $confirm_password) {
            setAlert('error', 'New passwords do not match');
        } elseif (strlen($new_password) < 6) {
            setAlert('error', 'Password must be at least 6 characters');
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE akun SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                setAlert('success', 'Password updated successfully');
                redirect('../customer/profile.php');
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <h1 class="text-3xl font-bold mb-8">Setting Profil</h1>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-4">Informasi Akun</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Email</label>
                        <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Nomor Telp</label>
                        <input type="tel" name="no_telp" value="<?= htmlspecialchars($user['no_telp']) ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <button type="submit" name="update_profile"
                            class="w-full bg-orange-600 text-white py-2 px-4 rounded hover:bg-orange-700 transition">
                         Ubah Profil
                    </button>
                </form>
            </div>
            
            <div class="border-t pt-6">
                <h2 class="text-xl font-bold mb-4">Ubah Password</h2>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Password Saat Ini</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Password Baru</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required minlength="6"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <button type="submit" name="update_password"
                            class="w-full bg-orange-600 text-white py-2 px-4 rounded hover:bg-orange-700 transition">
                        Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>