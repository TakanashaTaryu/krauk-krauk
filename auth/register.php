<?php
require_once '../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/kwu/admin/dashboard.php');
    } else {
        redirect('/kwu/customer/menu.php');
    }
}

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $no_telp = clean($_POST['no_telp']);
    
    // Validation
    if (empty($email) || empty($password) || empty($confirm_password) || empty($no_telp)) {
        setAlert('error', 'Semua field harus diisi');
    } elseif ($password !== $confirm_password) {
        setAlert('error', 'Password dan konfirmasi password tidak cocok');
    } elseif (strlen($password) < 6) {
        setAlert('error', 'Password minimal 6 karakter');
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM akun WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            setAlert('error', 'Email sudah terdaftar');
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO akun (email, password, no_telp, admin_value) VALUES (?, ?, ?, 0)");
            if ($stmt->execute([$email, $hashed_password, $no_telp])) {
                setAlert('success', 'Register berhasil. Silakan login');
                redirect('/kwu/auth/login.php');
            } else {
                setAlert('error', 'Terjadi kesalahan. Silakan coba lagi');
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-16">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Register</h2>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                >
            </div>

            <div class="mb-4">
                <label for="no_telp" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                <input 
                    type="tel" 
                    id="no_telp" 
                    name="no_telp" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                >
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
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
                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Konfirmasi Password</label>
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
                Register
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p>Sudah punya akun? <a href="/kwu/auth/login.php" class="text-orange-600 hover:underline">Login</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>