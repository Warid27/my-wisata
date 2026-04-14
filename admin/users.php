<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components/page_header.php';
require_once __DIR__ . '/../includes/components/search_filter.php';
require_once __DIR__ . '/../includes/components/data_table.php';
require_once __DIR__ . '/../includes/components/pagination.php';

require_admin();

$page_title = 'Manajemen Users';

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE nama LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total users
$count_query = "SELECT COUNT(*) as total FROM users $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users list
$query = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add subtitle for current user
foreach ($users as &$user) {
    $user['is_current_user'] = ($user['id_user'] == ($_SESSION['user_id'] ?? 0)) ? '(Anda)' : '';
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
        'title' => 'Manajemen Users',
        'subtitle' => 'Kelola data pengguna aplikasi',
        'actions' => [
            [
                'label' => 'Tambah User',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'modal' => '#addUserModal'
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter Component
    render_search_filter([
        'placeholder' => 'Cari berdasarkan nama atau email...',
        'search_value' => $search,
        'action_url' => '',
        'method' => 'GET',
        'show_reset' => true,
        'reset_url' => 'users.php'
    ]);
    ?>

    <?php
    // Data Table Component
    render_data_table([
        'title' => 'Daftar Users',
        'data' => $users,
        'total_count' => $total_users,
        'empty_message' => !empty($search) ? 'Tidak ada user yang cocok dengan pencarian' : 'Belum ada user',
        'empty_icon' => 'bi-people',
        'columns' => [
            [
                'key' => 'id_user',
                'label' => 'ID',
                'type' => 'badge'
            ],
            [
                'key' => 'nama',
                'label' => 'Nama',
                'type' => 'avatar',
                'subtitle' => 'is_current_user'
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'text'
            ],
            [
                'key' => 'created_at',
                'label' => 'Tanggal Daftar',
                'type' => 'date',
                'format' => 'd M Y'
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
                'class' => 'btn btn-sm btn-outline-primary',
                'onclick' => 'editUser({id})',
                'id_key' => 'id_user'
            ],
            [
                'label' => 'Hapus',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => 'deleteUser({id})',
                'id_key' => 'id_user',
                'condition' => [
                    'field' => 'id_user',
                    'operator' => '!=',
                    'value' => $_SESSION['user_id'] ?? 0
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
        'total_items' => $total_users,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => 'users.php',
        'query_params' => ['search' => $search]
    ]);
    ?>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" class="form-control" name="no_telp">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label" for="isActive">
                                User Aktif
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Add User
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_active = formData.has('is_active') ? 1 : 0;

        fetch('<?php echo base_url('api/users.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User berhasil ditambahkan', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Gagal menambahkan user', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan', 'error');
            });
    });

    // Edit User (placeholder)
    function editUser(userId) {
        showNotification('Fitur edit user akan segera tersedia', 'info');
    }

    // Delete User (placeholder)
    function deleteUser(userId) {
        showConfirmation('Apakah Anda yakin ingin menghapus user ini?', function() {
            showNotification('Fitur hapus user akan segera tersedia', 'info');
        }, {
            isDanger: true
        });
    }
</script>