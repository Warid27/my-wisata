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
    
    redirect('admin/tiket/');
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

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="container-fluid">
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



<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus tiket ini?')) {
        window.location.href = '<?php echo base_url('admin/tiket/?delete='); ?>' + id;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
