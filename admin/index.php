<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components/page_header.php';

require_staff();

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

// Get monthly revenue data for chart (last 6 months)
$query = "SELECT DATE_FORMAT(tanggal_order, '%Y-%m') as month, 
                COUNT(id_order) as orders, 
                SUM(total) as revenue
          FROM orders 
          WHERE status = 'paid' AND tanggal_order >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(tanggal_order, '%Y-%m')
          ORDER BY month ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get event category distribution
$query = "SELECT k.nama_kategori, COUNT(e.id_event) as total_events,
                COALESCE(SUM(CASE WHEN o.status = 'paid' THEN od.subtotal ELSE 0 END), 0) as revenue
          FROM kategori k 
          LEFT JOIN event e ON k.id_kategori = e.id_kategori 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
          LEFT JOIN orders o ON od.id_order = o.id_order 
          GROUP BY k.id_kategori
          ORDER BY total_events DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get venue performance
$query = "SELECT v.nama_venue, COUNT(DISTINCT e.id_event) as total_events,
                COALESCE(SUM(CASE WHEN o.status = 'paid' THEN od.subtotal ELSE 0 END), 0) as revenue,
                COUNT(DISTINCT a.id_attendee) as total_attendees
          FROM venue v 
          LEFT JOIN event e ON v.id_venue = e.id_venue 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
          LEFT JOIN orders o ON od.id_order = o.id_order 
          LEFT JOIN attendee a ON od.id_detail = a.id_detail 
          WHERE o.status = 'paid' OR o.status IS NULL
          GROUP BY v.id_venue
          ORDER BY revenue DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$venue_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo base_url('assets/js/dashboard-charts.js'); ?>"></script>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <?php
    // Page Header Component
    render_page_header([
        'title' => 'Dashboard',
        'subtitle' => 'Selamat datang di panel administrasi MyWisata',
        'actions' => [
            [
                'label' => 'Export Data',
                'icon' => 'bi-download',
                'class' => 'btn-secondary',
                'type' => 'button',
                'onclick' => 'showExportModal()'
            ]
        ]
    ]);
    ?>

            <!-- Dashboard Components -->
    <?php include __DIR__ . '/../includes/components/dashboard/stats_cards.php'; ?>
    
    <?php include __DIR__ . '/../includes/components/dashboard/revenue_chart.php'; ?>
    
    <!-- Recent Orders & Upcoming Events Row -->
    <div class="row">
        <?php include __DIR__ . '/../includes/components/dashboard/recent_orders.php'; ?>
        <?php include __DIR__ . '/../includes/components/dashboard/upcoming_events.php'; ?>
    </div>
    
    <!-- Popular Events & Venue Performance Row -->
    <div class="row">
        <?php include __DIR__ . '/../includes/components/dashboard/popular_events.php'; ?>
        <?php include __DIR__ . '/../includes/components/dashboard/venue_performance.php'; ?>
    </div>

</main>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportForm" method="get" action="<?php echo base_url('api/export_pdf.php'); ?>" target="_blank">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exportType" class="form-label">Tipe Export</label>
                        <select class="form-select" id="exportType" name="type" required>
                            <option value="sales">Laporan Penjualan</option>
                            <option value="tickets">Laporan Tiket Terjual</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dateFrom" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="dateFrom" name="date_from" 
                               value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="dateTo" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="dateTo" name="date_to" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showExportModal() {
    var modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

// Set max date to today for date inputs
document.addEventListener('DOMContentLoaded', function() {
    const dateTo = document.getElementById('dateTo');
    const dateFrom = document.getElementById('dateFrom');
    const today = new Date().toISOString().split('T')[0];
    
    dateTo.max = today;
    dateFrom.max = today;
    
    // Ensure dateFrom is not after dateTo
    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
    });
    
    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
