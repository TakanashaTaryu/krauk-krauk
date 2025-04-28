<?php
require_once '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama = clean($_POST['nama']);
                $deskripsi = clean($_POST['deskripsi']);
                $stok = (int)$_POST['stok'];
                $harga = (float)$_POST['harga'];
                
                // Handle image upload
                $gambar = '';
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
                    $target_dir = "../assets/images/menu/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        $gambar = $new_filename;
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO menu (nama, deskripsi, gambar, stok, harga) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$nama, $deskripsi, $gambar, $stok, $harga])) {
                    $menu_id = $pdo->lastInsertId();
                    
                    // Handle add-ons if any
                    if (isset($_POST['addon_names']) && is_array($_POST['addon_names'])) {
                        $addon_names = $_POST['addon_names'];
                        $addon_prices = $_POST['addon_prices'];
                        
                        for ($i = 0; $i < count($addon_names); $i++) {
                            if (!empty($addon_names[$i]) && isset($addon_prices[$i])) {
                                $addon_name = clean($addon_names[$i]);
                                $addon_price = (float)$addon_prices[$i];
                                
                                $stmt = $pdo->prepare("INSERT INTO menu_add_ons (id_menu, nama, harga) VALUES (?, ?, ?)");
                                $stmt->execute([$menu_id, $addon_name, $addon_price]);
                            }
                        }
                    }
                    
                    setAlert('success', 'Menu item added successfully');
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nama = clean($_POST['nama']);
                $deskripsi = clean($_POST['deskripsi']);
                $stok = (int)$_POST['stok'];
                $harga = (float)$_POST['harga'];
                
                // Handle image upload if new image is provided
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
                    $target_dir = "../assets/images/menu/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        // Delete old image
                        $stmt = $pdo->prepare("SELECT gambar FROM menu WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_image = $stmt->fetch()['gambar'];
                        if ($old_image && file_exists($target_dir . $old_image)) {
                            unlink($target_dir . $old_image);
                        }
                        
                        // Update with new image
                        $stmt = $pdo->prepare("UPDATE menu SET nama = ?, deskripsi = ?, gambar = ?, stok = ?, harga = ? WHERE id = ?");
                        $stmt->execute([$nama, $deskripsi, $new_filename, $stok, $harga, $id]);
                    }
                } else {
                    // Update without changing image
                    $stmt = $pdo->prepare("UPDATE menu SET nama = ?, deskripsi = ?, stok = ?, harga = ? WHERE id = ?");
                    $stmt->execute([$nama, $deskripsi, $stok, $harga, $id]);
                }
                
                // Handle existing add-ons (update or delete)
                if (isset($_POST['existing_addon_ids']) && is_array($_POST['existing_addon_ids'])) {
                    $existing_ids = $_POST['existing_addon_ids'];
                    $existing_names = $_POST['existing_addon_names'];
                    $existing_prices = $_POST['existing_addon_prices'];
                    
                    for ($i = 0; $i < count($existing_ids); $i++) {
                        $addon_id = (int)$existing_ids[$i];
                        
                        if (isset($_POST['delete_addon_' . $addon_id]) && $_POST['delete_addon_' . $addon_id] == 1) {
                            // Delete this add-on
                            $stmt = $pdo->prepare("DELETE FROM menu_add_ons WHERE id = ?");
                            $stmt->execute([$addon_id]);
                        } else {
                            // Update this add-on
                            $addon_name = clean($existing_names[$i]);
                            $addon_price = (float)$existing_prices[$i];
                            
                            $stmt = $pdo->prepare("UPDATE menu_add_ons SET nama = ?, harga = ? WHERE id = ?");
                            $stmt->execute([$addon_name, $addon_price, $addon_id]);
                        }
                    }
                }
                
                // Handle new add-ons
                if (isset($_POST['addon_names']) && is_array($_POST['addon_names'])) {
                    $addon_names = $_POST['addon_names'];
                    $addon_prices = $_POST['addon_prices'];
                    
                    for ($i = 0; $i < count($addon_names); $i++) {
                        if (!empty($addon_names[$i]) && isset($addon_prices[$i])) {
                            $addon_name = clean($addon_names[$i]);
                            $addon_price = (float)$addon_prices[$i];
                            
                            $stmt = $pdo->prepare("INSERT INTO menu_add_ons (id_menu, nama, harga) VALUES (?, ?, ?)");
                            $stmt->execute([$id, $addon_name, $addon_price]);
                        }
                    }
                }
                
                setAlert('success', 'Menu item updated successfully');
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Delete image file
                $stmt = $pdo->prepare("SELECT gambar FROM menu WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetch()['gambar'];
                if ($image && file_exists("../assets/images/menu/" . $image)) {
                    unlink("../assets/images/menu/" . $image);
                }
                
                // Delete menu item (add-ons will be deleted automatically due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
                $stmt->execute([$id]);
                setAlert('success', 'Menu item deleted successfully');
                break;
        }
    }
}

// Get all menu items
$stmt = $pdo->query("SELECT * FROM menu ORDER BY nama");
$menu_items = $stmt->fetchAll();

// Get all add-ons for each menu item
$menu_addons = [];
foreach ($menu_items as $item) {
    $stmt = $pdo->prepare("SELECT * FROM menu_add_ons WHERE id_menu = ? ORDER BY nama");
    $stmt->execute([$item['id']]);
    $menu_addons[$item['id']] = $stmt->fetchAll();
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Menu</h1>
        <button onclick="showAddModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            Tambahkan item baru
        </button>
    </div>
    
    <!-- Modify the menu item display section to include "Stock Terjual" -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($menu_items as $item): ?>
        <?php
        // Get sold stock count for this menu item
        $stmt = $pdo->prepare("SELECT SUM(jumlah) as total_sold FROM pesanan_detail WHERE id_menu = ?");
        $stmt->execute([$item['id']]);
        $sold_data = $stmt->fetch();
        $stock_terjual = $sold_data['total_sold'] ? (int)$sold_data['total_sold'] : 0;
        ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <img src="../assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" 
                 alt="<?= htmlspecialchars($item['nama']) ?>"
                 class="w-full h-48 object-cover">
            
            <div class="p-4">
                <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($item['nama']) ?></h3>
                <p class="text-gray-600 mb-2"><?= htmlspecialchars($item['deskripsi']) ?></p>
                <div class="flex justify-between mb-2">
                    <p>Stock: <?= $item['stok'] ?></p>
                    <p class="text-green-600">Stock Terjual: <?= $stock_terjual ?></p>
                </div>
                <p class="mb-4">Harga: Rp<?= number_format($item['harga'], 0, ',', '.') ?></p>
                
                <!-- Add-ons section -->
                <div class="mb-4">
                    <h4 class="font-bold mb-2">Tambahan:</h4>
                    <?php if (isset($menu_addons[$item['id']]) && count($menu_addons[$item['id']]) > 0): ?>
                        <ul class="list-disc pl-5 mb-2">
                            <?php foreach ($menu_addons[$item['id']] as $addon): ?>
                                <li class="flex justify-between">
                                    <span><?= htmlspecialchars($addon['nama']) ?></span>
                                    <span>Rp<?= number_format($addon['harga'], 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500 italic mb-2">Tidak ada tambahan</p>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="showEditModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1">
                        Ubah
                    </button>
                    <button onclick="deleteItem(<?= $item['id'] ?>)"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex-1">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6 overflow-y-auto max-h-screen">
        <h2 class="text-2xl font-bold mb-4">Tambahkan Menu Baru</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Nama</label>
                <input type="text" name="nama" required
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Deskripsi</label>
                <textarea name="deskripsi" required
                          class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Gambar</label>
                <input type="file" name="gambar" required accept="image/*"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Stok</label>
                <input type="number" name="stok" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Harga</label>
                <input type="number" name="harga" required min="0" step="0.01"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <!-- Add-ons section -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Tambahan</label>
                <div id="addons-container">
                    <!-- Add-on items will be added here -->
                </div>
                <button type="button" onclick="addNewAddon('addons-container')"
                        class="mt-2 bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 text-sm">
                    + Tambah Tambahan
                </button>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideAddModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Batal
                </button>
                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Tambah Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6 overflow-y-auto max-h-screen">
        <h2 class="text-2xl font-bold mb-4">Edit Menu Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Nama</label>
                <input type="text" name="nama" id="edit_nama" required
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="edit_deskripsi" required
                          class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Gambar</label>
                <input type="file" name="gambar" accept="image/*"
                       class="w-full px-3 py-2 border rounded">
                <small class="text-gray-500">Biarkan kosong jika ingin menggunakan gambar sebelumnya</small>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Stok</label>
                <input type="number" name="stok" id="edit_stok" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Harga</label>
                <input type="number" name="harga" id="edit_harga" required min="0" step="0.01"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <!-- Existing Add-ons section -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Tambahan Sebelumnya</label>
                <div id="existing-addons-container">
                    <!-- Existing add-ons will be loaded here -->
                </div>
            </div>
            
            <!-- New Add-ons section -->
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Tambahkan Tabahan Baru</label>
                <div id="edit-addons-container">
                    <!-- New add-on items will be added here -->
                </div>
                <button type="button" onclick="addNewAddon('edit-addons-container')"
                        class="mt-2 bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 text-sm">
                    + Tambah Tambahan
                </button>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideEditModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Batal
                </button>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Update Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    // Clear any existing add-ons
    document.getElementById('addons-container').innerHTML = '';
    document.getElementById('addModal').classList.remove('hidden');
}

function hideAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function showEditModal(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_nama').value = item.nama;
    document.getElementById('edit_deskripsi').value = item.deskripsi;
    document.getElementById('edit_stok').value = item.stok;
    document.getElementById('edit_harga').value = item.harga;
    
    // Clear any existing add-ons
    document.getElementById('existing-addons-container').innerHTML = '';
    document.getElementById('edit-addons-container').innerHTML = '';
    
    // Load existing add-ons
    loadExistingAddons(item.id);
    
    document.getElementById('editModal').classList.remove('hidden');
}

function hideEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Add-on related functions
function addNewAddon(containerId) {
    const container = document.getElementById(containerId);
    const addonIndex = container.children.length;
    
    const addonHtml = `
        <div class="flex items-center space-x-2 mb-2 addon-item">
            <input type="text" name="addon_names[]" placeholder="Add-on name" required
                   class="flex-1 px-3 py-2 border rounded">
            <input type="number" name="addon_prices[]" placeholder="Price" required min="0" step="0.01"
                   class="w-24 px-3 py-2 border rounded">
            <button type="button" onclick="removeAddon(this)"
                    class="bg-red-600 text-white px-2 py-2 rounded hover:bg-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', addonHtml);
}

function removeAddon(button) {
    const addonItem = button.closest('.addon-item');
    addonItem.remove();
}

function loadExistingAddons(menuId) {
    // Use AJAX to get the add-ons for this menu item
    fetch(`get_addons.php?menu_id=${menuId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(addons => {
            const container = document.getElementById('existing-addons-container');
            
            if (addons.length === 0) {
                container.innerHTML = '<p class="text-gray-500 italic">No add-ons available</p>';
                return;
            }
            
            addons.forEach(addon => {
                const addonHtml = `
                    <div class="flex items-center space-x-2 mb-2 addon-item">
                        <input type="hidden" name="existing_addon_ids[]" value="${addon.id}">
                        <input type="text" name="existing_addon_names[]" value="${addon.nama}" required
                               class="flex-1 px-3 py-2 border rounded">
                        <input type="number" name="existing_addon_prices[]" value="${addon.harga}" required min="0" step="0.01"
                               class="w-24 px-3 py-2 border rounded">
                        <div class="flex items-center">
                            <input type="checkbox" id="delete_addon_${addon.id}" name="delete_addon_${addon.id}" value="1" 
                                   class="mr-1">
                            <label for="delete_addon_${addon.id}" class="text-sm text-red-600">Delete</label>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', addonHtml);
            });
        })
        .catch(error => {
            console.error('Error loading add-ons:', error);
            document.getElementById('existing-addons-container').innerHTML = 
                '<p class="text-red-500">Error loading add-ons: ' + error.message + '</p>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>