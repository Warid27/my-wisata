<?php
/**
 * Recent Orders Component
 * Displays the most recent orders in a table
 * 
 * @param array $recent_orders Recent orders data
 */
if (!isset($recent_orders)) {
    return;
}
?>

<!-- Recent Orders -->
<div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h6 class="card-title mb-0">Pesanan Terbaru</h6>
                <small class="text-muted">5 pesanan terakhir</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-cart text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">Belum ada pesanan</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><small>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></small></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar-sm bg-primary-light text-primary me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold small"><?php echo htmlspecialchars($order['nama_user']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold small"><?php echo format_currency($order['total']); ?></td>
                                        <td>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Menunggu</span>
                                            <?php elseif ($order['status'] === 'paid'): ?>
                                                <span class="badge bg-success">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Dibatalkan</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
        </div>
    </div>
