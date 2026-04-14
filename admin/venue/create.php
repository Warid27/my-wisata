<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Tambah Venue';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_venue = sanitize($_POST['nama_venue'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    
    // Validation
    $errors = [];
    
    if (empty($nama_venue)) {
        $errors[] = 'Nama venue harus diisi';
    }
    
    if (empty($alamat)) {
        $errors[] = 'Alamat harus diisi';
    }
    
    if ($kapasitas <= 0) {
        $errors[] = 'Kapasitas harus lebih dari 0';
    }
    
    // Insert venue if no errors
    if (empty($errors)) {
        $query = "INSERT INTO venue (nama_venue, alamat, kapasitas) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nama_venue, $alamat, $kapasitas])) {
            set_flash_message('success', 'Venue berhasil ditambahkan');
            redirect('admin/venue/');
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
            <h1 class="h2">Tambah Venue</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="<?php echo base_url('admin/venue/'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Venue -->
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_venue" class="form-label">Nama Venue *</label>
                                <input type="text" class="form-control" id="nama_venue" name="nama_venue" 
                                       value="<?php echo isset($_POST['nama_venue']) ? htmlspecialchars($_POST['nama_venue']) : ''; ?>" 
                                       required>
                            </div>
                                    <label for="kapasitas" class="form-label">Kapasitas *</label>
                                    <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                           value="<?php echo isset($_POST['kapasitas']) ? htmlspecialchars($_POST['kapasitas']) : ''; ?>" 
                                           min="1" required>
                                    <div class="form-text">Jumlah maksimal orang</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat *</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/venue/'); ?>" class="btn btn-secondary me-2">
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
