<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Manajemen Tiket';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_tiket = $_GET['delete'];
    
    // Check if ticket has orders
    $query = "SELECT COUNT(*) as total FROM order_detail WHERE id_tiket = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_tiket]);
    $has_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_orders) {
        set_flash_message('error', 'Tiket tidak dapat dihapus karena sudah ada pesanan');
    } else {
        $query = "DELETE FROM tiket WHERE id_tiket = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_tiket])) {
            set_flash_message('success', 'Tiket berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus tiket');
        }
    }
    
    redirect('admin/tiket/index.php');
}

// Get all tickets with event
$query = "SELECT t.*, e.nama_event, e.tanggal 
          FROM tiket t 
          JOIN event e ON t.id_event = e.id_event 
          ORDER BY e.tanggal DESC, t.nama_tiket";
$stmt = $db->prepare($query);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <a class="nav-link" href="<?php echo base_url('admin/venue/index.php'); ?>">
                            <i class="bi bi-building"></i> Venue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/event/index.php'); ?>">
                            <i class="bi bi-calendar-event"></i> Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/tiket/index.php'); ?>">
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
                <h1 class="h2">Manajemen Tiket</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/tiket/create.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Tiket
                    </a>
                </div>
            </div>

            <!-- Ticket Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data tiket
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Tiket</th>
                                        <th>Event</th>
                                        <th>Tanggal Event</th>
                                        <th>Harga</th>
                                        <th>Kuota</th>
                                        <th>Terjual</th>
                                        <th>Sisa</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($tickets as $ticket): ?>
                                        <?php 
                                        // Get sold tickets count
                                        $query = "SELECT COALESCE(SUM(od.qty), 0) as sold 
                                                  FROM order_detail od 
                                                  JOIN orders o ON od.id_order = o.id_order 
                                                  WHERE od.id_tiket = ? AND o.status != 'cancelled'";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute([$ticket['id_tiket']]);
                                        $sold = $stmt->fetch(PDO::FETCH_ASSOC)['sold'];
                                        $sisa = $ticket['kuota'] - $sold;
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($ticket['nama_tiket']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['nama_event']); ?></td>
                                            <td><?php echo format_date($ticket['tanggal']); ?></td>
                                            <td><?php echo format_currency($ticket['harga']); ?></td>
                                            <td><?php echo number_format($ticket['kuota']); ?></td>
                                            <td><?php echo number_format($sold); ?></td>
                                            <td>
                                                <?php if ($sisa <= 0): ?>
                                                    <span class="badge bg-danger">Habis</span>
                                                <?php elseif ($sisa <= 10): ?>
                                                    <span class="badge bg-warning"><?php echo number_format($sisa); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo number_format($sisa); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo base_url('admin/tiket/edit.php?id=' . $ticket['id_tiket']); ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $ticket['id_tiket']; ?>)">
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
    if (confirm('Apakah Anda yakin ingin menghapus tiket ini?')) {
        window.location.href = '<?php echo base_url('admin/tiket/index.php?delete='); ?>' + id;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
