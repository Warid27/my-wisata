<?php
/**
 * Venue Performance Component
 * Displays top performing venues based on revenue and attendees
 * 
 * @param array $venue_performance Venue performance data
 */
if (!isset($venue_performance)) {
    return;
}
?>

<!-- Venue Performance -->
<div class="col-lg-6">
    <div class="card">
        <div class="card-header bg-white border-0 pt-4 pb-3">
            <h6 class="card-title mb-0">Performa Venue</h6>
            <small class="text-muted">5 venue teratas berdasarkan pendapatan</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($venue_performance)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">Belum ada data venue</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Event</th>
                                <th>Pendapatan</th>
                                <th>Pengunjung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($venue_performance as $venue): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="venue-icon me-2">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($venue['nama_venue']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo (int)$venue['total_events']; ?> event</small>
                                    </td>
                                    <td class="fw-bold"><?php echo format_currency_short($venue['revenue']); ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo number_format($venue['total_attendees']); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white border-0">
            <a href="venue/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>
    </div>
</div>
