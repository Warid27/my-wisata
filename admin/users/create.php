<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Tambah User';

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
        // Check if email already exists
        $query = "SELECT id_user FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = 'Role tidak valid';
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (nama, email, password, role, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nama, $email, $hashed_password, $role])) {
            set_flash_message('success', 'User berhasil ditambahkan');
            redirect('admin/users/');
        } else {
            $errors[] = 'Terjadi kesalahan. Silakan coba lagi';
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tambah User</h1>
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
                                           value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                    <div class="form-text">Minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user" <?php echo ($_POST['role'] ?? '') === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Info:</strong> User akan dapat login setelah dibuat. 
                            Password akan di-hash secara otomatis untuk keamanan.
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/users/'); ?>" class="btn btn-secondary me-2">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>
