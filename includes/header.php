<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?> <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS (includes Bootstrap) -->
<link href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/my-wisata-pecut/assets/css/style.css'); ?>" rel="stylesheet">
    <!-- Puter.js CDN -->
    <script src="https://js.puter.com/v2/"></script>

</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" id="main-navbar">
        <div class="container-fluid">
            <!-- Mobile sidebar toggle button for admin pages -->
            <?php if (strpos($_SERVER['REQUEST_URI'], '/admin') !== false): ?>
            <button class="navbar-toggler me-2 d-lg-none" type="button" id="mobileSidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <?php endif; ?>
            
            <a class="navbar-brand" href="<?php echo base_url(); ?>">
                <i class="bi bi-ticket-perforated"></i> <?php echo SITE_NAME; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo get_user_name(); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo base_url('logout.php'); ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('user/login.php'); ?>">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('user/register.php'); ?>">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (has_flash_message('success')): ?>
        <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
            <?php echo get_flash_message('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (has_flash_message('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show m-0" role="alert">
            <?php echo get_flash_message('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (has_flash_message('info')): ?>
        <div class="alert alert-info alert-dismissible fade show m-0" role="alert">
            <?php echo get_flash_message('info'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('main-navbar');
    const sidebar = document.querySelector('.sidebar');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    
    // Check if sidebar exists on the page
    if (sidebar) {
        // Sidebar exists - apply fixed positioning and alignment
        navbar.classList.add('fixed-top');
        navbar.style.zIndex = '1020';
        navbar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        
        // Function to update navbar position
        function updateNavbarPosition() {
            if (window.innerWidth >= 768) {
                if (sidebar.classList.contains('collapsed')) {
                    navbar.style.left = '80px';
                    navbar.style.width = 'calc(100% - 80px)';
                } else {
                    navbar.style.left = '280px';
                    navbar.style.width = 'calc(100% - 280px)';
                }
            } else {
                // Mobile view - navbar takes full width
                navbar.style.left = '0';
                navbar.style.width = '100%';
            }
        }
        
        // Initial position
        updateNavbarPosition();
        
        // Update on sidebar toggle
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updateNavbarPosition();
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            updateNavbarPosition();
        });
        
        // Mobile sidebar toggle
        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                const overlay = document.getElementById('sidebarOverlay');
                if (overlay) {
                    overlay.classList.toggle('show');
                }
                
                // Prevent body scroll when sidebar is open
                if (sidebar.classList.contains('mobile-open')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        }
    }
    // If no sidebar exists, navbar remains as normal (static positioning)
});
</script>

<!-- Main Content -->