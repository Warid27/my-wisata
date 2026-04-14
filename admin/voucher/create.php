<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Tambah Voucher';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_voucher = strtoupper(sanitize($_POST['kode_voucher'] ?? ''));
    $potongan = (int)($_POST['potongan'] ?? 0);
    $kuota = (int)($_POST['kuota'] ?? 0);
    $status = $_POST['status'] ?? 'aktif';
    
    // Validation
    $errors = [];
    
    if (empty($kode_voucher)) {
        $errors[] = 'Kode voucher harus diisi';
    } elseif (strlen($kode_voucher) < 3) {
        $errors[] = 'Kode voucher minimal 3 karakter';
    }
    
    // Check if voucher code already exists
    if (empty($errors)) {
        $query = "SELECT id_voucher FROM voucher WHERE kode_voucher = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$kode_voucher]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Kode voucher sudah ada';
        }
    }
    
    if ($potongan <= 0) {
        $errors[] = 'Potongan harus lebih dari 0';
    }
    
    if ($kuota <= 0) {
        $errors[] = 'Kuota harus lebih dari 0';
    }
    
    if (!in_array($status, ['aktif', 'nonaktif'])) {
        $errors[] = 'Status tidak valid';
    }
    
    // Insert voucher if no errors
    if (empty($errors)) {
        $query = "INSERT INTO voucher (kode_voucher, potongan, kuota, status) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$kode_voucher, $potongan, $kuota, $status])) {
            set_flash_message('success', 'Voucher berhasil ditambahkan');
            redirect('admin/voucher/');
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
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Tambah Voucher</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="<?php echo base_url('admin/voucher/'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Voucher -->
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kode_voucher" class="form-label">Kode Voucher *</label>
                                <input type="text" class="form-control" id="kode_voucher" name="kode_voucher" 
                                       value="<?php echo isset($_POST['kode_voucher']) ? htmlspecialchars($_POST['kode_voucher']) : ''; ?>" 
                                       style="text-transform: uppercase;" 
                                       placeholder="Contoh: DISCOUNT50" required>
                                <div class="form-text">Akan otomatis diubah menjadi huruf kapital</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="aktif" <?php echo (isset($_POST['status']) && $_POST['status'] === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?php echo (isset($_POST['status']) && $_POST['status'] === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="potongan" class="form-label">Potongan Harga *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="potongan" name="potongan" 
                                               value="<?php echo isset($_POST['potongan']) ? htmlspecialchars($_POST['potongan']) : ''; ?>" 
                                               min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kuota" class="form-label">Kuota *</label>
                                    <input type="number" class="form-control" id="kuota" name="kuota" 
                                           value="<?php echo isset($_POST['kuota']) ? htmlspecialchars($_POST['kuota']) : ''; ?>" 
                                           min="1" required>
                                    <div class="form-text">Jumlah penggunaan voucher</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Info:</strong> Voucher akan otomatis nonaktif jika kuota habis
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/voucher/'); ?>" class="btn btn-secondary me-2">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>
