<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components/page_header.php';
require_once __DIR__ . '/../includes/components/search_filter.php';
require_once __DIR__ . '/../includes/components/data_table.php';
require_once __DIR__ . '/../includes/components/pagination.php';

require_staff();

$page_title = 'Manajemen Pesanan';

// Get filters and search
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT o.*, u.nama as nama_user, u.email, 
                 COUNT(a.id_attendee) as total_tiket,
                 v.kode_voucher, v.potongan as voucher_potongan
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          LEFT JOIN voucher v ON o.id_voucher = v.id_voucher 
          LEFT JOIN order_detail od ON o.id_order = od.id_order 
          LEFT JOIN attendee a ON od.id_detail = a.id_detail ";

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(o.id_order LIKE ? OR u.nama LIKE ? OR u.email LIKE ? OR v.kode_voucher LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_conditions[] = "o.tanggal_order >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where_conditions[] = "o.tanggal_order <= ?";
    $params[] = $date_to . ' 23:59:59';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

// Get total orders
$count_query = "SELECT COUNT(DISTINCT o.id_order) as total FROM orders o 
               JOIN users u ON o.id_user = u.id_user 
               LEFT JOIN voucher v ON o.id_voucher = v.id_voucher 
               LEFT JOIN order_detail od ON o.id_order = od.id_order 
               LEFT JOIN attendee a ON od.id_detail = a.id_detail";

if (!empty($where_conditions)) {
    $count_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $per_page);

$query .= " GROUP BY o.id_order ORDER BY o.tanggal_order DESC LIMIT $offset, $per_page";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add formatted data for each order
foreach ($orders as &$order) {
    $order['formatted_id'] = '#' . str_pad($order['id_order'], 6, '0', STR_PAD_LEFT);
    
    // Add status badge
    if ($order['status'] === 'pending') {
        $order['status_badge'] = '<span class="badge bg-warning">Menunggu Pembayaran</span>';
        $order['status_text'] = 'Menunggu Pembayaran';
    } elseif ($order['status'] === 'paid') {
        $order['status_badge'] = '<span class="badge bg-success">Lunas</span>';
        $order['status_text'] = 'Lunas';
    } else {
        $order['status_badge'] = '<span class="badge bg-danger">Dibatalkan</span>';
        $order['status_text'] = 'Dibatalkan';
    }
    
    // Format voucher display
    if ($order['kode_voucher']) {
        $order['voucher_display'] = '<small class="text-success">' . htmlspecialchars($order['kode_voucher']) . '<br>-' . format_currency($order['voucher_potongan']) . '</small>';
    } else {
        $order['voucher_display'] = '-';
    }
}

// Update order status
if (isset($_POST['update_status']) && is_numeric($_POST['order_id'])) {
    $id_order = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'paid', 'cancelled'])) {
        $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id_order = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$new_status, $id_order])) {
            set_flash_message('success', 'Status pesanan berhasil diperbarui');
        } else {
            set_flash_message('error', 'Gagal memperbarui status pesanan');
        }
    }
    
    redirect('admin/orders.php');
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <?php
    // Page Header Component
    render_page_header([
        'title' => 'Manajemen Pesanan',
        'subtitle' => 'Kelola semua pesanan tiket'
    ]);
    ?>

    <?php
    // Search Filter Component with enhanced filters
    render_search_filter([
        'placeholder' => 'Cari berdasarkan ID order, nama user, email, atau kode voucher...',
        'search_value' => $search,
        'filters' => [
            [
                'name' => 'status',
                'type' => 'select',
                'options' => [
                    '' => 'Semua Status',
                    'pending' => 'Menunggu Pembayaran',
                    'paid' => 'Lunas',
                    'cancelled' => 'Dibatalkan'
                ],
                'value' => $status
            ],
            [
                'name' => 'date_from',
                'type' => 'date',
                'value' => $date_from,
                'placeholder' => 'Dari Tanggal'
            ],
            [
                'name' => 'date_to',
                'type' => 'date',
                'value' => $date_to,
                'placeholder' => 'Sampai Tanggal'
            ]
        ],
        'button_text' => 'Filter'
    ]);
    ?>

    <?php
    // Data Table Component
    render_data_table([
        'title' => 'Daftar Pesanan',
        'data' => $orders,
        'total_count' => $total_orders,
        'empty_message' => 'Tidak ada pesanan ditemukan',
        'empty_icon' => 'bi-receipt',
        'columns' => [
            [
                'key' => 'formatted_id',
                'label' => 'ID Order',
                'type' => 'text'
            ],
            [
                'key' => 'tanggal_order',
                'label' => 'Tanggal',
                'type' => 'date',
                'format' => 'd M Y H:i'
            ],
            [
                'key' => 'nama_user',
                'label' => 'User',
                'type' => 'avatar',
                'subtitle' => 'email'
            ],
            [
                'key' => 'total_tiket',
                'label' => 'Tiket',
                'type' => 'text'
            ],
            [
                'key' => 'total',
                'label' => 'Total',
                'type' => 'text',
                'format' => 'currency'
            ],
            [
                'key' => 'voucher_display',
                'label' => 'Voucher',
                'type' => 'html'
            ],
            [
                'key' => 'status_badge',
                'label' => 'Status',
                'type' => 'html'
            ],
            [
                'key' => 'actions',
                'label' => 'Aksi',
                'type' => 'actions'
            ]
        ],
        'actions' => [
            [
                'label' => 'Detail',
                'icon' => 'bi-eye',
                'class' => 'btn btn-sm btn-outline-info',
                'onclick' => 'showOrderDetail({id})',
                'id_key' => 'id_order'
            ],
            [
                'label' => 'Konfirmasi',
                'icon' => 'bi-check',
                'class' => 'btn btn-sm btn-success',
                'onclick' => 'confirmPayment({id})',
                'id_key' => 'id_order',
                'condition' => [
                    'field' => 'status',
                    'operator' => '==',
                    'value' => 'pending'
                ]
            ],
            [
                'label' => 'Batalkan',
                'icon' => 'bi-x',
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'cancelOrder({id})',
                'id_key' => 'id_order',
                'condition' => [
                    'field' => 'status',
                    'operator' => '==',
                    'value' => 'pending'
                ]
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination Component
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_orders,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'orders.php',
        'query_params' => [
            'search' => $search,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]
    ]);
    ?>
</main>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>



<script>
function showOrderDetail(orderId) {
    fetch('<?php echo base_url('api/order_detail.php'); ?>?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
        })
        .catch(error => {
            showNotification('Gagal memuat detail order', 'error');
        });
}

function confirmPayment(id) {
    showConfirmation('Konfirmasi pembayaran order ini?', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${id}">
            <input type="hidden" name="status" value="paid">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }, {isDanger: false});
}

function cancelOrder(id) {
    showConfirmation('Batalkan order ini?', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${id}">
            <input type="hidden" name="status" value="cancelled">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }, {isDanger: true});
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
