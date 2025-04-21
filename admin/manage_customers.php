<?php
require_once '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('/kwu/auth/login.php');
}

// Handle customer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $customer_id = (int)$_POST['customer_id'];
    
    // Don't allow deleting if customer has active orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pesanan WHERE id_customer = ?");
    $stmt->execute([$customer_id]);
    $has_orders = $stmt->fetch()['count'] > 0;
    
    if ($has_orders) {
        setAlert('error', 'Cannot delete customer with active orders');
    } else {
        $stmt = $pdo->prepare("DELETE FROM akun WHERE id = ? AND admin_value = 0");
        if ($stmt->execute([$customer_id])) {
            setAlert('success', 'Customer deleted successfully');
        }
    }
}

// Get all customers
$stmt = $pdo->query("SELECT * FROM akun WHERE admin_value = 0 ORDER BY email");
$customers = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Manage Customers</h1>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($customers as $customer): 
                    // Get order count for this customer
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pesanan WHERE id_customer = ?");
                    $stmt->execute([$customer['id']]);
                    $order_count = $stmt->fetch()['count'];
                ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $customer['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($customer['email']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $order_count ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button onclick="deleteCustomer(<?= $customer['id'] ?>, <?= $order_count ?>)"
                                class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteCustomer(id, orderCount) {
    if (orderCount > 0) {
        alert('Cannot delete customer with active orders');
        return;
    }
    
    if (confirm('Are you sure you want to delete this customer?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="customer_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>