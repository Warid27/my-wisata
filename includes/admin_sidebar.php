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
            <?php if (is_admin()): ?>
            <li class="nav-item">
                <a href="<?php echo base_url('admin/users.php'); ?>" class="nav-link <?php echo is_menu_active('users', $current_page, $request_uri); ?>">
                    <i class="nav-icon bi bi-people"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>
            <?php endif; ?>
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
                    <div class="user-role"><?php echo ucfirst($_SESSION['user_role'] ?? 'admin'); ?></div>
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

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Check if we're on mobile
    function isMobile() {
        return window.innerWidth < 768;
    }
    
    // Toggle sidebar (mobile)
    function toggleSidebar() {
        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
            
            // Prevent body scroll when sidebar is open
            if (sidebar.classList.contains('mobile-open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    }
    
    // Close sidebar (mobile)
    function closeSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Toggle button click
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    // Overlay click to close
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking on a link (mobile)
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) {
                setTimeout(closeSidebar, 300); // Small delay for navigation
            }
        });
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (!isMobile()) {
                // Reset mobile states when switching to desktop
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }, 250);
    });
    
    // Initial state - sidebar is hidden by default on mobile
    // No need to add mobile-closed class as it conflicts with mobile-open
    
    // Handle ESC key to close sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMobile() && sidebar.classList.contains('mobile-open')) {
            closeSidebar();
        }
    });
    
    // Touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, false);
    
    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, false);
    
    function handleSwipe() {
        // Swipe left to close
        if (touchEndX < touchStartX - 50 && isMobile()) {
            closeSidebar();
        }
    }
});
</script>
