<?php
/**
 * Upcoming Events Component
 * Displays upcoming events in a list
 * 
 * @param array $upcoming_events Upcoming events data
 */
if (!isset($upcoming_events)) {
    return;
}
?>

<!-- Upcoming Events -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-header bg-white border-0 pt-4 pb-3">
            <h6 class="card-title mb-0">Event Mendatang</h6>
            <small class="text-muted">5 event terdekat</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($upcoming_events)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">Tidak ada event mendatang</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="event-date-sm">
                                        <div class="day"><?php echo date('d', strtotime($event['tanggal'])); ?></div>
                                        <div class="month"><?php echo date('M', strtotime($event['tanggal'])); ?></div>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($event['nama_event']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['nama_venue']); ?>
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="event/edit.php?id=<?php echo $event['id_event']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0">
            <a href="event/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>
    </div>
</div>
