<?php
/**
 * Today's Events Component
 * 
 * Displays today's events with check-in statistics
 * 
 * @param array $events Array of today's events with check-in counts
 * @param string $title Card title (default: 'Event Hari Ini')
 * @param string $icon Card icon (default: 'bi-calendar-check')
 * @param string $header_class Header CSS class (default: 'bg-primary text-white')
 */

// Default parameters
$title = $title ?? 'Event Hari Ini';
$icon = $icon ?? 'bi-calendar-check';
$header_class = $header_class ?? 'bg-primary text-white';
?>

<div class="card shadow">
    <div class="card-header <?php echo $header_class; ?>">
        <h5 class="mb-0">
            <i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($title); ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($events)): ?>
            <p class="text-muted">Tidak ada event hari ini</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong>
                        <br>
                        <small class="text-muted">
                            Total: <?php echo (int)$event['total_checkins']; ?> | 
                            Check-in: <?php echo (int)$event['checked_in']; ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <?php 
                        $percentage = $event['total_checkins'] > 0 
                            ? round(($event['checked_in'] / $event['total_checkins']) * 100, 1) 
                            : 0; 
                        ?>
                        <span class="badge bg-<?php echo $percentage >= 50 ? 'success' : 'warning'; ?>">
                            <?php echo $percentage; ?>%
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
