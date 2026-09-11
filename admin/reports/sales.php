<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT DATE(created_at) d, SUM(total_amount) total, COUNT(*) cnt FROM orders
                        WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at) ORDER BY d ASC");
$stmt->execute([$startDate, $endDate]);
$daily = $stmt->fetchAll();

$summary = $pdo->prepare("SELECT COUNT(*) orders_count, COALESCE(SUM(total_amount),0) revenue, COALESCE(AVG(total_amount),0) aov
                           FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?");
$summary->execute([$startDate, $endDate]);
$summary = $summary->fetch();

$byPayment = $pdo->prepare("SELECT payment_method, COUNT(*) cnt, SUM(total_amount) total FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ? GROUP BY payment_method");
$byPayment->execute([$startDate, $endDate]);
$byPayment = $byPayment->fetchAll();

$pageTitle = 'Sales Report';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php require __DIR__ . '/tabs.php'; ?>

    <form method="get" class="d-flex gap-2 mb-4">
      <input type="hidden" name="tab" value="sales">
      <input type="date" name="start" class="form-control form-control-sm" value="<?= e($startDate) ?>">
      <input type="date" name="end" class="form-control form-control-sm" value="<?= e($endDate) ?>">
      <button class="btn btn-sc-gold btn-sm">Apply</button>
    </form>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-cash-stack"></i></div><div><h3><?= money($summary['revenue']) ?></h3><span class="label">Revenue</span></div></div></div>
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(111,168,220,.15);color:var(--sc-info)"><i class="bi bi-bag-check"></i></div><div><h3><?= (int)$summary['orders_count'] ?></h3><span class="label">Orders</span></div></div></div>
      <div class="col-md-4"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-graph-up"></i></div><div><h3><?= money($summary['aov']) ?></h3><span class="label">Avg. Order Value</span></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Revenue Over Time</h6>
          <canvas id="reportChart" height="100"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Payment Method Split</h6>
          <canvas id="paymentChart" height="180"></canvas>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#a8a8ad'; Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
new Chart(document.getElementById('reportChart'), {
  type: 'bar',
  data: { labels: <?= json_encode(array_map(fn($d) => date('M d', strtotime($d['d'])), $daily)) ?>,
    datasets: [{ label: 'Revenue', data: <?= json_encode(array_map(fn($d) => (float)$d['total'], $daily)) ?>, backgroundColor: '#d4af6a', borderRadius: 6 }] },
  options: { plugins: { legend: { display: false } } }
});
new Chart(document.getElementById('paymentChart'), {
  type: 'pie',
  data: { labels: <?= json_encode(array_map(fn($p) => strtoupper($p['payment_method']), $byPayment)) ?>,
    datasets: [{ data: <?= json_encode(array_map(fn($p) => (float)$p['total'], $byPayment)) ?>, backgroundColor: ['#d4af6a', '#6fa8dc'] }] },
  options: { plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
