<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    setAlert('error', 'Please login first');
    redirect('../auth/login.php');
}

if (isAdmin()) {
    setAlert('error', 'Access denied');
    redirect('../admin/dashboard.php');
}

// Get cart items with menu details
$stmt = $pdo->prepare("
    SELECT k.id, k.jumlah, m.id as menu_id, m.nama, m.gambar, m.harga, m.stok 
    FROM keranjang k
    JOIN menu m ON k.id_menu = m.id
    WHERE k.id_customer = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Get add-ons for each menu item
$menu_addons = [];
foreach ($cart_items as $item) {
    $stmt = $pdo->prepare("
        SELECT * FROM menu_add_ons 
        WHERE id_menu = ? 
        ORDER BY nama
    ");
    $stmt->execute([$item['menu_id']]);
    $menu_addons[$item['menu_id']] = $stmt->fetchAll();
}

// Get selected add-ons for each cart item
$selected_addons = [];
foreach ($cart_items as $item) {
    $stmt = $pdo->prepare("
        SELECT id_add_on FROM keranjang_add_ons 
        WHERE id_keranjang = ?
    ");
    $stmt->execute([$item['id']]);
    $selected_addons[$item['id']] = array_column($stmt->fetchAll(), 'id_add_on');
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $item_total = $item['harga'] * $item['jumlah'];
    
    // Add selected add-ons price
    if (isset($selected_addons[$item['id']]) && !empty($selected_addons[$item['id']])) {
        foreach ($selected_addons[$item['id']] as $addon_id) {
            foreach ($menu_addons[$item['menu_id']] as $addon) {
                if ($addon['id'] == $addon_id) {
                    $item_total += $addon['harga'] * $item['jumlah'];
                    break;
                }
            }
        }
    }
    
    $total += $item_total;
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Your Cart</h1>
    
    <!-- Pre-order Notice -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-md">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Pre-Order Information</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>All items are made to order and will be processed within 1-3 days after payment confirmation.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($cart_items) > 0): ?>
    <form id="checkoutForm" action="payment.php" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <!-- Desktop view table (hidden on mobile) -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8 hidden md:block">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-3 px-4 text-left">Menu</th>
                            <th class="py-3 px-4 text-center">Price</th>
                            <th class="py-3 px-4 text-center">Quantity</th>
                            <th class="py-3 px-4 text-center">Add-ons</th>
                            <th class="py-3 px-4 text-center">Subtotal</th>
                            <th class="py-3 px-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <?php foreach ($cart_items as $item): ?>
                        <tr class="border-b" data-cart-id="<?= $item['id'] ?>">
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <img src="../assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" 
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
                            <td class="py-4 px-4">
                                <?php if (!empty($menu_addons[$item['menu_id']])): ?>
                                <div class="flex flex-col items-start">
                                    <?php foreach ($menu_addons[$item['menu_id']] as $addon): ?>
                                    <div class="flex items-center mb-1">
                                        <input type="checkbox" 
                                               id="addon_<?= $item['id'] ?>_<?= $addon['id'] ?>" 
                                               name="addons[<?= $item['id'] ?>][]" 
                                               value="<?= $addon['id'] ?>"
                                               <?= in_array($addon['id'], $selected_addons[$item['id']] ?? []) ? 'checked' : '' ?>
                                               onchange="updateAddon(<?= $item['id'] ?>, <?= $addon['id'] ?>, this.checked)">
                                        <label for="addon_<?= $item['id'] ?>_<?= $addon['id'] ?>" class="ml-2 text-sm">
                                            <?= htmlspecialchars($addon['nama']) ?> (+Rp <?= number_format($addon['harga'], 0, ',', '.') ?>)
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-500 text-sm">No add-ons available</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center subtotal" data-item-id="<?= $item['id'] ?>">
                                <?php
                                $item_subtotal = $item['harga'] * $item['jumlah'];
                                if (isset($selected_addons[$item['id']]) && !empty($selected_addons[$item['id']])) {
                                    foreach ($selected_addons[$item['id']] as $addon_id) {
                                        foreach ($menu_addons[$item['menu_id']] as $addon) {
                                            if ($addon['id'] == $addon_id) {
                                                $item_subtotal += $addon['harga'] * $item['jumlah'];
                                                break;
                                            }
                                        }
                                    }
                                }
                                ?>
                                Rp <?= number_format($item_subtotal, 0, ',', '.') ?>
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
            
            <!-- Mobile view cards (visible only on mobile) -->
            <div class="md:hidden space-y-4">
                <?php foreach ($cart_items as $item): ?>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-orange-500" data-cart-id="<?= $item['id'] ?>">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <img src="../assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" 
                                 alt="<?= htmlspecialchars($item['nama']) ?>" 
                                 class="w-16 h-16 object-cover rounded-md mr-3">
                            <div>
                                <h3 class="font-medium"><?= htmlspecialchars($item['nama']) ?></h3>
                                <p class="text-gray-600">Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                        <button type="button"
                                onclick="removeItem(<?= $item['id'] ?>)"
                                class="text-red-600 hover:text-red-800 p-2"
                                title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    
                    <!-- Add-ons section for mobile -->
                    <?php if (!empty($menu_addons[$item['menu_id']])): ?>
                    <div class="mb-3">
                        <h4 class="font-medium text-sm mb-1">Add-ons:</h4>
                        <div class="pl-2">
                            <?php foreach ($menu_addons[$item['menu_id']] as $addon): ?>
                            <div class="flex items-center mb-1">
                                <input type="checkbox" 
                                       id="mobile_addon_<?= $item['id'] ?>_<?= $addon['id'] ?>" 
                                       name="addons[<?= $item['id'] ?>][]" 
                                       value="<?= $addon['id'] ?>"
                                       <?= in_array($addon['id'], $selected_addons[$item['id']] ?? []) ? 'checked' : '' ?>
                                       onchange="updateAddon(<?= $item['id'] ?>, <?= $addon['id'] ?>, this.checked)">
                                <label for="mobile_addon_<?= $item['id'] ?>_<?= $addon['id'] ?>" class="ml-2 text-sm">
                                    <?= htmlspecialchars($addon['nama']) ?> (+Rp <?= number_format($addon['harga'], 0, ',', '.') ?>)
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <label class="mr-2 text-sm text-gray-600">Quantity:</label>
                            <input type="number" 
                                   value="<?= $item['jumlah'] ?>" 
                                   min="1" 
                                   max="<?= $item['stok'] ?>" 
                                   class="w-16 px-2 py-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                   onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                        </div>
                        <div class="subtotal font-medium" data-item-id="<?= $item['id'] ?>">
                            <?php
                            $item_subtotal = $item['harga'] * $item['jumlah'];
                            if (isset($selected_addons[$item['id']]) && !empty($selected_addons[$item['id']])) {
                                foreach ($selected_addons[$item['id']] as $addon_id) {
                                    foreach ($menu_addons[$item['menu_id']] as $addon) {
                                        if ($addon['id'] == $addon_id) {
                                            $item_subtotal += $addon['harga'] * $item['jumlah'];
                                            break;
                                        }
                                    }
                                }
                            }
                            ?>
                            Rp <?= number_format($item_subtotal, 0, ',', '.') ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <br>
            
            <div class="mb-4">
                <a href="../customer/menu.php" class="text-orange-600 hover:underline inline-flex items-center">
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
                    
                    <!-- Add notes field -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Special Instructions (Optional)</label>
                        <textarea id="notes" 
                                  name="notes"
                                  rows="2" 
                                  class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                  placeholder="E.g., No spicy food, house with green gate, etc."></textarea>
                    </div>
                    
                    <!-- Location picker -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Lokasi Pengiriman</label>
                        <div id="map" class="w-full h-64 rounded-md border mb-2"></div>
                        <p class="text-sm text-gray-500 mb-2">Geser pin untuk menentukan lokasi yang tepat</p>
                        <input type="hidden" id="latitude" name="latitude" required>
                        <input type="hidden" id="longitude" name="longitude" required>
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
        <a href="../customer/menu.php" class="text-orange-600 hover:underline inline-flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Menu
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Leaflet CSS and JS (Open Source Maps) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
                    // Remove item from DOM
                    document.querySelectorAll(`[data-cart-id="${cartId}"]`).forEach(el => {
                        el.remove();
                    });
                    
                    // Update cart count and total
                    updateCartCountAndTotal(data.count, data.total);
                    
                    // If cart is empty, reload page to show empty cart message
                    if (data.count === 0) {
                        location.reload();
                    }
                    
                    Swal.fire('Removed!', data.message, 'success');
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
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
            // Update subtotal display
            document.querySelectorAll(`.subtotal[data-item-id="${cartId}"]`).forEach(el => {
                el.textContent = 'Rp ' + formatNumber(data.item_subtotal);
            });
            
            // Update cart count and total
            updateCartCountAndTotal(data.count, data.total);
        } else {
            Swal.fire('Error!', data.message, 'error');
            // Reset to previous quantity
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
    });
}

function updateAddon(cartId, addonId, checked) {
    fetch(`cart_actions.php?action=update_addon&id=${cartId}&addon_id=${addonId}&checked=${checked ? 1 : 0}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update subtotal display
            document.querySelectorAll(`.subtotal[data-item-id="${cartId}"]`).forEach(el => {
                el.textContent = 'Rp ' + formatNumber(data.item_subtotal);
            });
            
            // Update cart count and total
            updateCartCountAndTotal(data.count, data.total);
        } else {
            Swal.fire('Error!', data.message, 'error');
            // Reset checkbox state
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
    });
}

function updateCartCountAndTotal(count, total) {
    // Update cart count in navbar
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
    
    // Update total price
    const totalPriceElement = document.querySelector('.total-price');
    if (totalPriceElement) {
        totalPriceElement.textContent = 'Rp ' + formatNumber(total);
    }
}

function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

// Initialize map
document.addEventListener('DOMContentLoaded', function() {
    // Default to a central location in Indonesia if no coordinates are provided
    const map = L.map('map').setView([-6.2088, 106.8456], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add a draggable marker
    const marker = L.marker([-6.2088, 106.8456], {
        draggable: true
    }).addTo(map);
    
    // Update hidden inputs when marker is moved
    marker.on('dragend', function(event) {
        const position = marker.getLatLng();
        document.getElementById('latitude').value = position.lat;
        document.getElementById('longitude').value = position.lng;
    });
    
    // Set initial values
    document.getElementById('latitude').value = -6.2088;
    document.getElementById('longitude').value = 106.8456;
    
    // Try to get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Update map and marker
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
            
            // Update hidden inputs
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }, function(error) {
            console.log('Error getting location:', error.message);
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>