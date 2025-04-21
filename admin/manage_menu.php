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
                
                // Delete menu item
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
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Menu</h1>
        <button onclick="showAddModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            Add New Item
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($menu_items as $item): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <img src="../assets/images/menu/<?= htmlspecialchars($item['gambar']) ?>" 
                 alt="<?= htmlspecialchars($item['nama']) ?>"
                 class="w-full h-48 object-cover">
            
            <div class="p-4">
                <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($item['nama']) ?></h3>
                <p class="text-gray-600 mb-2"><?= htmlspecialchars($item['deskripsi']) ?></p>
                <p class="mb-2">Stock: <?= $item['stok'] ?></p>
                <p class="mb-4">Price: Rp<?= number_format($item['harga'], 0, ',', '.') ?></p>
                
                <div class="flex space-x-2">
                    <button onclick="showEditModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1">
                        Edit
                    </button>
                    <button onclick="deleteItem(<?= $item['id'] ?>)"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex-1">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6">
        <h2 class="text-2xl font-bold mb-4">Add New Menu Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Name</label>
                <input type="text" name="nama" required
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Description</label>
                <textarea name="deskripsi" required
                          class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Image</label>
                <input type="file" name="gambar" required accept="image/*"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Stock</label>
                <input type="number" name="stok" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Price</label>
                <input type="number" name="harga" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideAddModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6">
        <h2 class="text-2xl font-bold mb-4">Edit Menu Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Name</label>
                <input type="text" name="nama" id="edit_nama" required
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Description</label>
                <textarea name="deskripsi" id="edit_deskripsi" required
                          class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Image</label>
                <input type="file" name="gambar" accept="image/*"
                       class="w-full px-3 py-2 border rounded">
                <small class="text-gray-500">Leave empty to keep current image</small>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Stock</label>
                <input type="number" name="stok" id="edit_stok" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Price</label>
                <input type="number" name="harga" id="edit_harga" required min="0"
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideEditModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Cancel
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
</script>

<?php require_once '../includes/footer.php'; ?>