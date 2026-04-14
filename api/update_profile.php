<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($nama)) {
        echo json_encode(['success' => false, 'message' => 'Nama harus diisi']);
        exit;
    }
    
    try {
        // Update user name
        $query = "UPDATE users SET nama = ? WHERE id_user = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nama, get_user_id()]);
        
        // Update password if provided
        if (!empty($password)) {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
                exit;
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id_user = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$hashed_password, get_user_id()]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
