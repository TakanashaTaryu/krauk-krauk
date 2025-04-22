<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Move getAlert function call here to ensure it's processed before any output
$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;
if (isset($_SESSION['alert'])) {
    unset($_SESSION['alert']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krauk-Krauk - Pemesanan Makanan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Existing styles remain the same */
        .hero-section {
            height: 100vh;
            background-position: center;
            background-size: cover;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        .transition-fade {
            transition: opacity 0.5s ease-in-out;
        }
        /* Burger menu styles */
        .burger-menu {
            display: none;
            cursor: pointer;
        }
        .burger-bar {
            width: 25px;
            height: 3px;
            background-color: #ea580c;
            margin: 5px 0;
            transition: 0.4s;
        }
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 70%;
            height: 100vh;
            background-color: white;
            z-index: 100;
            transition: right 0.3s ease-in-out;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            padding-top: 60px;
        }
        .mobile-menu.active {
            right: 0;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
        }
        .overlay.active {
            opacity: 1;
            visibility: visible;
        }
        /* Animation for burger icon */
        .burger-menu.active .bar1 {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        .burger-menu.active .bar2 {
            opacity: 0;
        }
        .burger-menu.active .bar3 {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        @media (max-width: 768px) {
            .desktop-menu {
                display: none;
            }
            .burger-menu {
                display: block;
            }
        }
    </style>
    
    <!-- Add notification script in the head to ensure it loads early -->
    <?php if ($alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: '<?= $alert['type'] ?>',
                title: '<?= addslashes($alert['message']) ?>'
            });
        });
    </script>
    <?php endif; ?>
</head>
<body class="flex flex-col bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="../index.php" class="text-2xl font-bold text-orange-600">Krauk-Krauk</a>
            
            <!-- Desktop Menu -->
            <div class="space-x-4 desktop-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                    // Verify admin status from database
                    $stmt = $pdo->prepare("SELECT admin_value FROM akun WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    $_SESSION['admin_value'] = $user['admin_value'] ?? 0;
                    
                    if ($_SESSION['admin_value'] == 1): 
                    ?>
                        <a href="../admin/dashboard.php" class="text-gray-700 hover:text-orange-600">Dashboard</a>
                        <a href="../admin/manage_menu.php" class="text-gray-700 hover:text-orange-600">Menu</a>
                        <a href="../admin/manage_customers.php" class="text-gray-700 hover:text-orange-600">Customers</a>
                        <a href="../admin/manage_orders.php" class="text-gray-700 hover:text-orange-600">Pesanan</a>
                    <?php else: ?>
                        <a href="../customer/menu.php" class="text-gray-700 hover:text-orange-600">Menu</a>
                        <a href="../customer/cart.php" class="text-gray-700 hover:text-orange-600">
                            <i class="fas fa-shopping-cart"></i>
                            <?php
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM keranjang WHERE id_customer = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $count = $stmt->fetch()['count'];
                                if ($count > 0) {
                                    echo "<span class='bg-orange-600 text-white rounded-full px-2 py-1 text-xs'>$count</span>";
                                }
                            }
                            ?>
                        </a>
                        <a href="../customer/orders.php" class="text-gray-700 hover:text-orange-600">Pesanan</a>
                    <?php endif; ?>
                    <div class="inline-block relative group">
                        <button class="text-gray-700 hover:text-orange-600">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['email']) ?>
                        </button>
                        <div class="absolute right-0 hidden group-hover:block bg-white shadow-lg rounded-md mt-1 py-2 w-48 z-10">
                            <?php if (!isset($_SESSION['admin_value']) || $_SESSION['admin_value'] != 1): ?>
                                <a href="../customer/profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profil</a>
                            <?php endif; ?>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="text-gray-700 hover:text-orange-600">Login</a>
                    <a href="../auth/register.php" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Register</a>
                <?php endif; ?>
            </div>
            
            <!-- Burger Menu Button -->
            <div class="burger-menu" id="burger-menu">
                <div class="burger-bar bar1"></div>
                <div class="burger-bar bar2"></div>
                <div class="burger-bar bar3"></div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu Overlay -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobile-menu">
        <div class="px-4 py-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['admin_value'] == 1): ?>
                    <a href="../admin/dashboard.php" class="block py-3 border-b text-gray-700">Dashboard</a>
                    <a href="../admin/manage_menu.php" class="block py-3 border-b text-gray-700">Manage Menu</a>
                    <a href="../admin/manage_customers.php" class="block py-3 border-b text-gray-700">Manage Customers</a>
                    <a href="../admin/manage_orders.php" class="block py-3 border-b text-gray-700">Manage Orders</a>
                    <a href="../admin/kitchen_orders.php" class="block py-3 border-b text-gray-700">Kitchen Dashboard</a>
                    <a href="../admin/driver_orders.php" class="block py-3 border-b text-gray-700">Driver Dashboard</a>
                <?php else: ?>
                    <a href="../customer/menu.php" class="block py-3 border-b text-gray-700">Menu</a>
                    <a href="../customer/cart.php" class="block py-3 border-b text-gray-700">
                        Keranjang
                        <?php
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM keranjang WHERE id_customer = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $count = $stmt->fetch()['count'];
                            if ($count > 0) {
                                echo "<span class='bg-orange-600 text-white rounded-full px-2 py-1 text-xs ml-2'>$count</span>";
                            }
                        }
                        ?>
                    </a>
                    <a href="../customer/orders.php" class="block py-3 border-b text-gray-700">Pesanan</a>
                    <a href="../customer/profile.php" class="block py-3 border-b text-gray-700">Profil</a>
                <?php endif; ?>
                <a href="../auth/logout.php" class="block py-3 text-gray-700">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php" class="block py-3 border-b text-gray-700">Login</a>
                <a href="../auth/register.php" class="block py-3 text-gray-700">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Burger Menu Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const burgerMenu = document.getElementById('burger-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('overlay');
            
            burgerMenu.addEventListener('click', function() {
                burgerMenu.classList.toggle('active');
                mobileMenu.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
            });
            
            overlay.addEventListener('click', function() {
                burgerMenu.classList.remove('active');
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close menu when clicking on a link
            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    burgerMenu.classList.remove('active');
                    mobileMenu.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
        });
    </script>

    <main>