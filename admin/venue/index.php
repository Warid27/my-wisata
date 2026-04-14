<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/page_header.php';
require_once __DIR__ . '/../../includes/components/search_filter.php';
require_once __DIR__ . '/../../includes/components/data_table.php';

require_admin();

$page_title = 'Manajemen Venue';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_venue = $_GET['delete'];
    
    // Check if venue has events
    $query = "SELECT COUNT(*) as total FROM event WHERE id_venue = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_venue]);
    $has_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_events) {
        set_flash_message('error', 'Venue tidak dapat dihapus karena masih ada event terkait');
    } else {
        $query = "DELETE FROM venue WHERE id_venue = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_venue])) {
            set_flash_message('success', 'Venue berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus venue');
        }
    }
    
    redirect('admin/venue/');
}

// Get venues with pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE nama_venue LIKE ? OR alamat LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total venues
$count_query = "SELECT COUNT(*) as total FROM venue $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_venues = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_venues / $per_page);

// Get venues list
$query = "SELECT * FROM venue $where ORDER BY nama_venue LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <?php
    // Page Header Component
    render_page_header([
        'title' => 'Manajemen Venue',
        'subtitle' => 'Kelola data venue event',
        'actions' => [
            [
                'label' => 'Tambah Venue',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'type' => 'link',
                'href' => base_url('admin/venue/create.php')
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter Component
    render_search_filter([
        'placeholder' => 'Cari berdasarkan nama venue atau alamat...',
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
        'title' => 'Daftar Venue',
        'data' => $venues,
        'total_count' => $total_venues,
        'empty_message' => !empty($search) ? 'Tidak ada venue yang cocok dengan pencarian' : 'Belum ada data venue',
        'empty_icon' => 'bi-building',
        'columns' => [
            [
                'key' => 'id_venue',
                'label' => 'ID',
                'type' => 'badge'
            ],
            [
                'key' => 'nama_venue',
                'label' => 'Nama Venue',
                'type' => 'text'
            ],
            [
                'key' => 'alamat',
                'label' => 'Alamat',
                'type' => 'text'
            ],
            [
                'key' => 'kapasitas',
                'label' => 'Kapasitas',
                'type' => 'text',
                'format' => 'number'
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
                'href' => base_url('admin/venue/edit.php?id={id}'),
                'id_key' => 'id_venue'
            ],
            [
                'label' => 'Hapus',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'confirmDelete({id})',
                'id_key' => 'id_venue'
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination Component
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_venues,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'index.php',
        'query_params' => ['search' => $search]
    ]);
    ?>
</main>



<script>
function confirmDelete(id) {
    showConfirmation('Apakah Anda yakin ingin menghapus venue ini?', function() {
        window.location.href = '?delete=' + id;
    }, {isDanger: true});
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
