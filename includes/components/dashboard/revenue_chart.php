<?php
/**
 * Revenue Chart Component
 * Displays monthly revenue and orders chart
 * 
 * @param array $monthly_revenue Monthly revenue data
 */
if (!isset($monthly_revenue)) {
    return;
}

// Prepare data for Chart.js
$labels = [];
$revenue_data = [];
$orders_data = [];

foreach ($monthly_revenue as $month) {
    // Convert YYYY-MM to readable format
    $date = DateTime::createFromFormat('Y-m', $month['month']);
    $labels[] = $date ? $date->format('M Y') : $month['month'];
    $revenue_data[] = (int)$month['revenue'];
    $orders_data[] = (int)$month['orders'];
}
?>

<!-- Revenue Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h6 class="card-title mb-0">Pendapatan & Pesanan (6 Bulan Terakhir)</h6>
                <small class="text-muted">Grafik pendapatan dan jumlah pesanan per bulan</small>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart data for JavaScript -->
<script>
window.revenueChartData = {
    labels: <?php echo json_encode($labels); ?>,
    revenue: <?php echo json_encode($revenue_data); ?>,
    orders: <?php echo json_encode($orders_data); ?>
};
</script>
