<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

$page_title = 'Tiket Saya';

// Get specific order tickets if order ID is provided
$id_order = $_GET['order'] ?? null;
$tickets = [];

if ($id_order && is_numeric($id_order)) {
    // Get tickets for specific order
    $query = "SELECT a.*, o.id_order, o.tanggal_order, o.status as order_status,
                     t.nama_tiket, e.nama_event, e.tanggal as event_tanggal, v.nama_venue
              FROM attendee a 
              JOIN order_detail od ON a.id_detail = od.id_detail 
              JOIN orders o ON od.id_order = o.id_order 
              JOIN tiket t ON od.id_tiket = t.id_tiket 
              JOIN event e ON t.id_event = e.id_event 
              JOIN venue v ON e.id_venue = v.id_venue 
              WHERE o.id_order = ? AND o.id_user = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_order, $_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order info
    $query = "SELECT * FROM orders WHERE id_order = ? AND id_user = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_order, $_SESSION['user_id']]);
    $order_info = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Get all user tickets
    $query = "SELECT a.*, o.id_order, o.tanggal_order, o.status as order_status,
                     t.nama_tiket, e.nama_event, e.tanggal as event_tanggal, v.nama_venue
              FROM attendee a 
              JOIN order_detail od ON a.id_detail = od.id_detail 
              JOIN orders o ON od.id_order = o.id_order 
              JOIN tiket t ON od.id_tiket = t.id_tiket 
              JOIN event e ON t.id_event = e.id_event 
              JOIN venue v ON e.id_venue = v.id_venue 
              WHERE o.id_user = ? 
              ORDER BY e.tanggal DESC, o.tanggal_order DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-ticket"></i> Tiket Saya
        </h2>
        <?php if ($id_order): ?>
            <a href="<?php echo base_url('user/my_tickets.php'); ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Lihat Semua Tiket
            </a>
        <?php endif; ?>
    </div>

    <?php if ($id_order && $order_info): ?>
        <!-- Order Info -->
        <div class="alert alert-info">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>Order #<?php echo str_pad($order_info['id_order'], 6, '0', STR_PAD_LEFT); ?></strong>
                    <br>Tanggal: <?php echo format_date($order_info['tanggal_order'], 'd M Y H:i'); ?>
                </div>
                <div>
                    Status: 
                    <?php if ($order_info['status'] === 'pending'): ?>
                        <span class="badge bg-warning">Menunggu Pembayaran</span>
                    <?php elseif ($order_info['status'] === 'paid'): ?>
                        <span class="badge bg-success">Lunas</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Dibatalkan</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-ticket-perforated display-1"></i>
            <h5 class="mt-3">Anda belum memiliki tiket</h5>
            <p>Beli tiket event sekarang!</p>
            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-primary">
                <i class="bi bi-calendar-event"></i> Lihat Event
            </a>
        </div>
    <?php else: ?>
        <!-- Filter Tabs -->
        <?php if (!$id_order): ?>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#upcoming">
                        Akan Datang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#past">
                        Sudah Lewat
                    </a>
                </li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="upcoming">
                    <?php
                    $today = date('Y-m-d');
                    $upcoming_tickets = array_filter($tickets, function($t) use ($today) {
                        return $t['event_tanggal'] >= $today && $t['order_status'] === 'paid';
                    });
                    ?>
                    <?php if (empty($upcoming_tickets)): ?>
                        <p class="text-muted">Tidak ada tiket untuk event mendatang</p>
                    <?php else: ?>
                        <?php foreach ($upcoming_tickets as $ticket): ?>
                            <?php include __DIR__ . '/_ticket_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="tab-pane fade" id="past">
                    <?php
                    $today = date('Y-m-d');
                    $past_tickets = array_filter($tickets, function($t) use ($today) {
                        return $t['event_tanggal'] < $today && $t['order_status'] === 'paid';
                    });
                    ?>
                    <?php if (empty($past_tickets)): ?>
                        <p class="text-muted">Tidak ada tiket untuk event yang sudah lewat</p>
                    <?php else: ?>
                        <?php foreach ($past_tickets as $ticket): ?>
                            <?php include __DIR__ . '/_ticket_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Show all tickets for specific order -->
            <?php foreach ($tickets as $ticket): ?>
                <?php include __DIR__ . '/_ticket_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-code-container"></div>
                <p class="mt-3 mb-0">Kode Tiket: <strong id="ticket-code-display"></strong></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function showQRCode(kodeTiket) {
    document.getElementById('ticket-code-display').textContent = kodeTiket;
    
    // Clear previous QR code
    document.getElementById('qr-code-container').innerHTML = '';
    
    // Generate new QR code
    new QRCode(document.getElementById('qr-code-container'), {
        text: kodeTiket,
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Show modal
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show toast or alert
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '11';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-body">
                    Kode tiket disalin!
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
