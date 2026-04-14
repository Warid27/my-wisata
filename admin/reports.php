<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Laporan Penjualan';

// Get date filters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get sales report
$sales_report = get_sales_report($date_from, $date_to);

// Get top events by revenue
$query = "SELECT e.id_event, e.nama_event, COUNT(DISTINCT o.id_order) as total_orders,
                 SUM(od.qty) as total_tiket, SUM(od.subtotal) as total_penjualan
          FROM event e
          LEFT JOIN tiket t ON e.id_event = t.id_event
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket
          LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
          WHERE o.tanggal_order BETWEEN ? AND ?
          GROUP BY e.id_event
          ORDER BY total_penjualan DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$query = "SELECT status, COUNT(*) as count, COALESCE(SUM(total), 0) as total
          FROM orders 
          WHERE tanggal_order BETWEEN ? AND ?
          GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_orders = 0;
$total_revenue = 0;
$total_tickets = 0;
$cancelled_orders = 0;

foreach ($sales_report as $sale) {
    $total_orders += $sale['total_orders'];
    $total_revenue += $sale['total_penjualan'];
    $total_tickets += $sale['total_tiket'];
}

foreach ($payment_stats as $stat) {
    if ($stat['status'] === 'cancelled') {
        $cancelled_orders = $stat['count'];
    }
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
                        <a class="nav-link active" href="<?php echo base_url('admin/reports.php'); ?>">
                            <i class="bi bi-graph-up"></i> Laporan
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Laporan Penjualan</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" onclick="exportReport()">
                        <i class="bi bi-download"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to; ?>" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="<?php echo base_url('admin/reports.php'); ?>" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Pesanan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($total_orders); ?>
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Pendapatan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo format_currency($total_revenue); ?>
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
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Tiket Terjual</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($total_tickets); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-ticket fa-2x text-gray-300"></i>
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
                                        Dibatalkan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($cancelled_orders); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-x-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Report Table -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-table"></i> Laporan Penjualan per Event
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sales_report)): ?>
                        <p class="text-muted">Tidak ada data penjualan pada periode ini</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Tanggal</th>
                                        <th>Pesanan</th>
                                        <th>Tiket Terjual</th>
                                        <th>Total Penjualan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_report as $sale): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sale['nama_event']); ?></td>
                                            <td><?php echo format_date($sale['tanggal']); ?></td>
                                            <td><?php echo number_format($sale['total_orders']); ?></td>
                                            <td><?php echo number_format($sale['total_tiket']); ?></td>
                                            <td class="fw-bold"><?php echo format_currency($sale['total_penjualan']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-primary">
                                    <tr>
                                        <th colspan="2">TOTAL</th>
                                        <th><?php echo number_format($total_orders); ?></th>
                                        <th><?php echo number_format($total_tickets); ?></th>
                                        <th><?php echo format_currency($total_revenue); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Events -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-trophy"></i> Top 10 Event (Pendapatan Tertinggi)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_events)): ?>
                                <p class="text-muted">Tidak ada data</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Event</th>
                                                <th>Pesanan</th>
                                                <th>Tiket Terjual</th>
                                                <th>Pendapatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($top_events as $event): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($event['nama_event']); ?></td>
                                                    <td><?php echo number_format($event['total_orders']); ?></td>
                                                    <td><?php echo number_format($event['total_tiket']); ?></td>
                                                    <td class="fw-bold"><?php echo format_currency($event['total_penjualan']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
@media (min-width: 768px) {
    .sidebar {
        width: 240px;
    }
    main {
        margin-left: 240px;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportReport() {
    // Get table data
    const table = document.getElementById('salesTable');
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Create workbook
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Sales Report');
    
    // Generate filename with date range
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    const filename = `Laporan_Penjualan_${dateFrom}_s_d_${dateTo}.xlsx`;
    
    // Download file
    XLSX.writeFile(wb, filename);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
