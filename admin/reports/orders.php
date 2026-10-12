<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$statusBreakdown = $pdo->query("SELECT order_status, COUNT(*) cnt FROM orders GROUP BY order_status")->fetchAll();
$deliveryBreakdown = $pdo->query("SELECT delivery_method, COUNT(*) cnt FROM orders GROUP BY delivery_method")->fetchAll();
$cancelRate = $pdo->query("SELECT
    (SELECT COUNT(*) FROM orders WHERE order_status='cancelled') AS cancelled,
    (SELECT COUNT(*) FROM orders) AS total")->fetch();
$cancelPct = $cancelRate['total'] > 0 ? round($cancelRate['cancelled'] / $cancelRate['total'] * 100, 1) : 0;

$avgFulfillment = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, h.created_at)) avg_hours
                                FROM orders o JOIN order_status_history h ON h.order_id = o.order_id AND h.status = 'delivered'")->fetchColumn();

$pageTitle = 'Order Report';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php require __DIR__ . '/tabs.php'; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,102,95,.15);color:var(--sc-danger)"><i class="bi bi-x-circle"></i></div><div><h3><?= $cancelPct ?>%</h3><span class="label">Cancellation Rate</span></div></div></div>
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-clock-history"></i></div><div><h3><?= $avgFulfillment ? round($avgFulfillment) . 'h' : '-' ?></h3><span class="label">Avg. Fulfillment Time</span></div></div></div>
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(111,168,220,.15);color:var(--sc-info)"><i class="bi bi-bag-check"></i></div><div><h3><?= (int)$cancelRate['total'] ?></h3><span class="label">Total Orders</span></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Orders by Status</h6>
          <canvas id="statusChart" height="200"></canvas>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Delivery Method Split</h6>
          <canvas id="deliveryChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#a8a8ad'; Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: { labels: <?= json_encode(array_map(fn($s) => ucfirst($s['order_status']), $statusBreakdown)) ?>,
    datasets: [{ data: <?= json_encode(array_map(fn($s) => (int)$s['cnt'], $statusBreakdown)) ?>, backgroundColor: ['#d4af6a','#6fa8dc','#e0b25f','#b9903f','#4caf80','#e0665f'] }] },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
});
new Chart(document.getElementById('deliveryChart'), {
  type: 'pie',
  data: { labels: <?= json_encode(array_map(fn($d) => ucfirst($d['delivery_method']), $deliveryBreakdown)) ?>,
    datasets: [{ data: <?= json_encode(array_map(fn($d) => (int)$d['cnt'], $deliveryBreakdown)) ?>, backgroundColor: ['#d4af6a', '#6fa8dc'] }] },
  options: { plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
