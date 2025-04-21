<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    setAlert('error', 'Please login first');
    redirect('/kwu/auth/login.php');
}

if (isAdmin()) {
    setAlert('error', 'Access denied');
    redirect('/kwu/admin/dashboard.php');
}

$stmt = $pdo->prepare("
    SELECT k.id, k.jumlah, m.id as menu_id, m.nama, m.gambar, m.harga, m.stok 
    FROM keranjang k
    JOIN menu m ON k.id_menu = m.id
    WHERE k.id_customer = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>
    
    <?php if (count($cart_items) > 0): ?>
    <form id="checkoutForm" action="payment.php" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-3 px-4 text-left">Menu</th>
                            <th class="py-3 px-4 text-center">Price</th>
                            <th class="py-3 px-4 text-center">Quantity</th>
                            <th class="py-3 px-4 text-center">Subtotal</th>
                            <th class="py-3 px-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <?php foreach ($cart_items as $item): ?>
                        <tr class="border-b" data-cart-id="<?= $item['id'] ?>">
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <img src="/kwu/assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($item['nama']) ?>" 
                                         class="w-16 h-16 object-cover rounded-md mr-4">
                                    <span><?= htmlspecialchars($item['nama']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center">
                                    <input type="number" 
                                           value="<?= $item['jumlah'] ?>" 
                                           min="1" 
                                           max="<?= $item['stok'] ?>" 
                                           class="w-16 px-2 py-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                           onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center subtotal">
                                Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <button type="button"
                                        onclick="removeItem(<?= $item['id'] ?>)"
                                        class="text-red-600 hover:text-red-800"
                                        title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mb-4">
                <a href="/kwu/customer/menu.php" class="text-orange-600 hover:underline">
                    <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                </a>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                <h3 class="text-lg font-semibold mb-4">Delivery Information</h3>
                <div class="space-y-4">
                    <div>
                        <label for="nama_pemesan" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemesan</label>
                        <input type="text" 
                               id="nama_pemesan" 
                               name="nama_pemesan"
                               class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="Enter your name"
                               required>
                    </div>
                    <div>
                        <label for="alamat_pemesan" class="block text-sm font-medium text-gray-700 mb-1">Alamat Pengiriman</label>
                        <textarea id="alamat_pemesan" 
                                  name="alamat_pemesan"
                                  rows="3" 
                                  class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                  placeholder="Enter your delivery address"
                                  required></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-semibold">Total:</span>
                    <span class="text-xl font-bold text-orange-600 total-price">
                        Rp <?= number_format($total, 0, ',', '.') ?>
                    </span>
                </div>
                <input type="hidden" name="preview_order" value="1">
                <button type="submit"
                        class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition">
                    Review Order
                </button>
            </div>
        </div>
    </form>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <p class="text-gray-500 mb-4">Your cart is empty</p>
        <a href="/kwu/customer/menu.php" class="text-orange-600 hover:underline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Menu
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function removeItem(cartId) {
    Swal.fire({
        title: 'Remove Item',
        text: "Are you sure you want to remove this item?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`cart_actions.php?action=remove&id=${cartId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            updateCartDisplay(data);
                        }, 300);
                    }
                } else {
                    throw new Error(data.message || 'Failed to remove item');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        }
    });
}

function updateQuantity(cartId, quantity) {
    fetch(`cart_actions.php?action=update&id=${cartId}&quantity=${quantity}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
        } else {
            throw new Error(data.message || 'Failed to update quantity');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    });
}

function updateCartDisplay(data) {
    // Update cart badge
    const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
    if (cartBadge) {
        if (data.itemCount > 0) {
            cartBadge.textContent = data.itemCount;
        } else {
            cartBadge.remove();
        }
    }

    // Update total price
    const totalElement = document.querySelector('.total-price');
    if (totalElement) {
        totalElement.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(data.newTotal)}`;
    }

    // Reload if cart is empty
    if (data.isEmpty) {
        setTimeout(() => window.location.reload(), 1000);
    }
}

// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const nama = document.getElementById('nama_pemesan').value.trim();
    const alamat = document.getElementById('alamat_pemesan').value.trim();
    
    if (!nama || !alamat) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Required Information',
            text: 'Please fill in both name and delivery address'
        });
    }
});
</script>

<style>
tr[data-cart-id] {
    transition: opacity 0.3s ease-out;
}
</style>

<?php require_once '../includes/footer.php'; ?>