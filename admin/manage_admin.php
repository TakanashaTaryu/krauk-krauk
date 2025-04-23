<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Handle admin status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $user_id = (int)$_GET['toggle'];
    
    // Prevent changing own admin status
    if ($user_id === $_SESSION['user_id']) {
        setAlert('error', 'You cannot change your own admin status');
        redirect('manage_admin.php');
    }
    
    // Get current admin status
    $stmt = $pdo->prepare("SELECT admin_value FROM akun WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setAlert('error', 'User not found');
    } else {
        try {
            // Toggle admin status
            $new_status = $user['admin_value'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE akun SET admin_value = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            $status_text = $new_status ? 'enabled' : 'disabled';
            setAlert('success', "Admin privileges {$status_text} successfully");
            redirect('manage_admin.php');
        } catch (Exception $e) {
            setAlert('error', 'Error: ' . $e->getMessage());
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT id, email, admin_value, created_at, no_telp FROM akun ORDER BY admin_value DESC, created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Admin Privileges</h1>
        <a href="dashboard.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- User List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">User Accounts</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['admin_value'] ? 'bg-orange-50' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?= $user['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['no_telp'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('d M Y H:i', strtotime($user['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['admin_value']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Customer
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <a href="manage_admin.php?toggle=<?= $user['id'] ?>" 
                                           class="<?= $user['admin_value'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' ?>"
                                           onclick="return confirm('Are you sure you want to <?= $user['admin_value'] ? 'remove' : 'grant' ?> admin privileges for this user?')">
                                            <?php if ($user['admin_value']): ?>
                                                <i class="fas fa-user-minus"></i> Disable Admin
                                            <?php else: ?>
                                                <i class="fas fa-user-plus"></i> Enable Admin
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400"><i class="fas fa-user-check"></i> Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No user accounts found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>