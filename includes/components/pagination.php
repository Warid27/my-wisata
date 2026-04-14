<?php
/**
 * Reusable Pagination Component
 * 
 * @param array $config Configuration array with the following keys:
 *   - current_page: Current page number
 *   - total_pages: Total number of pages
 *   - total_items: Total number of items
 *   - per_page: Items per page
 *   - offset: Current offset
 *   - base_url: Base URL for pagination links
 *   - query_params: Additional query parameters to preserve (optional)
 */
function render_pagination($config) {
    $current_page = $config['current_page'];
    $total_pages = $config['total_pages'];
    $total_items = $config['total_items'];
    $per_page = $config['per_page'];
    $offset = $config['offset'];
    $base_url = $config['base_url'];
    $query_params = $config['query_params'] ?? [];
    
    if ($total_pages <= 1) {
        return;
    }
    
    // Build query string
    $query_string = '';
    if (!empty($query_params)) {
        $parts = [];
        foreach ($query_params as $key => $value) {
            if (!empty($value)) {
                $parts[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        if (!empty($parts)) {
            $query_string = '&' . implode('&', $parts);
        }
    }
    
    ?>
    <div class="d-flex justify-content-between align-items-center p-3">
        <small class="text-muted">
            Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_items); ?>
            dari <?php echo $total_items; ?> data
        </small>
        <nav>
            <ul class="pagination mb-0">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url; ?>?page=<?php echo $current_page - 1; ?><?php echo $query_string; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php 
                // Show page numbers
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                // Show first page if not in range
                if ($start_page > 1) {
                    ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url; ?>?page=1<?php echo $query_string; ?>">1</a>
                    </li>
                    <?php
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $base_url; ?>?page=<?php echo $i; ?><?php echo $query_string; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php 
                // Show last page if not in range
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url; ?>?page=<?php echo $total_pages; ?><?php echo $query_string; ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    </li>
                    <?php
                }
                ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base_url; ?>?page=<?php echo $current_page + 1; ?><?php echo $query_string; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php
}
?>
