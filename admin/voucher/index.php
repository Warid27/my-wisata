<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/page_header.php';
require_once __DIR__ . '/../../includes/components/search_filter.php';
require_once __DIR__ . '/../../includes/components/data_table.php';
require_once __DIR__ . '/../../includes/components/pagination.php';

require_staff();

$page_title = 'Manajemen Voucher';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_voucher = $_GET['delete'];

    // Check if voucher has orders
    $query = "SELECT COUNT(*) as total FROM orders WHERE id_voucher = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_voucher]);
    $has_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;

    if ($has_orders) {
        set_flash_message('error', 'Voucher tidak dapat dihapus karena sudah digunakan dalam pesanan');
    } else {
        $query = "DELETE FROM voucher WHERE id_voucher = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_voucher])) {
            set_flash_message('success', 'Voucher berhasil dihapus');
        } else {
            set_flash_message('error', 'Gagal menghapus voucher');
        }
    }

    redirect('admin/voucher/');
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id_voucher = $_GET['toggle'];

    $query = "UPDATE voucher SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id_voucher = ?";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$id_voucher])) {
        set_flash_message('success', 'Status voucher berhasil diperbarui');
    } else {
        set_flash_message('error', 'Gagal memperbarui status voucher');
    }

    redirect('admin/voucher/');
}

// Get vouchers with pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE kode_voucher LIKE ?";
    $params = ["%$search%"];
}

// Get total vouchers
$count_query = "SELECT COUNT(*) as total FROM voucher v $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_vouchers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_vouchers / $per_page);

// Get vouchers list
$query = "SELECT *, 
                 (SELECT COUNT(*) FROM orders WHERE id_voucher = v.id_voucher) as used_count 
          FROM voucher v 
          $where
          ORDER BY created_at DESC LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add status and remaining count for each voucher
foreach ($vouchers as &$voucher) {
    $sisa = $voucher['kuota'] - $voucher['used_count'];
    $voucher['sisa'] = $sisa;
    
    // Add status badge
    if ($voucher['status'] === 'aktif') {
        $voucher['status_badge'] = '<span class="badge bg-success">Aktif</span>';
        $voucher['status_text'] = 'Aktif';
    } else {
        $voucher['status_badge'] = '<span class="badge bg-secondary">Nonaktif</span>';
        $voucher['status_text'] = 'Nonaktif';
    }
    
    // Add remaining badge
    if ($sisa <= 0) {
        $voucher['remaining_badge'] = '<span class="badge bg-danger">Habis</span>';
    } elseif ($sisa <= 10) {
        $voucher['remaining_badge'] = '<span class="badge bg-warning">' . number_format($sisa) . '</span>';
    } else {
        $voucher['remaining_badge'] = '<span class="badge bg-success">' . number_format($sisa) . '</span>';
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
        'title' => 'Manajemen Voucher',
        'subtitle' => 'Kelola data voucher dan diskon',
        'actions' => [
            [
                'label' => 'Tambah Voucher',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'type' => 'link',
                'href' => base_url('admin/voucher/create.php')
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter Component
    render_search_filter([
        'placeholder' => 'Cari berdasarkan kode voucher...',
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
        'title' => 'Daftar Voucher',
        'data' => $vouchers,
        'total_count' => $total_vouchers,
        'empty_message' => !empty($search) ? 'Tidak ada voucher yang cocok dengan pencarian' : 'Belum ada data voucher',
        'empty_icon' => 'bi-ticket',
        'columns' => [
            [
                'key' => 'id_voucher',
                'label' => 'ID',
                'type' => 'badge'
            ],
            [
                'key' => 'kode_voucher',
                'label' => 'Kode',
                'type' => 'text'
            ],
            [
                'key' => 'potongan',
                'label' => 'Potongan',
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
                'key' => 'used_count',
                'label' => 'Terpakai',
                'type' => 'text',
                'format' => 'number'
            ],
            [
                'key' => 'remaining_badge',
                'label' => 'Sisa',
                'type' => 'html'
            ],
            [
                'key' => 'status_badge',
                'label' => 'Status',
                'type' => 'html'
            ],
            [
                'key' => 'created_at',
                'label' => 'Dibuat',
                'type' => 'date',
                'format' => 'd M Y H:i'
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
                'href' => base_url('admin/voucher/edit.php?id={id}'),
                'id_key' => 'id_voucher'
            ],
            [
                'label' => 'Toggle',
                'icon' => 'bi-pause',
                'class' => 'btn btn-sm btn-secondary',
                'onclick' => 'toggleStatus({id})',
                'id_key' => 'id_voucher'
            ],
            [
                'label' => 'Hapus',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'confirmDelete({id})',
                'id_key' => 'id_voucher'
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination Component
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_vouchers,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'index.php',
        'query_params' => ['search' => $search]
    ]);
    ?>
</main>



<script>
    function confirmDelete(id) {
        showConfirmation('Apakah Anda yakin ingin menghapus voucher ini?', function() {
            window.location.href = '<?php echo base_url('admin/voucher/?delete='); ?>' + id;
        }, {isDanger: true});
    }

    function toggleStatus(id) {
        window.location.href = '?toggle=' + id;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>