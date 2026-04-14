<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Manajemen Pesanan';

// Get filters
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT o.*, u.nama as nama_user, u.email, 
                 COUNT(a.id_attendee) as total_tiket,
                 v.kode_voucher, v.potongan as voucher_potongan
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          LEFT JOIN voucher v ON o.id_voucher = v.id_voucher 
          LEFT JOIN order_detail od ON o.id_order = od.id_order 
          LEFT JOIN attendee a ON od.id_detail = a.id_detail ";

$where_conditions = [];
$params = [];

if (!empty($status)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_conditions[] = "o.tanggal_order >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where_conditions[] = "o.tanggal_order <= ?";
    $params[] = $date_to . ' 23:59:59';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " GROUP BY o.id_order ORDER BY o.tanggal_order DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update order status
if (isset($_POST['update_status']) && is_numeric($_POST['order_id'])) {
    $id_order = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'paid', 'cancelled'])) {
        $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id_order = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$new_status, $id_order])) {
            set_flash_message('success', 'Status pesanan berhasil diperbarui');
        } else {
            set_flash_message('error', 'Gagal memperbarui status pesanan');
        }
    }
    
    redirect('admin/orders.php');
}

include __DIR__ . '/../includes/header.php';
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
                            <span>Transaksi</span>
                        </h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/orders.php'); ?>">
                            <i class="bi bi-cart"></i> Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/checkin.php'); ?>">
                            <i class="bi bi-qr-code"></i> Check-in
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/reports.php'); ?>">
                            <i class="bi bi-graph-up"></i> Laporan
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen Pesanan</h1>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Lunas</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?php echo $date_from; ?>" placeholder="Dari Tanggal">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?php echo $date_to; ?>" placeholder="Sampai Tanggal">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="<?php echo base_url('admin/orders.php'); ?>" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Tidak ada pesanan ditemukan
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Order</th>
                                        <th>Tanggal</th>
                                        <th>User</th>
                                        <th>Tiket</th>
                                        <th>Total</th>
                                        <th>Voucher</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td><?php echo format_date($order['tanggal_order'], 'd M Y H:i'); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['nama_user']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td><?php echo $order['total_tiket']; ?> tiket</td>
                                            <td class="fw-bold"><?php echo format_currency($order['total']); ?></td>
                                            <td>
                                                <?php if ($order['kode_voucher']): ?>
                                                    <small class="text-success">
                                                        <?php echo htmlspecialchars($order['kode_voucher']); ?>
                                                        <br>-<?php echo format_currency($order['voucher_potongan']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Menunggu Pembayaran</span>
                                                <?php elseif ($order['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Dibatalkan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="showOrderDetail(<?php echo $order['id_order']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($order['status'] === 'pending'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                                            <input type="hidden" name="status" value="paid">
                                                            <button type="submit" name="update_status" 
                                                                    class="btn btn-sm btn-success"
                                                                    onclick="return confirm('Konfirmasi pembayaran order ini?')">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" name="update_status" 
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Batalkan order ini?')">
                                                                <i class="bi bi-x"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
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

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
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
function showOrderDetail(orderId) {
    fetch('<?php echo base_url('api/order_detail.php'); ?>?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
        })
        .catch(error => {
            alert('Gagal memuat detail order');
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
