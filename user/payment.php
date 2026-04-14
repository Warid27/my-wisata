<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/XenditService.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

$page_title = 'Pembayaran';

// Get order ID
$id_order = $_GET['order'] ?? 0;
if (!is_numeric($id_order)) {
    set_flash_message('error', 'ID order tidak valid');
    redirect('user/');
}

// Get order details
$query = "SELECT o.*, u.nama as nama_user, u.email, v.kode_voucher, v.potongan as voucher_potongan 
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          LEFT JOIN voucher v ON o.id_voucher = v.id_voucher 
          WHERE o.id_order = ? AND o.id_user = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_order, get_user_id()]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    set_flash_message('error', 'Order tidak ditemukan');
    redirect('user/');
}

// Get order items
$order_details = get_order_details($id_order);

// Process payment - Create Xendit Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    try {
        $xendit = new XenditService();
        
        // Prepare items for Xendit
        $items = [];
        foreach ($order_details as $detail) {
            $items[] = [
                'name' => $detail['nama_tiket'],
                'quantity' => $detail['qty'],
                'price' => $detail['subtotal'] / $detail['qty']
            ];
        }
        
        // Create invoice data
        $invoiceData = [
            'external_id' => 'ORDER-' . str_pad($order['id_order'], 6, '0', STR_PAD_LEFT),
            'amount' => $order['total'],
            'description' => 'Pembayaran Tiket Event #' . str_pad($order['id_order'], 6, '0', STR_PAD_LEFT),
            'customer_name' => $order['nama_user'],
            'customer_email' => $order['email'],
            'items' => $items
        ];
        
        // Create invoice
        $result = $xendit->createInvoice($invoiceData);
        
        if ($result['success']) {
            // Update order with Xendit invoice ID
            $query = "UPDATE orders SET xendit_invoice_id = ?, updated_at = NOW() WHERE id_order = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$result['invoice_id'], $id_order]);
            
            // Redirect to Xendit payment page
            header("Location: " . $result['invoice_url']);
            exit();
        } else {
            set_flash_message('error', 'Gagal membuat pembayaran: ' . ($result['error']['message'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        set_flash_message('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

// Cancel order
if (isset($_POST['cancel_order'])) {
    $query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id_order = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$id_order])) {
        set_flash_message('info', 'Pesanan dibatalkan');
        redirect('user/history.php');
    } else {
        set_flash_message('error', 'Gagal membatalkan pesanan');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Order Status -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Detail Pesanan #<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tanggal Pesan:</strong></p>
                            <p><?php echo format_date($order['tanggal_order'], 'd M Y H:i'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <span class="badge bg-warning fs-6">Menunggu Pembayaran</span>
                                <?php elseif ($order['status'] === 'paid'): ?>
                                    <span class="badge bg-success fs-6">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger fs-6">Dibatalkan</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <!-- Order Items -->
                    <h6 class="mb-3">Detail Tiket</h6>
                    <?php foreach ($order_details as $detail): ?>
                        <div class="row mb-2">
                            <div class="col-8">
                                <?php echo htmlspecialchars($detail['nama_tiket']); ?> x <?php echo $detail['qty']; ?>
                            </div>
                            <div class="col-4 text-end">
                                <?php echo format_currency($detail['subtotal']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <!-- Total -->
                    <div class="row">
                        <div class="col-8">
                            <h5>Subtotal:</h5>
                            <?php if ($order['voucher_potongan']): ?>
                                <h6 class="text-success">Diskon (<?php echo $order['kode_voucher']; ?>):</h6>
                            <?php endif; ?>
                            <h5>Total:</h5>
                        </div>
                        <div class="col-4 text-end">
                            <h5><?php echo format_currency($order['total'] + ($order['voucher_potongan'] ?? 0)); ?></h5>
                            <?php if ($order['voucher_potongan']): ?>
                                <h6 class="text-success">-<?php echo format_currency($order['voucher_potongan']); ?></h6>
                            <?php endif; ?>
                            <h5 class="text-primary"><?php echo format_currency($order['total']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($order['status'] === 'pending'): ?>
                <!-- Xendit Payment -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card"></i> Metode Pembayaran - Xendit
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-bank display-4 text-primary"></i>
                                        <h6 class="mt-2">Virtual Account</h6>
                                        <p class="small text-muted">BCA, BNI, BRI, Mandiri</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-wallet2 display-4 text-primary"></i>
                                        <h6 class="mt-2">E-Wallet</h6>
                                        <p class="small text-muted">GoPay, OVO, Dana, ShopeePay</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-credit-card-2-back display-4 text-primary"></i>
                                        <h6 class="mt-2">Kartu Kredit</h6>
                                        <p class="small text-muted">Visa, Mastercard</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-shop display-4 text-primary"></i>
                                        <h6 class="mt-2">Retail</h6>
                                        <p class="small text-muted">Alfamart, Indomaret</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Informasi Pembayaran:</strong><br>
                            • Pembayaran diproses oleh Xendit (terpercaya dan aman)<br>
                            • Berlaku selama 24 jam sejak invoice dibuat<br>
                            • Anda akan diarahkan ke halaman pembayaran Xendit<br>
                            • Status pembayaran akan update otomatis
                        </div>

                        <form method="POST">
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="cancel_order" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Batalkan Pesanan
                                </button>
                                <button type="submit" name="create_payment" class="btn btn-success btn-lg">
                                    <i class="bi bi-credit-card"></i> Bayar Sekarang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Info -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> Informasi Pemesan
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($order['nama_user']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle"></i> 
                        E-tiket akan dikirim ke email Anda setelah pembayaran dikonfirmasi
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
