<?php


require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('../auth/login.php');
}

// Handle QRIS upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_qris'])) {
    $qris_code = clean($_POST['qris_code']);
    
    // Validate QRIS code format (basic validation)
    if (strlen($qris_code) < 10) {
        setAlert('error', 'Invalid QRIS code format');
    } else {
        // Store in database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('qris_static', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$qris_code, $qris_code]);
            
            setAlert('success', 'QRIS code updated successfully');
            redirect('manage_qris.php');
        } catch (Exception $e) {
            setAlert('error', 'Error: ' . $e->getMessage());
        }
    }
}

// Get current QRIS code
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'qris_static'");
$stmt->execute();
$qris_static = $stmt->fetch(PDO::FETCH_COLUMN) ?: '';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Pemyaran QRIS</h1>
        <a href="dashboard.php" class="text-orange-600 hover:underline inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Halaman Utama
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Konfigurasi QRIS</h2>
        
        <form action="manage_qris.php" method="POST">
            <div class="mb-4">
                <label for="qris_code" class="block text-sm font-medium text-gray-700 mb-1">Kode QRIS Statik</label>
                <textarea 
                    id="qris_code" 
                    name="qris_code" 
                    rows="4"
                    class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="Paste your static QRIS code here"
                    required
                ><?= htmlspecialchars($qris_static) ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Masukan kode full pembayaran QRIS statik</p>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" name="upload_qris" class="bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 transition">
                    Simpan konfigurasi QRIS
                </button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($qris_static)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">QRIS Preview</h2>
        
        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="text-center">
                <p class="mb-2 font-medium">Kode QRIS Statik</p>
                <div id="static-qr-container" class="bg-white p-4 inline-block rounded-lg border">
                    <!-- QR code will be generated here -->
                    <img id="static-qr-image" src="../assets/images/loading.gif" alt="Static QRIS Code" width="200" height="200">
                </div>
            </div>
            
            <div class="text-center">
                <p class="mb-2 font-medium">Preview QRIS Dinamis (Rp 10.000)</p>
                <div id="dynamic-qr-container" class="bg-white p-4 inline-block rounded-lg border">
                    <!-- Dynamic QR code will be generated here -->
                    <img id="dynamic-qr-image" src="../assets/images/loading.gif" alt="Dynamic QRIS Code" width="200" height="200">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- QR Code Generator Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($qris_static)): ?>
    // Generate static QR code using QRCode.js
    const staticQrCode = '<?= htmlspecialchars($qris_static) ?>';
    const staticQrImage = document.getElementById('static-qr-image');
    
    try {
        // Generate QR code
        const qrStatic = qrcode(0, 'M');
        qrStatic.addData(staticQrCode);
        qrStatic.make();
        
        // Set the image source
        staticQrImage.src = qrStatic.createDataURL(4, 0);
    } catch (error) {
        console.error('Error generating static QR code:', error);
        staticQrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(staticQrCode);
    }
    
    // Generate dynamic QR code for preview (10,000 IDR)
    const dynamicQrImage = document.getElementById('dynamic-qr-image');
    
    fetch('../includes/generate_dynamic_qris.php?amount=10000')
        .then(response => {
            // Check if response is OK before parsing JSON
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.qris_code) {
                try {
                    // Generate QR code
                    const qrDynamic = qrcode(0, 'M');
                    qrDynamic.addData(data.qris_code);
                    qrDynamic.make();
                    
                    // Set the image source
                    dynamicQrImage.src = qrDynamic.createDataURL(4, 0);
                } catch (error) {
                    console.error('Error generating dynamic QR code:', error);
                    dynamicQrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.qris_code);
                }
            } else {
                const dynamicQrContainer = document.getElementById('dynamic-qr-container');
                dynamicQrContainer.innerHTML = `<p class="text-red-500">${data.message || 'Failed to generate dynamic QRIS'}</p>`;
            }
        })
        .catch(error => {
            console.error('Error generating dynamic QRIS:', error);
            const dynamicQrContainer = document.getElementById('dynamic-qr-container');
            dynamicQrContainer.innerHTML = '<p class="text-red-500">Failed to generate dynamic QRIS</p>';
        });
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>