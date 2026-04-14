<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Tambah Tiket';

// Get all events for dropdown
$query = "SELECT e.*, v.nama_venue 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          WHERE e.tanggal >= CURDATE() 
          ORDER BY e.tanggal ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_event = (int)($_POST['id_event'] ?? 0);
    $nama_tiket = sanitize($_POST['nama_tiket'] ?? '');
    $harga = (int)($_POST['harga'] ?? 0);
    $kuota = (int)($_POST['kuota'] ?? 0);
    
    // Validation
    $errors = [];
    
    if ($id_event <= 0) {
        $errors[] = 'Event harus dipilih';
    }
    
    if (empty($nama_tiket)) {
        $errors[] = 'Nama tiket harus diisi';
    }
    
    if ($harga <= 0) {
        $errors[] = 'Harga harus lebih dari 0';
    }
    
    if ($kuota <= 0) {
        $errors[] = 'Kuota harus lebih dari 0';
    }
    
    // Insert ticket if no errors
    if (empty($errors)) {
        $query = "INSERT INTO tiket (id_event, nama_tiket, harga, kuota) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_event, $nama_tiket, $harga, $kuota])) {
            set_flash_message('success', 'Tiket berhasil ditambahkan');
            redirect('admin/tiket/');
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
            <h1 class="h2">Tambah Tiket</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="<?php echo base_url('admin/tiket/'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Tiket -->
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="id_event" class="form-label">Event *</label>
                        <select class="form-select" id="id_event" name="id_event" required>
                            <option value="">-- Pilih Event --</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['id_event']; ?>" 
                                        <?php echo (isset($_POST['id_event']) && $_POST['id_event'] == $event['id_event']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['nama_event']); ?> - 
                                    <?php echo format_date($event['tanggal']); ?> 
                                    (<?php echo htmlspecialchars($event['nama_venue']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($events)): ?>
                            <div class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Tidak ada event mendatang. Silakan buat event terlebih dahulu.
                            </div>
                        <?php endif; ?>
                    </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_tiket" class="form-label">Nama Tiket *</label>
                                    <input type="text" class="form-control" id="nama_tiket" name="nama_tiket" 
                                           value="<?php echo isset($_POST['nama_tiket']) ? htmlspecialchars($_POST['nama_tiket']) : ''; ?>" 
                                           placeholder="Contoh: VIP, Regular, Early Bird" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga" name="harga" 
                                               value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''; ?>" 
                                               min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kuota" class="form-label">Kuota *</label>
                            <input type="number" class="form-control" id="kuota" name="kuota" 
                                   value="<?php echo isset($_POST['kuota']) ? htmlspecialchars($_POST['kuota']) : ''; ?>" 
                                   min="1" required>
                            <div class="form-text">Jumlah tiket yang tersedia untuk dijual</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/tiket/'); ?>" class="btn btn-secondary me-2">
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
