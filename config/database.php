<?php
// config/database.php
$host = 'localhost';
$dbname = 'food_ordering_system';
$username = 'admin';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk membersihkan input
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk mengecek status login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk mengecek apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['admin_value']) && $_SESSION['admin_value'] == 1;
}

// Fungsi redirect - modified to handle headers already sent
function redirect($url) {
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit();
    } else {
        header("Location: $url");
        exit();
    }
}

// Fungsi untuk menampilkan alert
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fungsi untuk menampilkan alert
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Fungsi untuk upload gambar
function uploadImage($file, $directory) {
    $target_dir = $directory;
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Cek ekstensi file
    $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ["success" => false, "message" => "Format file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF."];
    }
    
    // Cek ukuran file (max 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "Ukuran file terlalu besar. Maksimal 5MB."];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $new_filename];
    } else {
        return ["success" => false, "message" => "Gagal mengupload file."];
    }
}