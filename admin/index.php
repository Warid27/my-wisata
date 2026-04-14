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

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/index.php'); ?>">
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
                        <a class="nav-link" href="<?php echo base_url('admin/voucher/index.php'); ?>">
                            <i class="bi bi-ticket-detailed"></i> Voucher
                        </a>
                    </li>
                    <li class="nav-item">
                        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                            <span>Transaksi</span>
                        </h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/orders.php'); ?>">
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
                <h1 class="h2">Dashboard</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total User</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_users']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Pesanan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_orders']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cart-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Pendapatan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo format_currency($stats['total_revenue']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Event</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_events']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calendar-event fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Recent Orders -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Pesanan Terbaru</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_orders)): ?>
                                <p class="text-center text-muted">Belum ada pesanan</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
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
                                                    <td>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo htmlspecialchars($order['nama_user']); ?></td>
                                                    <td><?php echo format_currency($order['total']); ?></td>
                                                    <td>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
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
                                <div class="text-center mt-2">
                                    <a href="<?php echo base_url('admin/orders.php'); ?>" class="btn btn-primary btn-sm">
                                        Lihat Semua
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Event Mendatang</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_events)): ?>
                                <p class="text-center text-muted">Belum ada event mendatang</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcoming_events as $event): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
                                                <small><?php echo format_date($event['tanggal']); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['nama_venue']); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-2">
                                    <a href="<?php echo base_url('admin/event/index.php'); ?>" class="btn btn-primary btn-sm">
                                        Kelola Event
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
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Event Populer (Tiket Terbanyak Terjual)</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($popular_events)): ?>
                                <p class="text-center text-muted">Belum ada data penjualan</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($popular_events as $event): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card border-left-success">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
                                                    <p class="card-text">
                                                        <i class="bi bi-ticket"></i> 
                                                        <?php echo number_format($event['total_tiket']); ?> tiket terjual
                                                    </p>
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
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
