<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Dashboard User';

// Get user statistics
$query = "SELECT 
            COUNT(DISTINCT o.id_order) as total_orders,
            COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.total ELSE 0 END), 0) as total_spent,
            COUNT(DISTINCT CASE WHEN e.tanggal >= CURDATE() THEN e.id_event END) as upcoming_events,
            COUNT(DISTINCT a.id_attendee) as total_tickets
          FROM users u 
          LEFT JOIN orders o ON u.id_user = o.id_user
          LEFT JOIN order_detail od ON o.id_order = od.id_order
          LEFT JOIN attendee a ON od.id_detail = a.id_detail
          LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
          LEFT JOIN event e ON t.id_event = e.id_event
          WHERE u.id_user = ?";
$stmt = $db->prepare($query);
$stmt->execute([get_user_id()]);
$user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$recent_orders = get_user_orders(get_user_id(), 5);

// Get upcoming events with tickets
$query = "SELECT DISTINCT e.*, a.kode_tiket, e.tanggal as event_date
          FROM attendee a 
          JOIN order_detail od ON a.id_detail = od.id_detail 
          JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
          JOIN tiket t ON od.id_tiket = t.id_tiket 
          JOIN event e ON t.id_event = e.id_event 
          WHERE o.id_user = ? AND e.tanggal >= CURDATE() AND a.status_checkin = 'belum'
          ORDER BY e.tanggal ASC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([get_user_id()]);
$upcoming_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get purchase history for chart
$query = "SELECT DATE_FORMAT(o.tanggal_order, '%Y-%m') as month, 
                COUNT(o.id_order) as orders, 
                SUM(o.total) as revenue
          FROM orders o 
          WHERE o.id_user = ? AND o.status = 'paid' 
                AND o.tanggal_order >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(o.tanggal_order, '%Y-%m')
          ORDER BY month ASC";
$stmt = $db->prepare($query);
$stmt->execute([get_user_id()]);
$purchase_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="card-title">
                                <i class="bi bi-person-circle"></i> Selamat Datang, <?php echo get_user_name(); ?>!
                            </h2>
                            <p class="card-text">Kelola tiket dan pantau aktivitas Anda</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?php echo base_url('user/events.php'); ?>" class="btn btn-light btn-lg">
                                <i class="bi bi-search"></i> Cari Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Pesanan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($user_stats['total_orders']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cart-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Pengeluaran</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo format_currency($user_stats['total_spent']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-wallet2 fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tiket Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($user_stats['total_tickets']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-ticket fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Event Mendatang</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($user_stats['upcoming_events']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-event fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row">
        <!-- Purchase History Chart -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Pembelian (6 Bulan Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="purchaseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?php echo base_url('user/events.php'); ?>" class="list-group-item list-group-item-action">
                            <i class="bi bi-search"></i> Cari Event
                        </a>
                        <a href="<?php echo base_url('user/my_tickets.php'); ?>" class="list-group-item list-group-item-action">
                            <i class="bi bi-ticket"></i> Lihat Tiket Saya
                        </a>
                        <a href="<?php echo base_url('user/history.php'); ?>" class="list-group-item list-group-item-action">
                            <i class="bi bi-clock-history"></i> Riwayat Pembelian
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person"></i> Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Tickets & Recent Orders -->
    <div class="row">
        <!-- Upcoming Tickets -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tiket Akan Datang</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_tickets)): ?>
                        <p class="text-muted">Anda tidak memiliki tiket untuk event mendatang</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Tanggal</th>
                                        <th>Kode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_tickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ticket['nama_event']); ?></td>
                                            <td><?php echo format_date($ticket['event_date']); ?></td>
                                            <td><code><?php echo substr($ticket['kode_tiket'], 0, 8); ?>...</code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pesanan Terbaru</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">Belum ada pesanan</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tanggal</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo format_date($order['tanggal_order'], 'd M'); ?></td>
                                            <td><?php echo format_currency($order['total']); ?></td>
                                            <td>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" value="<?php echo get_user_name(); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo get_user_email(); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
                        <input type="password" class="form-control" id="password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="updateProfile()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<script>
// Purchase History Chart
const ctx = document.getElementById('purchaseChart').getContext('2d');
const purchaseChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($purchase_history, 'month')); ?>,
        datasets: [{
            label: 'Pengeluaran (Rp)',
            data: <?php echo json_encode(array_column($purchase_history, 'revenue')); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});

// Update Profile
function updateProfile() {
    const nama = document.getElementById('nama').value;
    const password = document.getElementById('password').value;
    
    const formData = new FormData();
    formData.append('nama', nama);
    if (password) {
        formData.append('password', password);
    }
    
    fetch('<?php echo base_url('api/update_profile.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profil berhasil diperbarui');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
