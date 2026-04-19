<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_staff();

$page_title = 'Tambah Event';

// Get all venues for dropdown
$query = "SELECT * FROM venue ORDER BY nama_venue";
$stmt = $db->prepare($query);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_event = sanitize($_POST['nama_event'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $id_venue = (int)($_POST['id_venue'] ?? 0);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    
    // Handle image upload
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['gambar']['type'], $allowed_types)) {
            if ($_FILES['gambar']['size'] <= $max_size) {
                $filename = 'event_' . time() . '_' . $_FILES['gambar']['name'];
                $upload_path = UPLOAD_PATH . $filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $filename;
                } else {
                    $errors[] = 'Gagal mengupload gambar';
                }
            } else {
                $errors[] = 'Ukuran gambar maksimal 5MB';
            }
        } else {
            $errors[] = 'Format gambar tidak valid. Gunakan JPG, PNG, atau GIF';
        }
    }
    
    // Validation
    $errors = [];
    
    if (empty($nama_event)) {
        $errors[] = 'Nama event harus diisi';
    }
    
    if (empty($tanggal)) {
        $errors[] = 'Tanggal event harus diisi';
    } elseif (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Tanggal event tidak boleh kurang dari hari ini';
    }
    
    if ($id_venue <= 0) {
        $errors[] = 'Venue harus dipilih';
    }
    
    // Insert event if no errors
    if (empty($errors)) {
        $query = "INSERT INTO event (nama_event, tanggal, id_venue, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nama_event, $tanggal, $id_venue, $deskripsi, $gambar])) {
            set_flash_message('success', 'Event berhasil ditambahkan');
            redirect('admin/event/');
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
                <h1 class="h2">Tambah Event</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/event/'); ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- Form Event -->
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nama_event" class="form-label">Nama Event *</label>
                                    <input type="text" class="form-control" id="nama_event" name="nama_event" 
                                           value="<?php echo isset($_POST['nama_event']) ? htmlspecialchars($_POST['nama_event']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tanggal" class="form-label">Tanggal Event *</label>
                                    <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                           value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_venue" class="form-label">Venue *</label>
                            <select class="form-select" id="id_venue" name="id_venue" required>
                                <option value="">-- Pilih Venue --</option>
                                <?php foreach ($venues as $venue): ?>
                                    <option value="<?php echo $venue['id_venue']; ?>" 
                                            <?php echo (isset($_POST['id_venue']) && $_POST['id_venue'] == $venue['id_venue']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($venue['nama_venue']); ?> 
                                        (Kapasitas: <?php echo number_format($venue['kapasitas']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Gambar Event</label>
                            <input type="file" class="form-control" id="gambar" name="gambar" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif">
                            <div class="form-text">Format: JPG, PNG, GIF. Maksimal: 5MB</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/event/'); ?>" class="btn btn-secondary me-2">
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
