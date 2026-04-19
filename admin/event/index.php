<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/page_header.php';
require_once __DIR__ . '/../../includes/components/search_filter.php';
require_once __DIR__ . '/../../includes/components/data_table.php';
require_once __DIR__ . '/../../includes/components/pagination.php';

require_staff();

$page_title = 'Manajemen Event';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_event = $_GET['delete'];
    
    // Check if event has tickets or orders
    $query = "SELECT (SELECT COUNT(*) FROM tiket WHERE id_event = ?) as tickets, 
                     (SELECT COUNT(*) FROM order_detail od JOIN tiket t ON od.id_tiket = t.id_tiket WHERE t.id_event = ?) as orders";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_event, $id_event]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check['tickets'] > 0 || $check['orders'] > 0) {
        set_flash_message('error', 'Event tidak dapat dihapus karena masih ada tiket atau pesanan terkait');
    } else {
        $query = "DELETE FROM event WHERE id_event = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_event])) {
            set_flash_message('success', 'Event berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus event');
        }
    }
    
    redirect('admin/event/');
}

// Get events with pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE e.nama_event LIKE ? OR v.nama_venue LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total events
$count_query = "SELECT COUNT(*) as total FROM event e JOIN venue v ON e.id_venue = v.id_venue $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_events / $per_page);

// Get events list
$query = "SELECT e.*, v.nama_venue 
          FROM event e 
          JOIN venue v ON e.id_venue = v.id_venue 
          $where
          ORDER BY e.tanggal DESC LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add status to each event
foreach ($events as &$event) {
    $today = date('Y-m-d');
    if ($event['tanggal'] < $today) {
        $event['status_badge'] = '<span class="badge bg-secondary">Selesai</span>';
        $event['status_text'] = 'Selesai';
    } elseif ($event['tanggal'] == $today) {
        $event['status_badge'] = '<span class="badge bg-success">Hari Ini</span>';
        $event['status_text'] = 'Hari Ini';
    } else {
        $event['status_badge'] = '<span class="badge bg-primary">Mendatang</span>';
        $event['status_text'] = 'Mendatang';
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
        'title' => 'Manajemen Event',
        'subtitle' => 'Kelola data event dan tiket',
        'actions' => [
            [
                'label' => 'Tambah Event',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'type' => 'link',
                'href' => base_url('admin/event/create.php')
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter Component
    render_search_filter([
        'placeholder' => 'Cari berdasarkan nama event atau venue...',
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
        'title' => 'Daftar Event',
        'data' => $events,
        'total_count' => $total_events,
        'empty_message' => !empty($search) ? 'Tidak ada event yang cocok dengan pencarian' : 'Belum ada data event',
        'empty_icon' => 'bi-calendar-event',
        'columns' => [
            [
                'key' => 'id_event',
                'label' => 'ID',
                'type' => 'badge'
            ],
            [
                'key' => 'nama_event',
                'label' => 'Nama Event',
                'type' => 'avatar'
            ],
            [
                'key' => 'tanggal',
                'label' => 'Tanggal',
                'type' => 'date',
                'format' => 'd M Y'
            ],
            [
                'key' => 'nama_venue',
                'label' => 'Venue',
                'type' => 'text'
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
                'label' => 'Edit',
                'icon' => 'bi-pencil',
                'class' => 'btn btn-sm btn-warning',
                'type' => 'link',
                'href' => base_url('admin/event/edit.php?id={id}'),
                'id_key' => 'id_event'
            ],
            [
                'label' => 'Hapus',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'confirmDelete({id})',
                'id_key' => 'id_event'
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination Component
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_events,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'index.php',
        'query_params' => ['search' => $search]
    ]);
    ?>
</main>



<script>
function confirmDelete(id) {
    showConfirmation('Apakah Anda yakin ingin menghapus event ini?', function() {
        window.location.href = '?delete=' + id;
    }, {isDanger: true});
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
