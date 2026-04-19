<?php
// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

// Check role-based access
function require_admin() {
    if (!is_admin()) {
        set_flash_message('error', 'Akses ditolak. Anda bukan admin');
        redirect('user/');
    }
}

function require_staff() {
    if (!is_staff()) {
        set_flash_message('error', 'Akses ditolak. Anda bukan staff');
        redirect('user/');
    }
}

function require_user() {
    if (is_admin()) {
        set_flash_message('info', 'Admin tidak dapat mengakses halaman user');
        redirect('admin/');
    }
}
?>
