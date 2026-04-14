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
    
    redirect('admin/users/index.php');
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id_user = $_GET['toggle'];
    
    // Prevent disabling self
    if ($id_user == get_user_id()) {
        set_flash_message('error', 'Tidak dapat mengubah status akun Anda sendiri');
    } else {
        $query = "UPDATE users SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id_user = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_user])) {
            set_flash_message('success', 'Status user berhasil diperbarui');
        } else {
            set_flash_message('error', 'Gagal memperbarui status user');
        }
    }
    
    redirect('admin/users/index.php');
}

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

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

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/index.php'); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                            <span>Master Data</span>
                        </h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/venue/index.php'); ?>">
                            <i class="bi bi-building"></i> Venue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/event/index.php'); ?>">
                            <i class="bi bi-calendar-event"></i> Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/tiket/index.php'); ?>">
                            <i class="bi bi-ticket"></i> Tiket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('admin/voucher/index.php'); ?>">
                            <i class="bi bi-ticket-detailed"></i> Voucher
                        </a>
                    </li>
                    <li class="nav-item">
                        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                            <span>Users</span>
                        </h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo base_url('admin/users/index.php'); ?>">
                            <i class="bi bi-people"></i> Manajemen User
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manajemen User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo base_url('admin/users/create.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah User
                    </a>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Cari nama atau email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="role">
                                <option value="">Semua Role</option>
                                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo $status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
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
                                        <th>Status</th>
                                        <th>Terdaftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($users as $user): ?>
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
                                            <td>
                                                <?php if ($user['status'] === 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
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
                                                        <button type="button" class="btn btn-sm <?php echo $user['status'] === 'aktif' ? 'btn-secondary' : 'btn-success'; ?>" 
                                                                onclick="toggleStatus(<?php echo $user['id_user']; ?>)" 
                                                                title="<?php echo $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                            <i class="bi bi-<?php echo $user['status'] === 'aktif' ? 'pause' : 'play'; ?>"></i>
                                                        </button>
                                                        
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

<style>
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}
.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}
.nav-link {
    font-weight: 500;
    color: #333;
}
.nav-link:hover {
    color: #007bff;
}
.nav-link.active {
    color: #007bff;
}
@media (min-width: 768px) {
    .sidebar {
        width: 240px;
    }
    main {
        margin-left: 240px;
    }
}
</style>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        window.location.href = '<?php echo base_url('admin/users/index.php?delete='); ?>' + id;
    }
}

function toggleStatus(id) {
    window.location.href = '<?php echo base_url('admin/users/index.php?toggle='); ?>' + id;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
