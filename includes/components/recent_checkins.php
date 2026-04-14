<?php
/**
 * Recent Check-ins Component
 * 
 * Displays a list of recent check-ins with event details
 * 
 * @param array $checkins Array of recent check-in records
 * @param string $title Card title (default: 'Check-in Terakhir')
 * @param string $icon Card icon (default: 'bi-clock-history')
 * @param string $header_class Header CSS class (default: 'bg-info text-white')
 * @param int $limit Maximum number of check-ins to display (default: 10)
 */

// Default parameters
$title = $title ?? 'Check-in Terakhir';
$icon = $icon ?? 'bi-clock-history';
$header_class = $header_class ?? 'bg-info text-white';
$limit = $limit ?? 10;
?>

<div class="card shadow">
    <div class="card-header <?php echo $header_class; ?>">
        <h5 class="mb-0">
            <i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($title); ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($checkins)): ?>
            <p class="text-muted">Belum ada check-in hari ini</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Kode</th>
                            <th>Event</th>
                            <th>Pengunjung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $display_count = 0;
                        foreach ($checkins as $checkin): 
                            if ($display_count >= $limit) break;
                            $display_count++;
                        ?>
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($checkin['waktu_checkin'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($checkin['kode_tiket']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($checkin['nama_event']); ?></td>
                                <td><?php echo htmlspecialchars($checkin['nama_user']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
