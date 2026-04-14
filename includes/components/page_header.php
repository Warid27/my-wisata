<?php
/**
 * Reusable Page Header Component
 * 
 * @param array $config Configuration array with the following keys:
 *   - title: Page title
 *   - subtitle: Page subtitle/description (optional)
 *   - actions: Array of action buttons (optional)
 *     - label: Button label
 *   - icon: Bootstrap icon class (optional)
 *   - class: CSS classes for button
 *   - type: Button type (button, link, submit)
 *   - href: Link URL for link type
 *   - onclick: JavaScript function for button type
 *   - modal: Modal target for modal triggers
 *   - badge: Badge text for button (optional)
 */
function render_page_header($config) {
    $title = $config['title'] ?? '';
    $subtitle = $config['subtitle'] ?? '';
    $actions = $config['actions'] ?? [];
    
    ?>
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><?php echo htmlspecialchars($title); ?></h1>
            <?php if (!empty($subtitle)): ?>
                <p class="page-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($actions)): ?>
            <div class="page-actions">
                <?php foreach ($actions as $action): ?>
                    <?php
                    $button_class = $action['class'] ?? 'btn-primary';
                    $button_type = $action['type'] ?? 'button';
                    $button_label = $action['label'] ?? '';
                    $button_icon = $action['icon'] ?? '';
                    $button_modal = $action['modal'] ?? '';
                    $button_onclick = $action['onclick'] ?? '';
                    $button_href = $action['href'] ?? '';
                    $button_badge = $action['badge'] ?? '';
                    
                    if ($button_type === 'link' && !empty($button_href)) {
                        echo '<a href="' . htmlspecialchars($button_href) . '" class="btn ' . $button_class . '">';
                    } else {
                        $attributes = 'type="' . $button_type . '" class="btn ' . $button_class . '"';
                        if (!empty($button_modal)) {
                            $attributes .= ' data-bs-toggle="modal" data-bs-target="' . htmlspecialchars($button_modal) . '"';
                        }
                        if (!empty($button_onclick)) {
                            $attributes .= ' onclick="' . htmlspecialchars($button_onclick) . '"';
                        }
                        echo '<button ' . $attributes . '>';
                    }
                    
                    if (!empty($button_icon)) {
                        echo '<i class="bi ' . $button_icon . ' me-2"></i>';
                    }
                    
                    echo htmlspecialchars($button_label);
                    
                    if (!empty($button_badge)) {
                        echo ' <span class="badge bg-light text-dark ms-1">' . htmlspecialchars($button_badge) . '</span>';
                    }
                    
                    if ($button_type === 'link') {
                        echo '</a>';
                    } else {
                        echo '</button>';
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
