<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Edit User';

// Get user ID
$id_user = $_GET['id'] ?? 0;
if (!is_numeric($id_user)) {
    set_flash_message('error', 'ID user tidak valid');
    redirect('admin/users/');
}

// Get user data
$query = "SELECT * FROM users WHERE id_user = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    set_flash_message('error', 'User tidak ditemukan');
    redirect('admin/users/');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    // Validation
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = 'Nama harus diisi';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    } else {
        // Check if email already exists (excluding current user)
        $query = "SELECT id_user FROM users WHERE email = ? AND id_user != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $id_user]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar';
        }
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok';
        }
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = 'Role tidak valid';
    }
    
    // Update user if no errors
    if (empty($errors)) {
        // Build update query
        $query = "UPDATE users SET nama = ?, email = ?, role = ?";
        $params = [$nama, $email, $role];
        
        // Add password if provided
        if (!empty($password)) {
            $query .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE id_user = ?";
        $params[] = $id_user;
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            set_flash_message('success', 'User berhasil diperbarui');
            redirect('admin/users/');
        } else {
            $errors[] = 'Terjadi kesalahan. Silakan coba lagi';
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        // Update user data with submitted values
        $user['nama'] = $_POST['nama'];
        $user['email'] = $_POST['email'];
        $user['role'] = $_POST['role'];
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit User</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo base_url('admin/users/'); ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Form User -->
                <div class="card shadow">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nama" class="form-label">Nama Lengkap *</label>
                                        <input type="text" class="form-control" id="nama" name="nama" 
                                               value="<?php echo htmlspecialchars($user['nama']); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Kosongkan jika tidak diubah" minlength="6">
                                        <div class="form-text">Minimal 6 karakter (kosongkan jika tidak ingin mengubah)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" placeholder="Konfirmasi password baru">
                                    </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" placeholder="Konfirmasi password baru">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Terdaftar</label>
                                    <input type="text" class="form-control" value="<?php echo format_date($user['created_at'], 'd M Y H:i'); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ID User</label>
                                    <input type="text" class="form-control" value="<?php echo $user['id_user']; ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($user['id_user'] == get_user_id()): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Perhatian:</strong> Anda sedang mengedit akun Anda sendiri.
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/users/'); ?>" class="btn btn-secondary me-2">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>
