<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

$page_title = 'Pemesanan Tiket';

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    set_flash_message('error', 'Keranjang belanja kosong');
    redirect('user/events.php');
}

$cart_items = $_SESSION['cart'];
$total = array_sum(array_column($cart_items, 'subtotal'));

// Process voucher
$discount = 0;
$voucher_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
    $kode_voucher = strtoupper(sanitize($_POST['kode_voucher'] ?? ''));
    
    if (!empty($kode_voucher)) {
        $voucher_data = validate_voucher($kode_voucher);
        
        if ($voucher_data) {
            $discount = $voucher_data['potongan'];
            if ($discount > $total) {
                $discount = $total;
            }
            set_flash_message('success', 'Voucher berhasil digunakan! Potongan: ' . format_currency($discount));
        } else {
            set_flash_message('error', 'Kode voucher tidak valid atau sudah habis');
        }
    }
}

// Remove voucher
if (isset($_POST['remove_voucher'])) {
    $voucher_data = null;
    $discount = 0;
    set_flash_message('info', 'Voucher dihapus');
}

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_order'])) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Check ticket availability again
        foreach ($cart_items as $item) {
            if (!check_ticket_availability($item['id_tiket'], $item['qty'])) {
                throw new Exception('Tiket ' . $item['nama_tiket'] . ' tidak tersedia atau kuota tidak mencukupi');
            }
        }
        
        // Create order
        $id_voucher = $voucher_data ? $voucher_data['id_voucher'] : null;
        $final_total = $total - $discount;
        
        $query = "INSERT INTO orders (id_user, total, status, id_voucher) VALUES (?, ?, 'pending', ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([get_user_id(), $final_total, $id_voucher]);
        $id_order = $db->lastInsertId();
        
        // Create order details and generate tickets
        foreach ($cart_items as $item) {
            // Insert order detail
            $query = "INSERT INTO order_detail (id_order, id_tiket, qty, subtotal) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_order, $item['id_tiket'], $item['qty'], $item['subtotal']]);
            $id_detail = $db->lastInsertId();
            
            // Generate unique tickets
            for ($i = 0; $i < $item['qty']; $i++) {
                $kode_tiket = generate_ticket_code();
                $query = "INSERT INTO attendee (id_detail, kode_tiket) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$id_detail, $kode_tiket]);
            }
        }
        
        // Use voucher if applied
        if ($voucher_data) {
            use_voucher($voucher_data['id_voucher']);
        }
        
        $db->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        set_flash_message('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran');
        redirect('user/payment.php?order=' . $id_order);
        
    } catch (Exception $e) {
        $db->rollBack();
        set_flash_message('error', $e->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Cart Items -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cart"></i> Keranjang Belanja
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $index => $item): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_tiket']); ?></h6>
                                <small class="text-muted"><?php echo format_currency($item['harga']); ?> x <?php echo $item['qty']; ?></small>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-end"><?php echo format_currency($item['subtotal']); ?></h6>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php 
                                    // Get event_id from first cart item
                                    $event_id = !empty($cart_items) ? $cart_items[0]['id_event'] : '';
                                ?>
                                <a href="<?php echo base_url('user/event_detail.php?id=' . $event_id); ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Ubah
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Voucher -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-ticket-detailed"></i> Kode Voucher
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($voucher_data): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-check-circle"></i>
                                <strong><?php echo htmlspecialchars($voucher_data['kode_voucher']); ?></strong> 
                                - Potongan <?php echo format_currency($discount); ?>
                            </div>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="remove_voucher" class="btn btn-sm btn-outline-danger">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="input-group">
                                <input type="text" class="form-control" name="kode_voucher" 
                                       placeholder="Masukkan kode voucher" style="text-transform: uppercase;">
                                <button type="submit" name="apply_voucher" class="btn btn-outline-primary">
                                    Gunakan
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card shadow position-sticky" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Ringkasan Pesanan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo format_currency($total); ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Diskon Voucher:</span>
                            <span>-<?php echo format_currency($discount); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Total:</h5>
                        <h5 class="text-primary"><?php echo format_currency($total - $discount); ?></h5>
                    </div>

                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melanjutkan pemesanan?');">
                        <button type="submit" name="process_order" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock"></i> Lanjut ke Pembayaran
                        </button>
                    </form>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Dengan melanjutkan, Anda setuju dengan syarat dan ketentuan yang berlaku
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
