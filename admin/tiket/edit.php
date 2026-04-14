<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Edit Tiket';

// Get ticket ID
$id_tiket = $_GET['id'] ?? 0;
if (!is_numeric($id_tiket)) {
    set_flash_message('error', 'ID tiket tidak valid');
    redirect('admin/tiket/');
}

// Get ticket data with event
$query = "SELECT t.*, e.nama_event, e.tanggal 
          FROM tiket t 
          JOIN event e ON t.id_event = e.id_event 
          WHERE t.id_tiket = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_tiket]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    set_flash_message('error', 'Tiket tidak ditemukan');
    redirect('admin/tiket/');
}

// Get all events for dropdown
$query = "SELECT e.*, v.nama_venue 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
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
    
    // Get current sold tickets
    $query = "SELECT COALESCE(SUM(od.qty), 0) as sold 
              FROM order_detail od 
              JOIN orders o ON od.id_order = o.id_order 
              WHERE od.id_tiket = ? AND o.status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_tiket]);
    $sold = $stmt->fetch(PDO::FETCH_ASSOC)['sold'];
    
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
    
    if ($kuota < $sold) {
        $errors[] = 'Kuota tidak boleh kurang dari tiket yang sudah terjual (' . $sold . ' tiket)';
    }
    
    // Update ticket if no errors
    if (empty($errors)) {
        $query = "UPDATE tiket SET id_event = ?, nama_tiket = ?, harga = ?, kuota = ? WHERE id_tiket = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_event, $nama_tiket, $harga, $kuota, $id_tiket])) {
            set_flash_message('success', 'Tiket berhasil diperbarui');
            redirect('admin/tiket/');
        } else {
            $errors[] = 'Terjadi kesalahan. Silakan coba lagi';
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        // Update ticket data with submitted values
        $ticket['id_event'] = $_POST['id_event'];
        $ticket['nama_tiket'] = $_POST['nama_tiket'];
        $ticket['harga'] = $_POST['harga'];
        $ticket['kuota'] = $_POST['kuota'];
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
                    <h1 class="h2">Edit Tiket</h1>
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
                                                <?php echo ($ticket['id_event'] == $event['id_event']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['nama_event']); ?> - 
                                            <?php echo format_date($event['tanggal']); ?> 
                                            (<?php echo htmlspecialchars($event['nama_venue']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                    </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_tiket" class="form-label">Nama Tiket *</label>
                                    <input type="text" class="form-control" id="nama_tiket" name="nama_tiket" 
                                           value="<?php echo htmlspecialchars($ticket['nama_tiket']); ?>" 
                                           placeholder="Contoh: VIP, Regular, Early Bird" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga" name="harga" 
                                               value="<?php echo htmlspecialchars($ticket['harga']); ?>" 
                                               min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kuota" class="form-label">Kuota *</label>
                            <input type="number" class="form-control" id="kuota" name="kuota" 
                                   value="<?php echo htmlspecialchars($ticket['kuota']); ?>" 
                                   min="1" required>
                            <div class="form-text">Jumlah tiket yang tersedia untuk dijual</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Tiket yang sudah terjual tidak dapat dikurangi dari kuota
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo base_url('admin/tiket/'); ?>" class="btn btn-secondary me-2">
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
