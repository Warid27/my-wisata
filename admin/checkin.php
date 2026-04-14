<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Check-in Tiket';

// Process check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_code'])) {
    $kode_tiket = strtoupper(sanitize($_POST['kode_tiket'] ?? ''));
    
    if (empty($kode_tiket)) {
        set_flash_message('error', 'Kode tiket harus diisi');
    } else {
        $ticket_data = checkin_ticket($kode_tiket);
        
        if ($ticket_data) {
            set_flash_message('success', 
                'Check-in berhasil! ' . 
                '<br>Nama: ' . htmlspecialchars($ticket_data['nama_event']) . 
                '<br>Tanggal: ' . format_date($ticket_data['tanggal']) . 
                '<br>Waktu: ' . date('H:i:s')
            );
        } else {
            set_flash_message('error', 'Kode tiket tidak valid atau sudah check-in');
        }
    }
}

// Get recent check-ins
$query = "SELECT a.*, e.nama_event, e.tanggal, u.nama as nama_user 
          FROM attendee a 
          JOIN order_detail od ON a.id_detail = od.id_detail 
          JOIN orders o ON od.id_order = o.id_order 
          JOIN users u ON o.id_user = u.id_user 
          JOIN tiket t ON od.id_tiket = t.id_tiket 
          JOIN event e ON t.id_event = e.id_event 
          WHERE a.status_checkin = 'sudah' 
          ORDER BY a.waktu_checkin DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's events for quick access
$query = "SELECT DISTINCT e.id_event, e.nama_event, COUNT(a.id_attendee) as total_checkins,
                 COUNT(CASE WHEN a.status_checkin = 'sudah' THEN 1 END) as checked_in
          FROM event e 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
          LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
          LEFT JOIN attendee a ON od.id_detail = a.id_detail 
          WHERE e.tanggal = CURDATE()
          GROUP BY e.id_event
          ORDER BY e.nama_event";
$stmt = $db->prepare($query);
$stmt->execute();
$today_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <a class="nav-link active" href="<?php echo base_url('admin/checkin.php'); ?>">
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
                <h1 class="h2">Check-in Tiket</h1>
            </div>

            <!-- Check-in Form -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-qr-code-scan"></i> Scan / Input Kode Tiket
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="checkinForm">
                                <div class="mb-3">
                                    <label for="kode_tiket" class="form-label">Kode Tiket</label>
                                    <div class="input-group input-group-lg">
                                        <input type="text" class="form-control" id="kode_tiket" name="kode_tiket" 
                                               placeholder="Masukkan atau scan kode tiket" 
                                               style="text-transform: uppercase;" 
                                               autofocus required>
                                        <button type="submit" name="checkin_code" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Check-in
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> 
                                        Gunakan scanner QR code atau ketik kode tiket manual
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Today's Events -->
                <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-check"></i> Event Hari Ini
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_events)): ?>
                                <p class="text-muted">Tidak ada event hari ini</p>
                            <?php else: ?>
                                <?php foreach ($today_events as $event): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div>
                                            <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Check-in: <?php echo $event['checked_in']; ?> / <?php echo $event['total_checkins']; ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php $percentage = $event['total_checkins'] > 0 ? ($event['checked_in'] / $event['total_checkins']) * 100 : 0; ?>
                                            <span class="badge bg-<?php echo $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'secondary'); ?>">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Check-ins -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Check-in Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_checkins)): ?>
                        <p class="text-muted">Belum ada check-in hari ini</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Kode Tiket</th>
                                        <th>Event</th>
                                        <th>Pengunjung</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_checkins as $checkin): ?>
                                        <tr>
                                            <td><?php echo format_date($checkin['waktu_checkin'], 'H:i:s'); ?></td>
                                            <td>
                                                <code class="ticket-code"><?php echo htmlspecialchars($checkin['kode_tiket']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($checkin['nama_event']); ?></td>
                                            <td><?php echo htmlspecialchars($checkin['nama_user']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Sudah
                                                </span>
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
// Auto-focus on kode_tiket input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('kode_tiket').focus();
    
    // Clear input after successful check-in
    <?php if (has_flash_message('success')): ?>
        document.getElementById('kode_tiket').value = '';
        document.getElementById('kode_tiket').focus();
    <?php endif; ?>
});

// Handle Enter key for quick check-in
document.getElementById('checkinForm').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.submit();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
