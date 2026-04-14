<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// Handle directory-based pages
if ($current_page === 'index') {
    // Check if we're in a subdirectory
    $path_parts = parse_url($request_uri, PHP_URL_PATH);
    $path_segments = explode('/', trim($path_parts, '/'));
    
    // Find the admin directory and get the next segment
    $admin_index = array_search('admin', $path_segments);
    if ($admin_index !== false && isset($path_segments[$admin_index + 1])) {
        $current_page = $path_segments[$admin_index + 1];
    } else {
        $current_page = 'dashboard';
    }
}

// Helper function to check if menu is active
function is_menu_active($page, $current, $uri = '') {
    // Direct match for file-based pages
    if ($page === $current) {
        return 'active';
    }
    
    // Check if URI contains the page name (for directory-based pages)
    if ($uri && strpos($uri, '/' . $page . '/') !== false) {
        return 'active';
    }
    
    return '';
}
?>

<nav class="sidebar">
    <div class="sidebar-content">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="bi bi-speedometer2"></i>
            </div>
            <div class="brand-text">
                <span>Admin</span>
                <small>Panel</small>
            </div>
        </div>

        <!-- Navigation -->
        <ul class="sidebar-nav">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="<?php echo base_url('admin/'); ?>" class="nav-link <?php echo is_menu_active('dashboard', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Master Data Section -->
            <li class="nav-section">
                <div class="nav-section-title">Master Data</div>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/users.php'); ?>" class="nav-link <?php echo is_menu_active('users', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-people"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/venue/'); ?>" class="nav-link <?php echo is_menu_active('venue', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-building"></i>
                    <span class="nav-text">Venue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/event/'); ?>" class="nav-link <?php echo is_menu_active('event', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-calendar-event"></i>
                    <span class="nav-text">Event</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/tiket/'); ?>" class="nav-link <?php echo is_menu_active('tiket', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-ticket"></i>
                    <span class="nav-text">Tiket</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/voucher/'); ?>" class="nav-link <?php echo is_menu_active('voucher', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-ticket-detailed"></i>
                    <span class="nav-text">Voucher</span>
                </a>
            </li>

            <!-- Transaction Section -->
            <li class="nav-section">
                <div class="nav-section-title">Transaksi</div>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/orders.php'); ?>" class="nav-link <?php echo is_menu_active('orders', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-cart"></i>
                    <span class="nav-text">Pesanan</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/checkin.php'); ?>" class="nav-link <?php echo is_menu_active('checkin', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-qr-code"></i>
                    <span class="nav-text">Check-in</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/reports.php'); ?>" class="nav-link <?php echo is_menu_active('reports', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-graph-up"></i>
                    <span class="nav-text">Laporan</span>
                </a>
            </li>
        </ul>

        <!-- User Profile -->
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_nama'] ?? 'Admin'); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="<?php echo base_url('logout.php'); ?>" class="user-logout" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Toggle -->
    <button class="sidebar-toggle" type="button">
        <i class="bi bi-list"></i>
    </button>
</nav>

<!-- Sidebar JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for mobile
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }

    // Auto-collapse on mobile
    if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        }
    });
});
</script>
