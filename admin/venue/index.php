<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Manajemen Venue';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_venue = $_GET['delete'];
    
    // Check if venue has events
    $query = "SELECT COUNT(*) as total FROM event WHERE id_venue = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_venue]);
    $has_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_events) {
        set_flash_message('error', 'Venue tidak dapat dihapus karena masih ada event terkait');
    } else {
        $query = "DELETE FROM venue WHERE id_venue = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_venue])) {
            set_flash_message('success', 'Venue berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus venue');
        }
    }
    
    redirect('admin/venue/');
}

// Get all venues
$query = "SELECT * FROM venue ORDER BY nama_venue";
$stmt = $db->prepare($query);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Venue</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/venue/create.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Venue
                    </a>
                </div>
            </div>

            <!-- Venue Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($venues)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data venue
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Venue</th>
                                        <th>Alamat</th>
                                        <th>Kapasitas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($venues as $venue): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($venue['nama_venue']); ?></td>
                                            <td><?php echo htmlspecialchars($venue['alamat']); ?></td>
                                            <td><?php echo number_format($venue['kapasitas']); ?> orang</td>
                                            <td>
                                                <a href="<?php echo base_url('admin/venue/edit.php?id=' . $venue['id_venue']); ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $venue['id_venue']; ?>)">
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
    if (confirm('Apakah Anda yakin ingin menghapus venue ini?')) {
        window.location.href = '<?php echo base_url('admin/venue/?delete='); ?>' + id;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
