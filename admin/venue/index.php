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
    
    redirect('admin/venue/index.php');
}

// Get all venues
$query = "SELECT * FROM venue ORDER BY nama_venue";
$stmt = $db->prepare($query);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/index.php'); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                            <span>Master Data</span>
                        </h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/venue/index.php'); ?>">
                            <i class="bi bi-building"></i> Venue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/event/index.php'); ?>">
                            <i class="bi bi-calendar-event"></i> Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/tiket/index.php'); ?>">
                            <i class="bi bi-ticket"></i> Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/voucher/index.php'); ?>">
                            <i class="bi bi-ticket-detailed"></i> Voucher
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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

<style>
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}
.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}
.nav-link {
    font-weight: 500;
    color: #333;
}
.nav-link:hover {
    color: #007bff;
}
.nav-link.active {
    color: #007bff;
}
@media (min-width: 768px) {
    .sidebar {
        width: 240px;
    }
    main {
        margin-left: 240px;
    }
}
</style>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus venue ini?')) {
        window.location.href = '<?php echo base_url('admin/venue/index.php?delete='); ?>' + id;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
