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