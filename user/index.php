<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Dashboard User';

// Get user's recent orders
$recent_orders = get_user_orders(get_user_id(), 5);

// Get upcoming events
$query = "SELECT e.*, v.nama_venue, COUNT(t.id_tiket) as total_tiket_types 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          WHERE e.tanggal >= CURDATE() 
          GROUP BY e.id_event 
          ORDER BY e.tanggal ASC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="bi bi-person-circle"></i> Selamat Datang, <?php echo get_user_name(); ?>!
                    </h2>
                    <p class="card-text">Temukan event seru dan beli tiket dengan mudah</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-calendar-event display-4 text-primary"></i>
                    <h5 class="card-title mt-2">Lihat Event</h5>
                    <p class="card-text">Jelajahi event yang tersedia</p>
                    <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-primary">
                        Lihat Semua Event
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-ticket display-4 text-success"></i>
                    <h5 class="card-title mt-2">Tiket Saya</h5>
                    <p class="card-text">Kelola tiket yang sudah dibeli</p>
                    <a href="<?php echo base_url('user/my_tickets.php'); ?>" class="btn btn-success">
                        Lihat Tiket
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="bi bi-clock-history display-4 text-info"></i>
                    <h5 class="card-title mt-2">Riwayat</h5>
                    <p class="card-text">Lihat riwayat pembelian</p>
                    <a href="<?php echo base_url('user/history.php'); ?>" class="btn btn-info">
                        Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-3">
                <i class="bi bi-calendar-check"></i> Event Mendatang
            </h3>
            
            <?php if (empty($upcoming_events)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada event mendatang
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card event-card h-100" onclick="window.location.href='<?php echo base_url('user/event_detail.php?id=' . $event['id_event']); ?>'">
                                <?php if ($event['gambar']): ?>
                                    <img src="<?php echo assets_url('images/' . $event['gambar']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($event['nama_event']); ?>" 
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="bi bi-calendar-event display-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['nama_event']); ?></h5>
                                    <p class="card-text">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['nama_venue']); ?><br>
                                        <i class="bi bi-calendar"></i> <?php echo format_date($event['tanggal']); ?><br>
                                        <i class="bi bi-tag"></i> <?php echo $event['total_tiket_types']; ?> jenis tiket
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-outline-primary">
                        Lihat Semua Event <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <h3 class="mb-3">
                <i class="bi bi-receipt"></i> Pesanan Terbaru
            </h3>
            
            <?php if (empty($recent_orders)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Anda belum memiliki pesanan
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID Order</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tiket</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo format_date($order['tanggal_order'], 'd M Y H:i'); ?></td>
                                    <td><?php echo format_currency($order['total']); ?></td>
                                    <td>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($order['status'] === 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $order['total_tiket']; ?> tiket</td>
                                    <td>
                                        <a href="<?php echo base_url('user/my_tickets.php?order=' . $order['id_order']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo base_url('user/history.php'); ?>" class="btn btn-outline-primary">
                        Lihat Semua Pesanan <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
