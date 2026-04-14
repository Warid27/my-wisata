<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get order ID
$id_order = $_GET['id'] ?? 0;
if (!is_numeric($id_order)) {
    echo '<p>ID order tidak valid</p>';
    exit;
}

// Get order details
$query = "SELECT o.*, u.nama as nama_user, u.email, v.kode_voucher, v.potongan as voucher_potongan 
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          LEFT JOIN voucher v ON o.id_voucher = v.id_voucher 
          WHERE o.id_order = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_order]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<p>Order tidak ditemukan</p>';
    exit;
}

// Get order items
$order_details = get_order_details($id_order);
?>

<div class="order-detail">
    <div class="row mb-3">
        <div class="col-md-6">
            <p><strong>ID Order:</strong> #<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></p>
            <p><strong>Tanggal:</strong> <?php echo format_date($order['tanggal_order'], 'd M Y H:i'); ?></p>
        </div>
        <div class="col-md-6 text-end">
            <p><strong>Status:</strong> 
                <?php if ($order['status'] === 'pending'): ?>
                    <span class="badge bg-warning">Menunggu Pembayaran</span>
                <?php elseif ($order['status'] === 'paid'): ?>
                    <span class="badge bg-success">Lunas</span>
                <?php else: ?>
                    <span class="badge bg-danger">Dibatalkan</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <hr>

    <h6>Detail Tiket:</h6>
    <?php foreach ($order_details as $detail): ?>
        <div class="d-flex justify-content-between mb-2">
            <span><?php echo htmlspecialchars($detail['nama_tiket']); ?> x <?php echo $detail['qty']; ?></span>
            <span><?php echo format_currency($detail['subtotal']); ?></span>
        </div>
    <?php endforeach; ?>

    <hr>

    <div class="d-flex justify-content-between">
        <h6>Subtotal:</h6>
        <h6><?php echo format_currency($order['total'] + ($order['voucher_potongan'] ?? 0)); ?></h6>
    </div>
    
    <?php if ($order['voucher_potongan']): ?>
        <div class="d-flex justify-content-between text-success">
            <span>Diskon (<?php echo $order['kode_voucher']; ?>):</span>
            <span>-<?php echo format_currency($order['voucher_potongan']); ?></span>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between">
        <h5>Total:</h5>
        <h5 class="text-primary"><?php echo format_currency($order['total']); ?></h5>
    </div>
</div>
