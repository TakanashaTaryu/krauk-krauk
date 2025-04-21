<?php
// auth/login.php
require_once '../includes/header.php';

// Jika user sudah login, redirect ke halaman yang sesuai
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/kwu/admin/dashboard.php');
    } else {
        redirect('/kwu/customer/menu.php');
    }
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    // Validasi
    if (empty($email) || empty($password)) {
        setAlert('error', 'Email dan password harus diisi');
    } else {
        // Cek user di database
        $stmt = $pdo->prepare("SELECT * FROM akun WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['admin_value'] = $user['admin_value'];
            
            setAlert('success', 'Login berhasil');
            
            // Redirect ke halaman yang sesuai
            if ($user['admin_value'] == 1) {
                redirect('/kwu/admin/dashboard.php');
            } else {
                redirect('/kwu/customer/menu.php');
            }
        } else {
            setAlert('error', 'Email atau password salah');
        }
    }
}
?>

<div class="container mx-auto px-4 py-16">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
        
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
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" 
                    required
                >
                <div class="mt-1 text-right">
                    <a href="/kwu/auth/reset_password.php" class="text-sm text-orange-600 hover:underline">Lupa Password?</a>
                </div>
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition"
            >
                Login
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p>Belum punya akun? <a href="/kwu/auth/register.php" class="text-orange-600 hover:underline">Register</a></p>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>