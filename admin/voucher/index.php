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

    redirect('admin/voucher/');
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

    redirect('admin/voucher/');
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

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Voucher</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo base_url('admin/voucher/create.php'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Voucher
            </a>
        </div>
    </div> <?php if (empty($vouchers)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Belum ada data voucher
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
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
                    <?php $no = 1;
                    foreach ($vouchers as $voucher): ?>
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



<script>
    function confirmDelete(id) {
        showConfirmation('Apakah Anda yakin ingin menghapus voucher ini?', function() {
            window.location.href = '<?php echo base_url('admin/voucher/?delete='); ?>' + id;
        }, {isDanger: true});
    }

    function toggleStatus(id) {
        window.location.href = '<?php echo base_url('admin/voucher/?toggle='); ?>' + id;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>