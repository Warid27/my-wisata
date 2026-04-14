<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'MyWisata';

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
<section class="hero-section text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="bi bi-ticket-perforated-fill"></i> 
                    MyWisata
                </h1>
                <p class="lead mb-4">
                    Temukan dan beli tiket untuk event favorit Anda dengan mudah dan aman. 
                    Dapatkan pengalaman pemesanan tiket yang menyenangkan!
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                            <i class="bi bi-calendar-event-fill"></i> Jelajahi Event
                        </a>
                        <?php if (is_admin()): ?>
                            <a href="<?php echo base_url('admin/'); ?>" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-speedometer2"></i> Dashboard Admin
                            </a>
                        <?php else: ?>
                            <a href="<?php echo base_url('user/my_tickets.php'); ?>" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-ticket-fill"></i> Tiket Saya
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                            <i class="bi bi-calendar-event-fill"></i> Jelajahi Event
                        </a>
                        <a href="<?php echo base_url('user/login.php'); ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row text-center animate-on-scroll">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-shield-check-fill text-success"></i>
                    <h4>Aman & Terpercaya</h4>
                    <p>Sistem pembayaran yang aman dan perlindungan data pribadi Anda dengan enkripsi SSL</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                    <h4>Fast & Easy</h4>
                    <p>Pesan tiket dengan cepat dalam 3 langkah mudah tanpa perlu registrasi</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <i class="bi bi-phone-fill text-info"></i>
                    <h4>Mobile Friendly</h4>
                    <p>Akses dari mana saja, kapan saja melalui perangkat Anda dengan UI responsif</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Events Section -->
<section class="featured-events py-5">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>
                <i class="bi bi-star-fill text-warning"></i> Event Mendatang
            </h2>
        </div>

        <?php if (empty($featured_events)): ?>
            <div class="alert alert-info text-center animate-on-scroll">
                <i class="bi bi-calendar-x display-1"></i>
                <h5 class="mt-3">Belum ada event mendatang</h5>
                <p>Nantikan event seru yang akan datang!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($featured_events as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4 animate-on-scroll">
                        <div class="card event-card h-100">
                            <?php if ($event['gambar']): ?>
                                <img src="<?php echo assets_url('images/' . $event['gambar']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($event['nama_event']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                     style="height: 240px;">
                                    <i class="bi bi-calendar-event display-1 text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-badge">
                                <i class="bi bi-calendar-check"></i> <?php echo date('M d', strtotime($event['tanggal'])); ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['nama_event']); ?></h5>
                                
                                <div class="event-meta">
                                    <div><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($event['nama_venue']); ?></div>
                                    <div><i class="bi bi-calendar-fill"></i> <?php echo format_date($event['tanggal']); ?></div>
                                </div>
                                
                                <div class="mt-auto">
                                    <?php if ($event['min_harga']): ?>
                                        <div class="event-price mb-3">
                                            Mulai dari <?php echo format_currency($event['min_harga']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid">
                                        <a href="<?php echo base_url('user/event_detail.php?id=' . $event['id_event']); ?>" 
                                           class="btn btn-primary">
                                            <i class="bi bi-ticket-fill"></i> Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-overlay">
                                <a href="<?php echo base_url('user/event_detail.php?id=' . $event['id_event']); ?>" 
                                   class="btn btn-light btn-lg w-100">
                                    <i class="bi bi-eye-fill"></i> Lihat Event
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5 animate-on-scroll">
                <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-calendar-week"></i> Lihat Semua Event
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-card animate-on-scroll">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div class="stat-number" data-target="500">0</div>
                    <div class="stat-label">Event Tersedia</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-card animate-on-scroll">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-number" data-target="10000">0</div>
                    <div class="stat-label">Pengguna Aktif</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-card animate-on-scroll">
                    <div class="stat-icon">
                        <i class="bi bi-ticket-fill"></i>
                    </div>
                    <div class="stat-number" data-target="50000">0</div>
                    <div class="stat-label">Tiket Terjual</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-card animate-on-scroll">
                    <div class="stat-icon">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="stat-number" data-target="98">0</div>
                    <div class="stat-label">% Kepuasan</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container text-center">
        <h2 class="mb-4 animate-on-scroll">Siap untuk Menghadiri Event Seru?</h2>
        <p class="lead mb-4 animate-on-scroll">Bergabunglah dengan ribuan pengguna yang telah menikmati kemudahan pemesanan tiket</p>
        <div class="animate-on-scroll">
            <?php if (!is_logged_in()): ?>
                <a href="<?php echo base_url('user/register.php'); ?>" class="btn btn-light btn-lg me-3">
                    <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                </a>
                <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-calendar-event-fill"></i> Jelajahi Event
                </a>
            <?php else: ?>
                <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                    <i class="bi bi-calendar-event-fill"></i> Temukan Event
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>


<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Fallback script to ensure sections are visible -->
<script>
// Immediately make sections visible if they haven't been animated after 2 seconds
setTimeout(function() {
    document.querySelectorAll('.animate-on-scroll').forEach(function(el) {
        if (!el.classList.contains('animated')) {
            el.classList.add('animated');
        }
    });
}, 2000);
</script>
