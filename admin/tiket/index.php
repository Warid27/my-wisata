<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/page_header.php';
require_once __DIR__ . '/../../includes/components/search_filter.php';
require_once __DIR__ . '/../../includes/components/data_table.php';
require_once __DIR__ . '/../../includes/components/pagination.php';

require_admin();

$page_title = 'Manajemen Tiket';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_tiket = $_GET['delete'];
    
    // Check if ticket has orders
    $query = "SELECT COUNT(*) as total FROM order_detail WHERE id_tiket = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_tiket]);
    $has_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_orders) {
        set_flash_message('error', 'Tiket tidak dapat dihapus karena sudah ada pesanan');
    } else {
        $query = "DELETE FROM tiket WHERE id_tiket = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_tiket])) {
            set_flash_message('success', 'Tiket berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus tiket');
        }
    }
    
    redirect('admin/tiket/');
}

// Get tickets with pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE t.nama_tiket LIKE ? OR e.nama_event LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total tickets
$count_query = "SELECT COUNT(*) as total FROM tiket t JOIN event e ON t.id_event = e.id_event $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_tickets / $per_page);

// Get tickets list
$query = "SELECT t.*, e.nama_event, e.tanggal 
          FROM tiket t 
          JOIN event e ON t.id_event = e.id_event 
          $where
          ORDER BY e.tanggal DESC, t.nama_tiket LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add sold tickets count and status for each ticket
foreach ($tickets as &$ticket) {
    // Get sold tickets count
    $query = "SELECT COALESCE(SUM(od.qty), 0) as sold 
              FROM order_detail od 
              JOIN orders o ON od.id_order = o.id_order 
              WHERE od.id_tiket = ? AND o.status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->execute([$ticket['id_tiket']]);
    $sold = $stmt->fetch(PDO::FETCH_ASSOC)['sold'];
    $sisa = $ticket['kuota'] - $sold;
    
    $ticket['sold'] = $sold;
    $ticket['sisa'] = $sisa;
    
    // Add status badge
    if ($sisa <= 0) {
        $ticket['status_badge'] = '<span class="badge bg-danger">Habis</span>';
        $ticket['status_text'] = 'Habis';
    } elseif ($sisa <= 10) {
        $ticket['status_badge'] = '<span class="badge bg-warning">' . number_format($sisa) . '</span>';
        $ticket['status_text'] = 'Terbatas';
    } else {
        $ticket['status_badge'] = '<span class="badge bg-success">' . number_format($sisa) . '</span>';
        $ticket['status_text'] = 'Tersedia';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <?php
    // Page Header Component
    render_page_header([
        'title' => 'Manajemen Tiket',
        'subtitle' => 'Kelola data tiket event',
        'actions' => [
            [
                'label' => 'Tambah Tiket',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'type' => 'link',
                'href' => base_url('admin/tiket/create.php')
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter Component
    render_search_filter([
        'placeholder' => 'Cari berdasarkan nama tiket atau event...',
        'search_value' => $search,
        'action_url' => '',
        'method' => 'GET',
        'show_reset' => true,
        'reset_url' => 'index.php'
    ]);
    ?>

    <?php
    // Data Table Component
    render_data_table([
        'title' => 'Daftar Tiket',
        'data' => $tickets,
        'total_count' => $total_tickets,
        'empty_message' => !empty($search) ? 'Tidak ada tiket yang cocok dengan pencarian' : 'Belum ada data tiket',
        'empty_icon' => 'bi-ticket-perforated',
        'columns' => [
            [
                'key' => 'id_tiket',
                'label' => 'ID',
                'type' => 'badge'
            ],
            [
                'key' => 'nama_tiket',
                'label' => 'Nama Tiket',
                'type' => 'text'
            ],
            [
                'key' => 'nama_event',
                'label' => 'Event',
                'type' => 'text'
            ],
            [
                'key' => 'tanggal',
                'label' => 'Tanggal Event',
                'type' => 'date',
                'format' => 'd M Y'
            ],
            [
                'key' => 'harga',
                'label' => 'Harga',
                'type' => 'text',
                'format' => 'currency'
            ],
            [
                'key' => 'kuota',
                'label' => 'Kuota',
                'type' => 'text',
                'format' => 'number'
            ],
            [
                'key' => 'sold',
                'label' => 'Terjual',
                'type' => 'text',
                'format' => 'number'
            ],
            [
                'key' => 'status_badge',
                'label' => 'Sisa',
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
                'label' => 'Edit',
                'icon' => 'bi-pencil',
                'class' => 'btn btn-sm btn-warning',
                'type' => 'link',
                'href' => base_url('admin/tiket/edit.php?id={id}'),
                'id_key' => 'id_tiket'
            ],
            [
                'label' => 'Hapus',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'confirmDelete({id})',
                'id_key' => 'id_tiket'
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination Component
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_tickets,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'index.php',
        'query_params' => ['search' => $search]
    ]);
    ?>
</main>



<script>
function confirmDelete(id) {
    showConfirmation('Apakah Anda yakin ingin menghapus tiket ini?', function() {
        window.location.href = '?delete=' + id;
    }, {isDanger: true});
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
