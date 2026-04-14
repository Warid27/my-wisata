<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Dashboard Admin';

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get recent orders
$query = "SELECT o.*, u.nama as nama_user 
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          ORDER BY o.tanggal_order DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming events
$query = "SELECT e.*, v.nama_venue 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          WHERE e.tanggal >= CURDATE() 
          ORDER BY e.tanggal ASC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular events (most tickets sold)
$query = "SELECT e.nama_event, COUNT(a.id_attendee) as total_tiket 
          FROM event e 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
          LEFT JOIN orders o ON od.id_order = o.id_order 
          LEFT JOIN attendee a ON od.id_detail = a.id_detail 
          WHERE o.status = 'paid' 
          GROUP BY e.id_event 
          ORDER BY total_tiket DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$popular_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Selamat datang di panel administrasi MyWisata</p>
                </div>
                <div class="page-actions">
                    <button type="button" class="btn btn-secondary">
                        <i class="bi bi-download me-2"></i>Export Data
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row dashboard-stats">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total User</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                                    <div class="stat-change positive">
                                        <i class="bi bi-arrow-up"></i> 12% dari bulan lalu
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary-light">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total Pesanan</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                                    <div class="stat-change positive">
                                        <i class="bi bi-arrow-up"></i> 8% dari bulan lalu
                                    </div>
                                </div>
                                <div class="stat-icon bg-success-light">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total Pendapatan</div>
                                    <div class="stat-value"><?php echo format_currency($stats['total_revenue']); ?></div>
                                    <div class="stat-change positive">
                                        <i class="bi bi-arrow-up"></i> 23% dari bulan lalu
                                    </div>
                                </div>
                                <div class="stat-icon bg-info-light">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total Event</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_events']); ?></div>
                                    <div class="stat-change positive">
                                        <i class="bi bi-arrow-up"></i> 5% dari bulan lalu
                                    </div>
                                </div>
                                <div class="stat-icon bg-warning-light">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-6 mb-4">
                    <div class="card data-table">
                        <div class="card-header bg-white border-0 pt-4 pb-3">
                            <h6 class="card-title mb-0">Pesanan Terbaru</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">Belum ada pesanan</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID Pesanan</th>
                                                <th>Pelanggan</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark">
                                                            #<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['nama_user']); ?></td>
                                                    <td class="fw-semibold"><?php echo format_currency($order['total']); ?></td>
                                                    <td>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php elseif ($order['status'] === 'paid'): ?>
                                                            <span class="badge bg-success">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center p-3">
                                    <a href="<?php echo base_url('admin/orders.php'); ?>" class="btn btn-outline-primary btn-sm">
                                        Lihat Semua Pesanan
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="col-lg-6 mb-4">
                    <div class="card activity-card">
                        <div class="card-header bg-white border-0 pt-4 pb-3">
                            <h6 class="card-title mb-0">Event Mendatang</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($upcoming_events)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada event mendatang</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-center">
                                            <div class="activity-icon bg-info">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <div class="activity-content ms-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="activity-title">
                                                        <?php echo htmlspecialchars($event['nama_event']); ?>
                                                    </div>
                                                    <small class="activity-time">
                                                        <?php echo date('d M Y', strtotime($event['tanggal'])); ?>
                                                    </small>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?php echo htmlspecialchars($event['nama_venue']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center p-3">
                                    <a href="<?php echo base_url('admin/event/'); ?>" class="btn btn-outline-primary btn-sm">
                                        Kelola Semua Event
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Events -->
            <div class="row">
                <div class="col-12">
                    <div class="card chart-container">
                        <div class="chart-header">
                            <h6 class="chart-title">Event Populer (Tiket Terbanyak Terjual)</h6>
                            <p class="chart-subtitle">Berdasarkan total tiket yang terjual</p>
                        </div>
                        <div class="card-body">
                            <?php if (empty($popular_events)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-bar-chart text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">Belum ada data penjualan</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($popular_events as $index => $event): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="flex-shrink-0">
                                                            <div class="stat-icon bg-success-light">
                                                                <i class="bi bi-trophy"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <span class="badge bg-light text-dark fs-6">#<?php echo $index + 1; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <div class="stat-value" style="font-size: 1.5rem;">
                                                                <?php echo number_format($event['total_tiket']); ?>
                                                            </div>
                                                            <div class="stat-label">Tiket Terjual</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
