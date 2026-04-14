<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'HOTS Analysis';

// Task 22: Mencegah pembelian melebihi kuota tiket
function check_quota_prevention() {
    global $db;
    
    // Get tickets with high demand
    $query = "SELECT t.id_tiket, t.nama_tiket, t.kuota, 
                     COALESCE(SUM(od.qty), 0) as sold,
                     e.nama_event
              FROM tiket t
              JOIN event e ON t.id_event = e.id_event
              LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket
              LEFT JOIN orders o ON od.id_order = o.id_order AND o.status != 'cancelled'
              GROUP BY t.id_tiket
              HAVING sold >= (kuota * 0.9)
              ORDER BY (sold / kuota) DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Task 23: Query total tiket terjual per event
function get_tickets_sold_per_event() {
    global $db;
    
    $query = "SELECT e.id_event, e.nama_event, e.tanggal,
                     COUNT(DISTINCT o.id_order) as total_orders,
                     SUM(od.qty) as total_tickets,
                     SUM(od.subtotal) as total_revenue,
                     COUNT(DISTINCT o.id_user) as unique_customers
              FROM event e
              LEFT JOIN tiket t ON e.id_event = t.id_event
              LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket
              LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
              GROUP BY e.id_event
              ORDER BY total_tickets DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Task 24: Riwayat pembelian user (already implemented in user/history.php)
function get_user_purchase_analytics() {
    global $db;
    
    $query = "SELECT u.id_user, u.nama, u.email,
                     COUNT(o.id_order) as total_orders,
                     SUM(o.total) as total_spent,
                     COUNT(DISTINCT e.id_event) as unique_events,
                     MAX(o.tanggal_order) as last_purchase
              FROM users u
              LEFT JOIN orders o ON u.id_user = o.id_user AND o.status = 'paid'
              LEFT JOIN order_detail od ON o.id_order = od.id_order
              LEFT JOIN tiket t ON od.id_tiket = t.id_tiket
              LEFT JOIN event e ON t.id_event = e.id_event
              WHERE u.role = 'user'
              GROUP BY u.id_user
              ORDER BY total_spent DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Task 25: Analisis voucher tanpa batasan kuota
function analyze_voucher_impact() {
    global $db;
    
    // Get voucher usage statistics
    $query = "SELECT v.kode_voucher, v.potongan, v.kuota, v.status,
                     COUNT(o.id_order) as times_used,
                     SUM(o.total) as total_sales_with_voucher,
                     SUM(v.potongan) as total_discount_given
              FROM voucher v
              LEFT JOIN orders o ON v.id_voucher = o.id_voucher AND o.status = 'paid'
              GROUP BY v.id_voucher
              ORDER BY times_used DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get analytics data
$high_demand_tickets = check_quota_prevention();
$tickets_per_event = get_tickets_sold_per_event();
$top_users = get_user_purchase_analytics();
$voucher_analysis = analyze_voucher_impact();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/'); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/reports.php'); ?>">
                            <i class="bi bi-graph-up"></i> Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/hots_analysis.php'); ?>">
                            <i class="bi bi-graph-up-arrow"></i> HOTS Analysis
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">HOTS (Higher Order Thinking Skills) Analysis</h1>
            </div>

            <!-- Task 22: Quota Prevention -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-exclamation"></i> Task 22: Pencegahan Pembelian Melebihi Kuota
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Implementasi:</strong> Sistem menggunakan <code>SELECT FOR UPDATE</code> untuk lock tiket saat transaksi, 
                        memastikan tidak ada pembelian duplikat yang melebihi kuota.
                    </p>
                    
                    <h6>Tiket dengan Permintaan Tinggi (Kuota Hampir Habis):</h6>
                    <?php if (empty($high_demand_tickets)): ?>
                        <p class="text-muted">Tidak ada tiket dengan permintaan tinggi saat ini</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Tiket</th>
                                        <th>Kuota</th>
                                        <th>Terjual</th>
                                        <th>Sisa</th>
                                        <th>Utilization</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($high_demand_tickets as $ticket): ?>
                                        <?php $utilization = ($ticket['sold'] / $ticket['kuota']) * 100; ?>
                                        <tr class="<?php echo $utilization >= 100 ? 'table-danger' : ($utilization >= 95 ? 'table-warning' : ''); ?>">
                                            <td><?php echo htmlspecialchars($ticket['nama_event']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['nama_tiket']); ?></td>
                                            <td><?php echo number_format($ticket['kuota']); ?></td>
                                            <td><?php echo number_format($ticket['sold']); ?></td>
                                            <td><?php echo number_format($ticket['kuota'] - $ticket['sold']); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?php echo $utilization >= 100 ? 'bg-danger' : ($utilization >= 95 ? 'bg-warning' : 'bg-success'); ?>" 
                                                         style="width: <?php echo min($utilization, 100); ?>%">
                                                        <?php echo number_format($utilization, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($utilization >= 100): ?>
                                                    <span class="badge bg-danger">HABIS</span>
                                                <?php elseif ($utilization >= 95): ?>
                                                    <span class="badge bg-warning">KRITIS</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">AMAN</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Rekomendasi:</strong> Pertimbangkan untuk menambah kuota atau membuat event seru lainnya untuk tiket yang hampir habis.
                    </div>
                </div>
            </div>

            <!-- Task 23: Total Tiket Terjual per Event -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart"></i> Task 23: Total Tiket Terjual per Event
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Query:</strong> 
                        <code class="d-block bg-light p-2 mt-2">
                            SELECT e.nama_event, SUM(od.qty) as total_tickets<br>
                            FROM event e<br>
                            LEFT JOIN tiket t ON e.id_event = t.id_event<br>
                            LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket<br>
                            LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'<br>
                            GROUP BY e.id_event
                        </code>
                    </p>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Tanggal</th>
                                    <th>Pesanan</th>
                                    <th>Tiket Terjual</th>
                                    <th>Pendapatan</th>
                                    <th>Pembeli Unik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets_per_event as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['nama_event']); ?></td>
                                        <td><?php echo format_date($event['tanggal']); ?></td>
                                        <td><?php echo number_format($event['total_orders']); ?></td>
                                        <td class="fw-bold"><?php echo number_format($event['total_tickets']); ?></td>
                                        <td><?php echo format_currency($event['total_revenue']); ?></td>
                                        <td><?php echo number_format($event['unique_customers']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Task 24: Riwayat Pembelian User -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> Task 24: Analytics Riwayat Pembelian User
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Fitur:</strong> Riwayat pembelian user telah diimplementasikan di <code>user/history.php</code>
                    </p>
                    
                    <h6>Top 10 Pelanggan (Berdasarkan Total Pengeluaran):</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Total Pesanan</th>
                                    <th>Total Pengeluaran</th>
                                    <th>Event Diikuti</th>
                                    <th>Terakhir Beli</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo number_format($user['total_orders']); ?></td>
                                        <td class="fw-bold"><?php echo format_currency($user['total_spent']); ?></td>
                                        <td><?php echo number_format($user['unique_events']); ?></td>
                                        <td><?php echo $user['last_purchase'] ? format_date($user['last_purchase'], 'd M Y') : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Task 25: Analisis Voucher -->
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-ticket-detailed"></i> Task 25: Analisis Dampak Voucher Tanpa Batasan Kuota
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Analisis:</strong> Jika voucher tidak dibatasi kuota:
                    </p>
                    
                    <ul class="mb-3">
                        <li><strong>Keuntungan:</strong> Meningkatkan konversi pembelian</li>
                        <li><strong>Kerugian:</strong> Potensi kerugian finansial tidak terbatas</li>
                        <li><strong>Solusi:</strong> Sistem menggunakan batasan kuota untuk mengontrol diskon</li>
                    </ul>
                    
                    <h6>Statistik Penggunaan Voucher:</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode Voucher</th>
                                    <th>Potongan</th>
                                    <th>Kuota</th>
                                    <th>Digunakan</th>
                                    <th>Sisa</th>
                                    <th>Total Penjualan</th>
                                    <th>Total Diskon</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voucher_analysis as $voucher): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($voucher['kode_voucher']); ?></code></td>
                                        <td><?php echo format_currency($voucher['potongan']); ?></td>
                                        <td><?php echo number_format($voucher['kuota']); ?></td>
                                        <td><?php echo number_format($voucher['times_used']); ?></td>
                                        <td><?php echo number_format($voucher['kuota'] - $voucher['times_used']); ?></td>
                                        <td><?php echo format_currency($voucher['total_sales_with_voucher']); ?></td>
                                        <td class="text-danger">-<?php echo format_currency($voucher['total_discount_given']); ?></td>
                                        <td>
                                            <?php if ($voucher['status'] === 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Impact Analysis:</strong> Total diskon yang diberikan: 
                        <strong class="text-danger">
                            <?php echo format_currency(array_sum(array_column($voucher_analysis, 'total_discount_given'))); ?>
                        </strong>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include __DIR__ . '/../includes/footer.php'; ?>
