<?php
// includes/functions.php

// Function to upload images
function uploadImage($file, $target_dir) {
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $target_file = $target_dir . $filename;
    
    // Check if file is an actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return [
            'success' => false,
            'message' => 'File is not an image.'
        ];
    }
    
    // Check file size (20MB limit)
    if ($file['size'] > 20 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is 20MB.'
        ];
    }
    
    // Allow certain file formats
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return [
            'success' => false,
            'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'
        ];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'message' => 'There was an error uploading your file.'
        ];
    }
}