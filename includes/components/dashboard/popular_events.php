<?php
/**
 * Popular Events Component
 * Displays most popular events based on ticket sales
 * 
 * @param array $popular_events Popular events data
 */
if (!isset($popular_events)) {
    return;
}
?>

<!-- Popular Events -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h6 class="card-title mb-0">Event Populer</h6>
                <small class="text-muted">Berdasarkan penjualan tiket</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($popular_events)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-star text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">Belum ada data penjualan</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Tiket Terjual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_events as $index => $event): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="ranking-badge me-2">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($event['nama_event']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                    <?php 
                                                    $max_tickets = max(array_column($popular_events, 'total_tiket'));
                                                    $percentage = $max_tickets > 0 ? ($event['total_tiket'] / $max_tickets) * 100 : 0;
                                                    ?>
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo number_format($event['total_tiket']); ?></small>
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
    </div>
