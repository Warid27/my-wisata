<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Manajemen Event';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_event = $_GET['delete'];
    
    // Check if event has tickets or orders
    $query = "SELECT (SELECT COUNT(*) FROM tiket WHERE id_event = ?) as tickets, 
                     (SELECT COUNT(*) FROM order_detail od JOIN tiket t ON od.id_tiket = t.id_tiket WHERE t.id_event = ?) as orders";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_event, $id_event]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check['tickets'] > 0 || $check['orders'] > 0) {
        set_flash_message('error', 'Event tidak dapat dihapus karena masih ada tiket atau pesanan terkait');
    } else {
        $query = "DELETE FROM event WHERE id_event = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_event])) {
            set_flash_message('success', 'Event berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus event');
        }
    }
    
    redirect('admin/event/');
}

// Get all events with venue
$query = "SELECT e.*, v.nama_venue 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          ORDER BY e.tanggal DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Event</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/event/create.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Event
                    </a>
                </div>
            </div>

            <!-- Event Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data event
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Event</th>
                                        <th>Tanggal</th>
                                        <th>Venue</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($event['gambar']): ?>
                                                        <img src="<?php echo assets_url('images/' . $event['gambar']); ?>" 
                                                             alt="<?php echo htmlspecialchars($event['nama_event']); ?>" 
                                                             class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong>
                                                        <?php if ($event['deskripsi']): ?>
                                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($event['deskripsi']), 0, 50); ?>...</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo format_date($event['tanggal']); ?></td>
                                            <td><?php echo htmlspecialchars($event['nama_venue']); ?></td>
                                            <td>
                                                <?php 
                                                $today = date('Y-m-d');
                                                if ($event['tanggal'] < $today): ?>
                                                    <span class="badge bg-secondary">Selesai</span>
                                                <?php elseif ($event['tanggal'] == $today): ?>
                                                    <span class="badge bg-success">Hari Ini</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Mendatang</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo base_url('admin/event/edit.php?id=' . $event['id_event']); ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $event['id_event']; ?>)">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>



<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus event ini?')) {
        window.location.href = '<?php echo base_url('admin/event/?delete='); ?>' + id;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
