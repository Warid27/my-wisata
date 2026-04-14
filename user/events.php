<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Daftar Event';

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$date_filter = $_GET['date'] ?? '';

// Build query
$query = "SELECT e.*, v.nama_venue, v.alamat, 
                 COUNT(t.id_tiket) as total_tiket_types,
                 MIN(t.harga) as min_harga,
                 MAX(t.harga) as max_harga
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          LEFT JOIN tiket t ON e.id_event = t.id_event ";

$params = [];

// Add search filter
if (!empty($search)) {
    $query .= " WHERE (e.nama_event LIKE ? OR e.deskripsi LIKE ? OR v.nama_venue LIKE ?) ";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add date filter
if (!empty($date_filter)) {
    $query .= $search ? " AND " : " WHERE ";
    if ($date_filter === 'upcoming') {
        $query .= " e.tanggal >= CURDATE() ";
    } elseif ($date_filter === 'past') {
        $query .= " e.tanggal < CURDATE() ";
    } elseif ($date_filter === 'today') {
        $query .= " e.tanggal = CURDATE() ";
    } elseif ($date_filter === 'week') {
        $query .= " e.tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ";
    } elseif ($date_filter === 'month') {
        $query .= " e.tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ";
    }
}

$query .= " GROUP BY e.id_event ORDER BY e.tanggal ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">
                <i class="bi bi-calendar-event"></i> Daftar Event
            </h2>
            
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Cari event, venue, atau deskripsi..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="date">
                                <option value="">Semua Tanggal</option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Minggu Ini</option>
                                <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>30 Hari</option>
                                <option value="upcoming" <?php echo $date_filter === 'upcoming' ? 'selected' : ''; ?>>Akan Datang</option>
                                <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Sudah Lewat</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Event List -->
    <?php if (empty($events)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-calendar-x"></i>
            <h5 class="mt-2">Tidak ada event ditemukan</h5>
            <p>Coba ubah filter atau kata kunci pencarian</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
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
                            
                            <?php if ($event['deskripsi']): ?>
                                <p class="card-text text-muted small">
                                    <?php echo substr(htmlspecialchars($event['deskripsi']), 0, 100); ?>...
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <?php if ($event['total_tiket_types'] > 0): ?>
                                    <div class="mb-2">
                                        <?php if ($event['min_harga'] == $event['max_harga']): ?>
                                            <span class="fw-bold text-primary"><?php echo format_currency($event['min_harga']); ?></span>
                                        <?php else: ?>
                                            <span class="fw-bold text-primary">
                                                <?php echo format_currency($event['min_harga']); ?> - <?php echo format_currency($event['max_harga']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <small class="text-muted">/tiket</small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid">
                                    <a href="<?php echo base_url('user/event_detail.php?id=' . $event['id_event']); ?>" 
                                       class="btn btn-primary">
                                        <i class="bi bi-ticket"></i> Lihat Detail & Beli Tiket
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer text-muted">
                            <small>
                                <?php 
                                $today = date('Y-m-d');
                                if ($event['tanggal'] < $today): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> Event Selesai</span>
                                <?php elseif ($event['tanggal'] == $today): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Hari Ini!</span>
                                <?php else: ?>
                                    <span class="text-primary"><i class="bi bi-clock"></i> 
                                        <?php 
                                        $days = (strtotime($event['tanggal']) - strtotime($today)) / 86400;
                                        if ($days == 1) echo 'Besok';
                                        elseif ($days <= 7) echo number_format($days) . ' hari lagi';
                                        else echo format_date($event['tanggal']);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.event-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
