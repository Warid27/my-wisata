<?php
/**
 * Dashboard Stats Cards Component
 * Displays key statistics cards with percentage changes
 * 
 * @param array $stats Dashboard statistics from get_dashboard_stats()
 */
if (!isset($stats)) {
    return;
}
?>

<!-- Stats Cards -->
<div class="row dashboard-stats">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Total User</div>
                        <div class="stat-value"><?php echo format_number_short($stats['total_users']); ?></div>
                        <div class="stat-change <?php echo $stats['users_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="bi bi-arrow-<?php echo $stats['users_change'] >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs(round($stats['users_change'], 1)); ?>% dari bulan lalu
                        </div>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-value"><?php echo format_number_short($stats['total_orders']); ?></div>
                        <div class="stat-change <?php echo $stats['orders_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="bi bi-arrow-<?php echo $stats['orders_change'] >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs(round($stats['orders_change'], 1)); ?>% dari bulan lalu
                        </div>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Total Pendapatan</div>
                        <div class="stat-value"><?php echo format_currency_short($stats['total_revenue']); ?></div>
                        <div class="stat-change <?php echo $stats['revenue_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="bi bi-arrow-<?php echo $stats['revenue_change'] >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs(round($stats['revenue_change'], 1)); ?>% dari bulan lalu
                        </div>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Total Event</div>
                        <div class="stat-value"><?php echo format_number_short($stats['total_events']); ?></div>
                        <div class="stat-change <?php echo $stats['events_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="bi bi-arrow-<?php echo $stats['events_change'] >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs(round($stats['events_change'], 1)); ?>% dari bulan lalu
                        </div>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
