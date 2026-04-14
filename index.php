<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Event Ticket Booking System';

// Get featured events (upcoming events)
$query = "SELECT e.*, v.nama_venue, COUNT(t.id_tiket) as total_tiket_types,
                 MIN(t.harga) as min_harga
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          WHERE e.tanggal >= CURDATE() 
          GROUP BY e.id_event 
          ORDER BY e.tanggal ASC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="bi bi-ticket-perforated"></i> 
                    Event Ticket Booking System
                </h1>
                <p class="lead mb-4">
                    Temukan dan beli tiket untuk event favorit Anda dengan mudah dan aman. 
                    Dapatkan pengalaman pemesanan tiket yang menyenangkan!
                </p>
                <div class="d-flex gap-3">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                            <i class="bi bi-calendar-event"></i> Jelajahi Event
                        </a>
                        <?php if (is_admin()): ?>
                            <a href="<?php echo base_url('admin/index.php'); ?>" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-speedometer2"></i> Dashboard Admin
                            </a>
                        <?php else: ?>
                            <a href="<?php echo base_url('user/my_tickets.php'); ?>" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-ticket"></i> Tiket Saya
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                            <i class="bi bi-calendar-event"></i> Jelajahi Event
                        </a>
                        <a href="<?php echo base_url('user/login.php'); ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="bi bi-qr-code display-1 opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5 mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                    <h4>Aman & Terpercaya</h4>
                    <p class="text-muted">Sistem pembayaran yang aman dan perlindungan data pribadi Anda</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-lightning-charge display-4 text-success mb-3"></i>
                    <h4>Fast & Easy</h4>
                    <p class="text-muted">Pesan tiket dengan cepat dalam 3 langkah mudah</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-phone display-4 text-info mb-3"></i>
                    <h4>Mobile Friendly</h4>
                    <p class="text-muted">Akses dari mana saja, kapan saja melalui perangkat Anda</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Events Section -->
<section class="featured-events py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-star"></i> Event Mendatang
            </h2>
            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-outline-primary">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($featured_events)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-calendar-x display-1"></i>
                <h5 class="mt-3">Belum ada event mendatang</h5>
                <p>Nantikan event seru yang akan datang!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($featured_events as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card event-card h-100">
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
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['nama_event']); ?></h5>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['nama_venue']); ?><br>
                                        <i class="bi bi-calendar"></i> <?php echo format_date($event['tanggal']); ?>
                                    </small>
                                </div>
                                
                                <div class="mt-auto">
                                    <?php if ($event['min_harga']): ?>
                                        <div class="mb-2">
                                            <span class="fw-bold text-primary">
                                                Mulai dari <?php echo format_currency($event['min_harga']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid">
                                        <a href="<?php echo base_url('user/event_detail.php?id=' . $event['id_event']); ?>" 
                                           class="btn btn-primary">
                                            <i class="bi bi-ticket"></i> Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section bg-light py-5">
    <div class="container text-center">
        <h2 class="mb-4">Siap untuk Menghadiri Event Seru?</h2>
        <p class="lead mb-4">Bergabunglah dengan ribuan pengguna yang telah menikmati kemudahan pemesanan tiket</p>
        <?php if (!is_logged_in()): ?>
            <a href="<?php echo base_url('user/register.php'); ?>" class="btn btn-primary btn-lg me-3">
                <i class="bi bi-person-plus"></i> Daftar Sekarang
            </a>
            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-calendar-event"></i> Jelajahi Event
            </a>
        <?php else: ?>
            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-calendar-event"></i> Temukan Event
            </a>
        <?php endif; ?>
    </div>
</section>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.feature-card {
    padding: 2rem;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
}

.event-card {
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.cta-section {
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
