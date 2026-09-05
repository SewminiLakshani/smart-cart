<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$newCustomers = $pdo->query("SELECT DATE(created_at) d, COUNT(*) cnt FROM users WHERE role_id=1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC")->fetchAll();
$topCustomers = $pdo->query("SELECT u.full_name, u.email, COUNT(o.order_id) order_count, COALESCE(SUM(o.total_amount),0) total_spent
                              FROM users u JOIN orders o ON o.user_id = u.user_id WHERE o.order_status != 'cancelled'
                              GROUP BY u.user_id ORDER BY total_spent DESC LIMIT 10")->fetchAll();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id=1")->fetchColumn();
$activeCustomers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

$labels = []; $map = [];
foreach ($newCustomers as $r) $map[$r['d']] = (int)$r['cnt'];
for ($i = 29; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $labels[] = date('M d', strtotime($d)); $data[] = $map[$d] ?? 0; }

$pageTitle = 'Customer Report';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php require __DIR__ . '/tabs.php'; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-6"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-people"></i></div><div><h3><?= $totalCustomers ?></h3><span class="label">Total Customers</span></div></div></div>
      <div class="col-md-6"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-person-check"></i></div><div><h3><?= $activeCustomers ?></h3><span class="label">Active in Last 30 Days</span></div></div></div>
    </div>

    <div class="sc-card mb-4">
      <h6 class="text-gold mb-3">New Customer Signups (30 Days)</h6>
      <canvas id="signupChart" height="90"></canvas>
    </div>

    <div class="sc-card">
      <h6 class="text-gold mb-3">Top Customers by Spend</h6>
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Customer</th><th>Orders</th><th>Total Spent</th></tr></thead>
        <tbody>
          <?php foreach ($topCustomers as $c): ?>
            <tr><td><?= e($c['full_name']) ?><br><span class="text-secondary small"><?= e($c['email']) ?></span></td><td><?= (int)$c['order_count'] ?></td><td><?= money($c['total_spent']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#a8a8ad'; Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
new Chart(document.getElementById('signupChart'), {
  type: 'line',
  data: { labels: <?= json_encode($labels) ?>, datasets: [{ label: 'New Customers', data: <?= json_encode($data) ?>, borderColor: '#d4af6a', backgroundColor: 'rgba(212,175,106,.15)', fill: true, tension: .35 }] },
  options: { plugins: { legend: { display: false } } }
});
</script>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
