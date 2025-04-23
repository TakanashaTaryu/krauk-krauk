<?php
// Turn off all error reporting to prevent HTML in JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start session before requiring files that might use session variables
session_start();

// Include required files
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['menu_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Menu ID is required']);
    exit;
}

$menu_id = (int)$_GET['menu_id'];

try {
    // Check if the menu_add_ons table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'menu_add_ons'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $pdo->exec("CREATE TABLE `menu_add_ons` (
            `id` int NOT NULL AUTO_INCREMENT,
            `id_menu` int NOT NULL,
            `nama` varchar(255) NOT NULL,
            `harga` decimal(10,2) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `id_menu` (`id_menu`),
            CONSTRAINT `menu_add_ons_ibfk_1` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE
        )");
    }
    
    $stmt = $pdo->prepare("SELECT * FROM menu_add_ons WHERE id_menu = ? ORDER BY nama");
    $stmt->execute([$menu_id]);
    $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($addons);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>