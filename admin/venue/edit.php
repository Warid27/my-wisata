<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Edit Venue';

// Get venue ID
$id_venue = $_GET['id'] ?? 0;
if (!is_numeric($id_venue)) {
    set_flash_message('error', 'ID venue tidak valid');
    redirect('admin/venue/');
}

// Get venue data
$query = "SELECT * FROM venue WHERE id_venue = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_venue]);
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venue) {
    set_flash_message('error', 'Venue tidak ditemukan');
    redirect('admin/venue/');
}

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
    
    // Update venue if no errors
    if (empty($errors)) {
        $query = "UPDATE venue SET nama_venue = ?, alamat = ?, kapasitas = ? WHERE id_venue = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nama_venue, $alamat, $kapasitas, $id_venue])) {
            set_flash_message('success', 'Venue berhasil diperbarui');
            redirect('admin/venue/');
        } else {
            $errors[] = 'Terjadi kesalahan. Silakan coba lagi';
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        // Update venue data with submitted values
        $venue['nama_venue'] = $_POST['nama_venue'];
        $venue['alamat'] = $_POST['alamat'];
        $venue['kapasitas'] = $_POST['kapasitas'];
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
            <div class="col-md-12">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Venue</h1>
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
                                               value="<?php echo htmlspecialchars($venue['nama_venue']); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kapasitas" class="form-label">Kapasitas *</label>
                                        <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                               value="<?php echo htmlspecialchars($venue['kapasitas']); ?>" 
                                               min="1" required>
                                        <div class="form-text">Jumlah maksimal orang</div>
                                    </div>
                                    <label for="kapasitas" class="form-label">Kapasitas *</label>
                                    <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                           value="<?php echo htmlspecialchars($venue['kapasitas']); ?>" 
                                           min="1" required>
                                    <div class="form-text">Jumlah maksimal orang</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat *</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo htmlspecialchars($venue['alamat']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/venue/'); ?>" class="btn btn-secondary me-2">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>
