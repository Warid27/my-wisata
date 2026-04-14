<?php
/**
 * Reusable Search and Filter Component
 * 
 * @param array $config Configuration array with the following keys:
 *   - placeholder: Search input placeholder text
 *   - search_value: Current search value
 *   - action_url: Form action URL (default: current page)
 *   - method: Form method (default: GET)
 *   - show_reset: Show reset button when search is active (default: true)
 *   - reset_url: URL for reset button (default: current page without params)
 *   - filters: Array of additional filter fields (optional)
 *     - name: Field name
 *     - type: Field type (select, text, date, etc.)
 *     - label: Field label
 *     - options: Array of options for select type
 *     - value: Current field value
 *     - placeholder: Field placeholder
 *     - class: Additional CSS classes
 *   - button_text: Search button text (default: "Cari")
 */
function render_search_filter($config) {
    $placeholder = $config['placeholder'] ?? 'Cari data...';
    $search_value = $config['search_value'] ?? '';
    $action_url = $config['action_url'] ?? '';
    $method = $config['method'] ?? 'GET';
    $show_reset = $config['show_reset'] ?? true;
    $reset_url = $config['reset_url'] ?? basename($_SERVER['PHP_SELF']);
    $filters = $config['filters'] ?? [];
    $button_text = $config['button_text'] ?? 'Cari';
    
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="<?php echo strtoupper($method); ?>" action="<?php echo htmlspecialchars($action_url); ?>">
                <div class="row g-3">
                    <?php if (empty($filters)): ?>
                        <!-- Simple search only -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <input type="text" class="form-control" name="search"
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                    value="<?php echo htmlspecialchars($search_value); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-search"></i> <?php echo htmlspecialchars($button_text); ?>
                                </button>
                                <?php if ($show_reset && !empty($search_value)): ?>
                                    <a href="<?php echo htmlspecialchars($reset_url); ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Search with filters -->
                        <div class="col-md-<?php echo count($filters) >= 2 ? '4' : '6'; ?>">
                            <div class="form-group">
                                <input type="text" class="form-control" name="search"
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                    value="<?php echo htmlspecialchars($search_value); ?>">
                            </div>
                        </div>
                        
                        <?php foreach ($filters as $index => $filter): ?>
                            <div class="col-md-<?php echo count($filters) >= 2 ? '4' : '6'; ?>">
                                <div class="form-group">
                                    <?php if ($filter['type'] === 'select'): ?>
                                        <select class="form-control <?php echo $filter['class'] ?? ''; ?>" 
                                                name="<?php echo htmlspecialchars($filter['name']); ?>">
                                            <?php if (isset($filter['placeholder'])): ?>
                                                <option value=""><?php echo htmlspecialchars($filter['placeholder']); ?></option>
                                            <?php endif; ?>
                                            <?php foreach ($filter['options'] as $value => $label): ?>
                                                <option value="<?php echo htmlspecialchars($value); ?>" 
                                                        <?php echo (isset($filter['value']) && $filter['value'] == $value) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($filter['type'] === 'date'): ?>
                                        <input type="date" class="form-control <?php echo $filter['class'] ?? ''; ?>" 
                                               name="<?php echo htmlspecialchars($filter['name']); ?>"
                                               value="<?php echo htmlspecialchars($filter['value'] ?? ''); ?>">
                                    <?php else: ?>
                                        <input type="<?php echo htmlspecialchars($filter['type'] ?? 'text'); ?>" 
                                               class="form-control <?php echo $filter['class'] ?? ''; ?>" 
                                               name="<?php echo htmlspecialchars($filter['name']); ?>"
                                               placeholder="<?php echo htmlspecialchars($filter['placeholder'] ?? ''); ?>"
                                               value="<?php echo htmlspecialchars($filter['value'] ?? ''); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-search"></i> <?php echo htmlspecialchars($button_text); ?>
                                </button>
                                <?php if ($show_reset && (!empty($search_value) || !empty(array_filter(array_column($filters, 'value'))))): ?>
                                    <a href="<?php echo htmlspecialchars($reset_url); ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
