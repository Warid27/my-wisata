<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Manajemen Voucher';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_voucher = $_GET['delete'];
    
    // Check if voucher has orders
    $query = "SELECT COUNT(*) as total FROM orders WHERE id_voucher = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_voucher]);
    $has_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_orders) {
        set_flash_message('error', 'Voucher tidak dapat dihapus karena sudah digunakan dalam pesanan');
    } else {
        $query = "DELETE FROM voucher WHERE id_voucher = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_voucher])) {
            set_flash_message('success', 'Voucher berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus voucher');
        }
    }
    
    redirect('admin/voucher/index.php');
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id_voucher = $_GET['toggle'];
    
    $query = "UPDATE voucher SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id_voucher = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$id_voucher])) {
        set_flash_message('success', 'Status voucher berhasil diperbarui');
    } else {
        set_flash_message('error', 'Gagal memperbarui status voucher');
    }
    
    redirect('admin/voucher/index.php');
}

// Get all vouchers
$query = "SELECT *, 
                 (SELECT COUNT(*) FROM orders WHERE id_voucher = v.id_voucher) as used_count 
          FROM voucher v 
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <a class="nav-link" href="<?php echo base_url('admin/tiket/index.php'); ?>">
                            <i class="bi bi-ticket"></i> Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/voucher/index.php'); ?>">
                            <i class="bi bi-ticket-detailed"></i> Voucher
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Voucher</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/voucher/create.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Voucher
                    </a>
                </div>
            </div>

            <!-- Voucher Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($vouchers)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Belum ada data voucher
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Voucher</th>
                                        <th>Potongan</th>
                                        <th>Kuota</th>
                                        <th>Terpakai</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($vouchers as $voucher): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <code class="ticket-code"><?php echo htmlspecialchars($voucher['kode_voucher']); ?></code>
                                            </td>
                                            <td><?php echo format_currency($voucher['potongan']); ?></td>
                                            <td><?php echo number_format($voucher['kuota']); ?></td>
                                            <td><?php echo number_format($voucher['used_count']); ?></td>
                                            <td>
                                                <?php $sisa = $voucher['kuota'] - $voucher['used_count']; ?>
                                                <?php if ($sisa <= 0): ?>
                                                    <span class="badge bg-danger">Habis</span>
                                                <?php elseif ($sisa <= 10): ?>
                                                    <span class="badge bg-warning"><?php echo number_format($sisa); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo number_format($sisa); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($voucher['status'] === 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo format_date($voucher['created_at'], 'd M Y H:i'); ?></td>
                                            <td>
                                                <a href="<?php echo base_url('admin/voucher/edit.php?id=' . $voucher['id_voucher']); ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm <?php echo $voucher['status'] === 'aktif' ? 'btn-secondary' : 'btn-success'; ?>" 
                                                        onclick="toggleStatus(<?php echo $voucher['id_voucher']; ?>)" 
                                                        title="<?php echo $voucher['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                    <i class="bi bi-<?php echo $voucher['status'] === 'aktif' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $voucher['id_voucher']; ?>)">
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
    if (confirm('Apakah Anda yakin ingin menghapus voucher ini?')) {
        window.location.href = '<?php echo base_url('admin/voucher/index.php?delete='); ?>' + id;
    }
}

function toggleStatus(id) {
    window.location.href = '<?php echo base_url('admin/voucher/index.php?toggle='); ?>' + id;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
