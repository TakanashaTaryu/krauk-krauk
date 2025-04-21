<?php
// auth/logout.php
session_start();
require_once '../config/database.php';

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
redirect('/kwu/auth/login.php');
?>
