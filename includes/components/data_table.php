<?php
/**
 * Reusable Data Table Component
 * 
 * @param array $config Configuration array with the following keys:
 *   - title: Table title
 *   - data: Array of data items
 *   - columns: Array of column definitions
 *     - key: Data key for the column
 *     - label: Column header label
 *     - type: 'text', 'badge', 'date', 'avatar', 'actions' (default: 'text')
 *     - format: Optional format function for date type
 *   - total_count: Total number of items
 *   - empty_message: Message to show when no data
 *   - empty_icon: Bootstrap icon class for empty state
 *   - actions: Array of action buttons for each row (optional)
 *     - label: Button label
 *     - icon: Bootstrap icon class
 *     - class: CSS classes for button
 *     - onclick: JavaScript function to call
 *     - condition: Optional condition to show/hide button
 */
function render_data_table($config) {
    $title = $config['title'] ?? 'Data Table';
    $data = $config['data'] ?? [];
    $columns = $config['columns'] ?? [];
    $total_count = $config['total_count'] ?? count($data);
    $empty_message = $config['empty_message'] ?? 'Tidak ada data';
    $empty_icon = $config['empty_icon'] ?? 'bi-database';
    $actions = $config['actions'] ?? [];
    
    ?>
    <div class="card data-table">
        <div class="card-header bg-white border-0 pt-4 pb-3">
            <h6 class="card-title mb-0"><?php echo htmlspecialchars($title); ?></h6>
            <small class="text-muted">Total <?php echo number_format($total_count); ?> item</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($data)): ?>
                <div class="text-center py-4">
                    <i class="bi <?php echo $empty_icon; ?> text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2"><?php echo htmlspecialchars($empty_message); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <th><?php echo htmlspecialchars($column['label']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr>
                                    <?php foreach ($columns as $column): ?>
                                        <?php
                                        $value = $item[$column['key']] ?? '';
                                        $type = $column['type'] ?? 'text';
                                        
                                        switch ($type) {
                                            case 'badge':
                                                echo '<td><span class="badge bg-light text-dark">#' . htmlspecialchars($value) . '</span></td>';
                                                break;
                                                
                                            case 'avatar':
                                                echo '<td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar-sm bg-primary-light text-primary me-2">
                                                            <i class="bi bi-person"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">' . htmlspecialchars($value) . '</div>';
                                                            if (isset($column['subtitle']) && isset($item[$column['subtitle']])) {
                                                                echo '<small class="text-muted">' . htmlspecialchars($item[$column['subtitle']]) . '</small>';
                                                            }
                                                            echo '</div></div></td>';
                                                break;
                                                
                                            case 'date':
                                                $format = $column['format'] ?? 'd M Y';
                                                if (!empty($value)) {
                                                    echo '<td><small>' . date($format, strtotime($value)) . '</small></td>';
                                                } else {
                                                    echo '<td><small>-</small></td>';
                                                }
                                                break;
                                                
                                            case 'html':
                                                echo '<td>' . $value . '</td>';
                                                break;
                                                
                                            case 'actions':
                                                echo '<td>';
                                                if (!empty($actions)) {
                                                    echo '<div class="btn-group">';
                                                    foreach ($actions as $action) {
                                                        $show_action = true;
                                                        if (isset($action['condition'])) {
                                                            $condition_field = $action['condition']['field'];
                                                            $condition_value = $action['condition']['value'];
                                                            $condition_operator = $action['condition']['operator'] ?? '==';
                                                            
                                                            $item_value = $item[$condition_field] ?? null;
                                                            
                                                            switch ($condition_operator) {
                                                                case '==':
                                                                    $show_action = ($item_value == $condition_value);
                                                                    break;
                                                                case '!=':
                                                                    $show_action = ($item_value != $condition_value);
                                                                    break;
                                                                case '>':
                                                                    $show_action = ($item_value > $condition_value);
                                                                    break;
                                                                case '<':
                                                                    $show_action = ($item_value < $condition_value);
                                                                    break;
                                                            }
                                                        }
                                                        
                                                        if ($show_action) {
                                                            if (isset($action['type']) && $action['type'] === 'link') {
                                                                $href = str_replace('{id}', $item[$action['id_key'] ?? 'id'], $action['href']);
                                                                echo '<a href="' . htmlspecialchars($href) . '" class="btn ' . $action['class'] . '">
                                                                    <i class="bi ' . $action['icon'] . '"></i>
                                                                </a>';
                                                            } else {
                                                                $onclick = str_replace('{id}', $item[$action['id_key'] ?? 'id'], $action['onclick']);
                                                                echo '<button type="button" class="btn ' . $action['class'] . '" onclick="' . htmlspecialchars($onclick) . '">
                                                                    <i class="bi ' . $action['icon'] . '"></i>
                                                                </button>';
                                                            }
                                                        }
                                                    }
                                                    echo '</div>';
                                                }
                                                echo '</td>';
                                                break;
                                                
                                            default:
                                                $display_value = $value ?? '-';
                                                if (isset($column['format'])) {
                                                    if ($column['format'] === 'number') {
                                                        $display_value = number_format($value) . ' orang';
                                                    } elseif ($column['format'] === 'currency') {
                                                        $display_value = format_currency($value);
                                                    }
                                                }
                                                echo '<td>' . htmlspecialchars($display_value) . '</td>';
                                                break;
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>
