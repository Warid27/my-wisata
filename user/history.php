<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

$page_title = 'Riwayat Pembelian';

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get total orders
$query = "SELECT COUNT(*) as total FROM orders WHERE id_user = ?";
$stmt = $db->prepare($query);
$stmt->execute([get_user_id()]);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total_orders, $limit, $page);

// Get user orders with pagination
$orders = get_user_orders(get_user_id(), $limit, $offset);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="bi bi-clock-history"></i> Riwayat Pembelian
    </h2>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-inbox display-1"></i>
            <h5 class="mt-3">Belum ada riwayat pembelian</h5>
            <p>Belanja sekarang dan lihat riwayatnya di sini!</p>
            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-primary">
                <i class="bi bi-calendar-event"></i> Lihat Event
            </a>
        </div>
    <?php else: ?>
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                            <option value="paid" <?php echo ($_GET['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Lunas</option>
                            <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control" name="date_from" 
                               value="<?php echo $_GET['date_from'] ?? ''; ?>" 
                               placeholder="Dari Tanggal">
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control" name="date_to" 
                               value="<?php echo $_GET['date_to'] ?? ''; ?>" 
                               placeholder="Sampai Tanggal">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="<?php echo base_url('user/history.php'); ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID Order</th>
                                <th>Tanggal</th>
                                <th>Event</th>
                                <th>Tiket</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php 
                                // Get first event name from order
                                $query = "SELECT e.nama_event 
                                          FROM orders o 
                                          JOIN order_detail od ON o.id_order = od.id_order 
                                          JOIN tiket t ON od.id_tiket = t.id_tiket 
                                          JOIN event e ON t.id_event = e.id_event 
                                          WHERE o.id_order = ? 
                                          LIMIT 1";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$order['id_order']]);
                                $event_name = $stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo format_date($order['tanggal_order'], 'd M Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($event_name); ?></td>
                                    <td><?php echo $order['total_tiket']; ?> tiket</td>
                                    <td class="fw-bold"><?php echo format_currency($order['total']); ?></td>
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
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="<?php echo base_url('user/payment.php?order=' . $order['id_order']); ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-credit-card"></i> Bayar
                                                </a>
                                            <?php elseif ($order['status'] === 'paid'): ?>
                                                <a href="<?php echo base_url('user/my_tickets.php?order=' . $order['id_order']); ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bi bi-ticket"></i> Lihat Tiket
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="showOrderDetail(<?php echo $order['id_order']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['has_prev']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
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

<script>
function showOrderDetail(orderId) {
    fetch('<?php echo base_url('api/order_detail.php'); ?>?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
        })
        .catch(error => {
            showNotification('Gagal memuat detail order', 'error');
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
