<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Detail Event';

// Get event ID
$id_event = $_GET['id'] ?? 0;
if (!is_numeric($id_event)) {
    set_flash_message('error', 'ID event tidak valid');
    redirect('user/events.php');
}

// Get event details with venue
$event = get_event_with_venue($id_event);
if (!$event) {
    set_flash_message('error', 'Event tidak ditemukan');
    redirect('user/events.php');
}

// Get tickets for this event
$tickets = get_tickets_by_event($id_event);

// Process add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        set_flash_message('error', 'Silakan login terlebih dahulu untuk memesan tiket');
        redirect('user/login.php');
    }
    
    $cart_items = [];
    $has_items = false;
    
    foreach ($tickets as $ticket) {
        $qty_key = 'qty_' . $ticket['id_tiket'];
        $qty = (int)($_POST[$qty_key] ?? 0);
        
        if ($qty > 0) {
            // Check availability
            if (!check_ticket_availability($ticket['id_tiket'], $qty)) {
                set_flash_message('error', 'Tiket ' . $ticket['nama_tiket'] . ' tidak tersedia atau kuota tidak mencukupi');
                continue;
            }
            
            $cart_items[] = [
                'id_tiket' => $ticket['id_tiket'],
                'nama_tiket' => $ticket['nama_tiket'],
                'harga' => $ticket['harga'],
                'qty' => $qty,
                'subtotal' => $ticket['harga'] * $qty,
                'id_event' => $id_event
            ];
            $has_items = true;
        }
    }
    
    if ($has_items) {
        $_SESSION['cart'] = $cart_items;
        redirect('user/order.php');
    } else {
        set_flash_message('error', 'Pilih minimal 1 tiket untuk memesan');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Event Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo base_url('user/events.php'); ?>">Event</a>
                    </li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($event['nama_event']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Event Image -->
        <div class="col-lg-5 mb-4">
            <?php if ($event['gambar']): ?>
                <img src="<?php echo assets_url('images/' . $event['gambar']); ?>" 
                     alt="<?php echo htmlspecialchars($event['nama_event']); ?>" 
                     class="img-fluid rounded shadow" style="width: 100%;">
            <?php else: ?>
                <div class="bg-light rounded shadow d-flex align-items-center justify-content-center" 
                     style="height: 400px;">
                    <i class="bi bi-calendar-event display-1 text-muted"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Event Info -->
        <div class="col-lg-7 mb-4">
            <h1><?php echo htmlspecialchars($event['nama_event']); ?></h1>
            
            <div class="mb-3">
                <span class="badge bg-primary fs-6">
                    <?php 
                    $today = date('Y-m-d');
                    if ($event['tanggal'] < $today): ?>
                        Event Selesai
                    <?php elseif ($event['tanggal'] == $today): ?>
                        Hari Ini!
                    <?php else: ?>
                        <?php 
                        $days = (strtotime($event['tanggal']) - strtotime($today)) / 86400;
                        if ($days == 1) echo 'Besok';
                        elseif ($days <= 7) echo number_format($days) . ' hari lagi';
                        else echo format_date($event['tanggal']);
                        ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="event-info mb-4">
                <p class="mb-2">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <strong>Tanggal:</strong> <?php echo format_date($event['tanggal']); ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-geo-alt text-danger"></i>
                    <strong>Tempat:</strong> <?php echo htmlspecialchars($event['nama_venue']); ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-map text-success"></i>
                    <strong>Alamat:</strong> <?php echo htmlspecialchars($event['alamat']); ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-people text-info"></i>
                    <strong>Kapasitas:</strong> <?php echo number_format($event['kapasitas']); ?> orang
                </p>
            </div>

            <?php if ($event['deskripsi']): ?>
                <div class="mb-4">
                    <h5>Deskripsi Event</h5>
                    <p><?php echo nl2br(htmlspecialchars($event['deskripsi'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Selection -->
    <?php if (!empty($tickets)): ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-ticket"></i> Pilih Tiket
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php foreach ($tickets as $ticket): ?>
                        <?php 
                        // Get available tickets
                        $query = "SELECT kuota - COALESCE(SUM(od.qty), 0) as available 
                                  FROM tiket t 
                                  LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
                                  LEFT JOIN orders o ON od.id_order = o.id_order AND o.status != 'cancelled'
                                  WHERE t.id_tiket = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$ticket['id_tiket']]);
                        $available = $stmt->fetch(PDO::FETCH_ASSOC)['available'];
                        ?>
                        
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($ticket['nama_tiket']); ?></h6>
                                <small class="text-muted">
                                    Tersedia: <span class="fw-bold <?php echo $available <= 10 ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo number_format($available); ?> tiket
                                    </span>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <h5 class="text-primary mb-0"><?php echo format_currency($ticket['harga']); ?></h5>
                                <small class="text-muted">per tiket</small>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <button type="button" class="btn btn-outline-secondary" onclick="decreaseQty(<?php echo $ticket['id_tiket']; ?>, <?php echo $available; ?>)">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" 
                                           id="qty_<?php echo $ticket['id_tiket']; ?>" 
                                           name="qty_<?php echo $ticket['id_tiket']; ?>" 
                                           value="0" min="0" max="<?php echo $available; ?>" 
                                           onchange="updateSubtotal(<?php echo $ticket['id_tiket']; ?>, <?php echo $ticket['harga']; ?>)">
                                    <button type="button" class="btn btn-outline-secondary" onclick="increaseQty(<?php echo $ticket['id_tiket']; ?>, <?php echo $available; ?>)">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">Subtotal:</small>
                                <h6 class="mb-0 text-end" id="subtotal_<?php echo $ticket['id_tiket']; ?>">
                                    <?php echo format_currency(0); ?>
                                </h6>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Total -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between">
                                <h5>Total:</h5>
                                <h5 class="text-primary" id="total_price"><?php echo format_currency(0); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                <i class="bi bi-cart-plus"></i> Pesan Sekarang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            Belum ada tiket tersedia untuk event ini
        </div>
    <?php endif; ?>
</div>

<script>
function increaseQty(ticketId, maxQty) {
    const input = document.getElementById('qty_' + ticketId);
    const currentQty = parseInt(input.value) || 0;
    if (currentQty < maxQty) {
        input.value = currentQty + 1;
        input.dispatchEvent(new Event('change'));
    }
}

function decreaseQty(ticketId, maxQty) {
    const input = document.getElementById('qty_' + ticketId);
    const currentQty = parseInt(input.value) || 0;
    if (currentQty > 0) {
        input.value = currentQty - 1;
        input.dispatchEvent(new Event('change'));
    }
}

function updateSubtotal(ticketId, price) {
    const qty = parseInt(document.getElementById('qty_' + ticketId).value) || 0;
    const subtotal = qty * price;
    document.getElementById('subtotal_' + ticketId).textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    
    updateTotal();
}

function updateTotal() {
    let total = 0;
    <?php foreach ($tickets as $ticket): ?>
        const qty<?php echo $ticket['id_tiket']; ?> = parseInt(document.getElementById('qty_<?php echo $ticket['id_tiket']; ?>').value) || 0;
        total += qty<?php echo $ticket['id_tiket']; ?> * <?php echo $ticket['harga']; ?>;
    <?php endforeach; ?>
    
    document.getElementById('total_price').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
