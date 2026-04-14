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

<!-- HTML5-QRCode Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Check-in Tiket</h1>
            <p class="page-subtitle">Scan atau input kode tiket untuk check-in</p>
        </div>
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
                    <!-- Scan Mode Toggle -->
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" name="scanMode" id="manualMode" autocomplete="off" checked>
                        <label class="btn btn-outline-secondary" for="manualMode">
                            <i class="bi bi-keyboard"></i> Manual
                        </label>
                        
                        <input type="radio" class="btn-check" name="scanMode" id="cameraMode" autocomplete="off">
                        <label class="btn btn-outline-success" for="cameraMode">
                            <i class="bi bi-camera"></i> Kamera
                        </label>
                    </div>
                    
                    <!-- Manual Input Form -->
                    <div id="manualInput">
                        <form method="POST" id="checkinForm">
                            <div class="mb-3">
                                <label for="kode_tiket" class="form-label">Kode Tiket</label>
                                <div class="input-group input-group-lg">
                                    <input type="text" class="form-control" id="kode_tiket" name="kode_tiket" 
                                           placeholder="Masukkan kode tiket" 
                                           style="text-transform: uppercase;" 
                                           autofocus required>
                                    <button type="submit" name="checkin_code" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i> Check-in
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> 
                                    Ketik kode tiket manual atau gunakan scanner kamera
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Camera Scanner Component -->
                    <?php include __DIR__ . '/../includes/components/camera_scanner.php'; ?>
                </div>
            </div>
        </div>

        <!-- Today's Events Component -->
        <div class="col-lg-6">
            <?php 
            $events = $today_events;
            include __DIR__ . '/../includes/components/today_events.php';
            ?>
        </div>
    </div>

    <!-- Recent Check-ins Component -->
    <div class="row">
        <div class="col-12">
            <?php 
            $checkins = $recent_checkins;
            include __DIR__ . '/../includes/components/recent_checkins.php';
            ?>
        </div>
    </div>
</main>

<!-- Confirmation Modal Component -->
<?php include __DIR__ . '/../includes/components/confirmation_modal.php'; ?>

<!-- Camera Scanner JavaScript -->
<script src="../assets/js/camera-scanner.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
