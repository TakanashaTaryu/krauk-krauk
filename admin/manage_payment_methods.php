<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new payment method
        if (isset($_POST['add_payment_method'])) {
            $name = clean($_POST['name']);
            $account_number = clean($_POST['account_number']);
            $account_name = clean($_POST['account_name']);
            $description = clean($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle logo upload if provided
            $logo = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $upload = uploadImage($_FILES['logo'], '../assets/images/payment/');
                if ($upload['success']) {
                    $logo = $upload['filename'];
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO payment_methods (name, account_number, account_name, description, logo, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $account_number, $account_name, $description, $logo, $is_active]);
            
            setAlert('success', 'Payment method added successfully');
            redirect('manage_payment_methods.php');
        }
        
        // Update payment method
        if (isset($_POST['update_payment_method'])) {
            $id = (int)$_POST['id'];
            $name = clean($_POST['name']);
            $account_number = clean($_POST['account_number']);
            $account_name = clean($_POST['account_name']);
            $description = clean($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle logo upload if provided
            $logo_sql = "";
            $params = [$name, $account_number, $account_name, $description, $is_active];
            
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $upload = uploadImage($_FILES['logo'], '../assets/images/payment/');
                if ($upload['success']) {
                    $logo_sql = ", logo = ?";
                    $params[] = $upload['filename'];
                    
                    // Delete old logo if exists
                    $stmt = $pdo->prepare("SELECT logo FROM payment_methods WHERE id = ?");
                    $stmt->execute([$id]);
                    $old_logo = $stmt->fetchColumn();
                    
                    if ($old_logo && file_exists("../assets/images/payment/$old_logo")) {
                        unlink("../assets/images/payment/$old_logo");
                    }
                }
            }
            
            $params[] = $id;
            
            $stmt = $pdo->prepare("
                UPDATE payment_methods 
                SET name = ?, account_number = ?, account_name = ?, description = ?, is_active = ? $logo_sql
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            setAlert('success', 'Payment method updated successfully');
            redirect('manage_payment_methods.php');
        }
        
        // Delete payment method
        if (isset($_POST['delete_payment_method'])) {
            $id = (int)$_POST['id'];
            
            // Check if the payment method is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE payment_method_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                setAlert('error', 'Cannot delete payment method that is in use by orders');
            } else {
                // Delete logo if exists
                $stmt = $pdo->prepare("SELECT logo FROM payment_methods WHERE id = ?");
                $stmt->execute([$id]);
                $logo = $stmt->fetchColumn();
                
                if ($logo && file_exists("../assets/images/payment/$logo")) {
                    unlink("../assets/images/payment/$logo");
                }
                
                $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
                $stmt->execute([$id]);
                
                setAlert('success', 'Payment method deleted successfully');
            }
            
            redirect('manage_payment_methods.php');
        }
        
    } catch (Exception $e) {
        setAlert('error', 'Error: ' . $e->getMessage());
    }
}

// Get all payment methods
$stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY name");
$payment_methods = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manage Payment Methods</h1>
        <button type="button" onclick="openAddModal()" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
            <i class="fas fa-plus mr-2"></i> Add Payment Method
        </button>
    </div>
    
    <!-- Payment Methods Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($payment_methods)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No payment methods found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($payment_methods as $method): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($method['logo']): ?>
                            <img src="../assets/images/payment/<?= htmlspecialchars($method['logo']) ?>" alt="<?= htmlspecialchars($method['name']) ?>" class="h-10 w-auto">
                            <?php else: ?>
                            <div class="bg-gray-200 h-10 w-10 flex items-center justify-center rounded">
                                <i class="fas fa-credit-card text-gray-500"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($method['name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($method['account_number']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($method['account_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $method['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $method['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($method)) ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" onclick="confirmDelete(<?= $method['id'] ?>, '<?= htmlspecialchars($method['name']) ?>')" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Payment Method Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Add Payment Method</h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input type="text" name="account_number" id="account_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="account_name" class="block text-sm font-medium text-gray-700">Account Name</label>
                    <input type="text" name="account_name" id="account_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50"></textarea>
                </div>
                <div class="mb-4">
                    <label for="logo" class="block text-sm font-medium text-gray-700">Logo (Optional)</label>
                    <input type="file" name="logo" id="logo" accept="image/*" class="mt-1 block w-full">
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" checked class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeAddModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" name="add_payment_method" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Add Payment Method</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Payment Method Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Edit Payment Method</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label for="edit_name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="edit_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="edit_account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input type="text" name="account_number" id="edit_account_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="edit_account_name" class="block text-sm font-medium text-gray-700">Account Name</label>
                    <input type="text" name="account_name" id="edit_account_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="edit_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50"></textarea>
                </div>
                <div class="mb-4">
                    <label for="edit_logo" class="block text-sm font-medium text-gray-700">Logo (Optional)</label>
                    <input type="file" name="logo" id="edit_logo" accept="image/*" class="mt-1 block w-full">
                    <div id="current_logo_container" class="mt-2 hidden">
                        <p class="text-sm text-gray-500">Current logo:</p>
                        <img id="current_logo" src="" alt="Current Logo" class="h-10 mt-1">
                    </div>
                </div>
                <div class="mb-4 flex items-center">
                    <input type="checkbox" name="is_active" id="edit_is_active" class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-500 focus:ring-opacity-50">
                    <label for="edit_is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" name="update_payment_method" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Update Payment Method</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete Payment Method</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500" id="delete_confirmation_text"></p>
                </div>
                <div class="flex justify-center mt-3">
                    <form action="" method="POST">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" onclick="closeDeleteModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="delete_payment_method" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add Modal Functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

// Edit Modal Functions
function openEditModal(method) {
    document.getElementById('edit_id').value = method.id;
    document.getElementById('edit_name').value = method.name;
    document.getElementById('edit_account_number').value = method.account_number;
    document.getElementById('edit_account_name').value = method.account_name;
    document.getElementById('edit_description').value = method.description;
    document.getElementById('edit_is_active').checked = method.is_active == 1;
    
    // Show current logo if exists
    if (method.logo) {
        document.getElementById('current_logo').src = '../assets/images/payment/' + method.logo;
        document.getElementById('current_logo_container').classList.remove('hidden');
    } else {
        document.getElementById('current_logo_container').classList.add('hidden');
    }
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Delete Modal Functions
function confirmDelete(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_confirmation_text').textContent = `Are you sure you want to delete the payment method "${name}"?`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    
    if (event.target === editModal) {
        closeEditModal();
    }
    
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>