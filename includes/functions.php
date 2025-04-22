<?php
/**
 * Helper functions for the application
 * 
 * Note: This file only contains functions that are not already defined in database.php
 */

// Only define these functions if they don't already exist
if (!function_exists('formatDate')) {
    // Format date
    function formatDate($date, $format = 'd M Y H:i') {
        return date($format, strtotime($date));
    }
}

if (!function_exists('formatCurrency')) {
    // Format currency
    function formatCurrency($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('isValidEmail')) {
    // Validate email
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('sanitizeInput')) {
    // Sanitize input
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input));
    }
}

if (!function_exists('isActivePage')) {
    // Check if current page matches the given path
    function isActivePage($path) {
        $current_page = basename($_SERVER['PHP_SELF']);
        return $current_page == $path;
    }
}

if (!function_exists('generateRandomString')) {
    // Generate a random string
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

if (!function_exists('getUserById')) {
    // Get user data by ID
    function getUserById($pdo, $user_id) {
        $stmt = $pdo->prepare("SELECT * FROM akun WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('isImage')) {
    // Check if a file is an image
    function isImage($file) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        return in_array($file['type'], $allowed_types);
    }
}

// Note: We're not redefining the following functions as they already exist in database.php:
// - redirect()
// - setAlert()
// - getAlert()
// - isLoggedIn()
// - isAdmin()
// - uploadImage()

/**
 * Upload and process an image
 * 
 * @param array $file The uploaded file data
 * @param string $target_dir The directory to save the file
 * @param bool $compress Whether to compress the image
 * @return array Result with success status and message
 */
function uploadImage($file, $target_dir, $compress = false) {
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check if file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Generate a unique filename
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $extension;
    $target_file = $target_dir . $filename;
    
    // Check file size (8MB limit)
    if ($file["size"] > 8 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 8MB.'];
    }
    
    // Allow only certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, & PNG files are allowed.'];
    }
    
    // If compression is enabled
    if ($compress && in_array($extension, ['jpg', 'jpeg', 'png'])) {
        // Create image resource based on file type
        if ($extension == 'png') {
            $image = imagecreatefrompng($file["tmp_name"]);
        } else {
            $image = imagecreatefromjpeg($file["tmp_name"]);
        }
        
        // Set compression quality (0-100 for JPG, 0-9 for PNG)
        if ($extension == 'png') {
            // PNG compression (0-9)
            imagepng($image, $target_file, 7); // Medium compression
        } else {
            // JPEG compression (0-100)
            imagejpeg($image, $target_file, 50); // 50% quality
        }
        
        // Free up memory
        imagedestroy($image);
    } else {
        // Move the uploaded file without compression
        if (!move_uploaded_file($file["tmp_name"], $target_file)) {
            return ['success' => false, 'message' => 'There was an error uploading your file.'];
        }
    }
    
    return ['success' => true, 'filename' => $filename, 'path' => $target_file];
}