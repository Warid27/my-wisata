<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$page_title = 'Manajemen User';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_user = $_GET['delete'];

    // Prevent deleting self
    if ($id_user == get_user_id()) {
        set_flash_message('error', 'Tidak dapat menghapus akun Anda sendiri');
    } else {
        // Check if user has orders
        $query = "SELECT COUNT(*) as total FROM orders WHERE id_user = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_user]);
        $has_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;

        if ($has_orders) {
            set_flash_message('error', 'User tidak dapat dihapus karena memiliki riwayat pesanan');
        } else {
            $query = "DELETE FROM users WHERE id_user = ? AND role != 'admin'";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$id_user])) {
                set_flash_message('success', 'User berhasil dihapus');
            } else {
                set_flash_message('error', 'Gagal menghapus user atau user adalah admin');
            }
        }
    }

    redirect('admin/users/');
}

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (nama LIKE ? OR email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($role)) {
    $query .= " AND role = ?";
    $params[] = $role;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen User</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo base_url('admin/users/create.php'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah User
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="role">
            <option value="">Semua Role</option>
            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Cari
        </button>
    </div>
    </form>
    </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tidak ada data user
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2"
                                                style="width: 40px; height: 40px; font-weight: bold;">
                                                <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['nama']); ?></strong>
                                                <?php if ($user['id_user'] == get_user_id()): ?>
                                                    <br><small class="text-muted">(Anda)</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">User</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo format_date($user['created_at'], 'd M Y'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo base_url('admin/users/edit.php?id=' . $user['id_user']); ?>"
                                                class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <?php if ($user['id_user'] != get_user_id()): ?>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmDelete(<?php echo $user['id_user']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>
</div>



<script>
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
            window.location.href = '<?php echo base_url('admin/users/?delete='); ?>' + id;
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>