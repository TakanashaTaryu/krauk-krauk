<?php
session_start();
require_once __DIR__ . '/../config/database.php';
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
    </style>
</head>
<body class="flex flex-col bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/kwu/index.php" class="text-2xl font-bold text-orange-600">Krauk-Krauk</a>
            <div class="space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                    // Verify admin status from database
                    $stmt = $pdo->prepare("SELECT admin_value FROM akun WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    $_SESSION['admin_value'] = $user['admin_value'] ?? 0;
                    
                    if ($_SESSION['admin_value'] == 1): 
                    ?>
                        <a href="/kwu/admin/dashboard.php" class="text-gray-700 hover:text-orange-600">Dashboard</a>
                        <a href="/kwu/admin/manage_menu.php" class="text-gray-700 hover:text-orange-600">Menu</a>
                        <a href="/kwu/admin/manage_customers.php" class="text-gray-700 hover:text-orange-600">Customers</a>
                        <a href="/kwu/admin/manage_orders.php" class="text-gray-700 hover:text-orange-600">Pesanan</a>
                    <?php else: ?>
                        <a href="/kwu/customer/menu.php" class="text-gray-700 hover:text-orange-600">Menu</a>
                        <a href="/kwu/customer/cart.php" class="text-gray-700 hover:text-orange-600">
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
                        <a href="/kwu/customer/orders.php" class="text-gray-700 hover:text-orange-600">Pesanan</a>
                    <?php endif; ?>
                    <div class="inline-block relative group">
                        <button class="text-gray-700 hover:text-orange-600">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['email']) ?>
                        </button>
                        <div class="absolute right-0 hidden group-hover:block bg-white shadow-lg rounded-md mt-1 py-2 w-48 z-10">
                            <?php if (!isset($_SESSION['admin_value']) || $_SESSION['admin_value'] != 1): ?>
                                <a href="/kwu/customer/profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profil</a>
                            <?php endif; ?>
                            <a href="/kwu/auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/kwu/auth/login.php" class="text-gray-700 hover:text-orange-600">Login</a>
                    <a href="/kwu/auth/register.php" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php
    $alert = getAlert();
    if ($alert): ?>
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

    <main>