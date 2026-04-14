<?php
require_once __DIR__ . '/config/config.php';

// Destroy all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
set_flash_message('success', 'Anda telah berhasil logout');
redirect('user/login.php');
?>
