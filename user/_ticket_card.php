<?php
// This file is included in my_tickets.php
// $ticket variable should be available
?>
<div class="card shadow mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="card-title mb-1"><?php echo htmlspecialchars($ticket['nama_event']); ?></h5>
                <p class="card-text">
                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($ticket['nama_tiket']); ?><br>
                    <i class="bi bi-calendar"></i> <?php echo format_date($ticket['event_tanggal']); ?><br>
                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ticket['nama_venue']); ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($ticket['order_status'] === 'pending'): ?>
                    <span class="badge bg-warning mb-2">Menunggu Pembayaran</span>
                    <br>
                    <a href="<?php echo base_url('user/payment.php?order=' . $ticket['id_order']); ?>" 
                       class="btn btn-sm btn-primary">
                        <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </a>
                <?php elseif ($ticket['order_status'] === 'paid'): ?>
                    <?php if ($ticket['event_tanggal'] < date('Y-m-d')): ?>
                        <span class="badge bg-secondary mb-2">Event Selesai</span>
                    <?php else: ?>
                        <span class="badge bg-success mb-2">Aktif</span>
                    <?php endif; ?>
                    <br>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                            onclick="showQRCode('<?php echo $ticket['kode_tiket']; ?>')">
                        <i class="bi bi-qr-code"></i> QR
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                            onclick="copyToClipboard('<?php echo $ticket['kode_tiket']; ?>')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                <?php else: ?>
                    <span class="badge bg-danger mb-2">Dibatalkan</span>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">Kode Tiket:</small>
                <div class="ticket-code"><?php echo $ticket['kode_tiket']; ?></div>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">Status Check-in:</small>
                <div>
                    <?php if ($ticket['status_checkin'] === 'sudah'): ?>
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle"></i> Sudah Check-in
                        </span>
                        <br><small><?php echo format_date($ticket['waktu_checkin'], 'd M Y H:i'); ?></small>
                    <?php else: ?>
                        <span class="badge bg-warning">
                            <i class="bi bi-clock"></i> Belum Check-in
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
