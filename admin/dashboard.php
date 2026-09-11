<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();

$totalSales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$processingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'processing'")->fetchColumn();
$deliveredOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'")->fetchColumn();
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory WHERE stock_status IN ('low_stock','out_of_stock')")->fetchColumn();

$recentOrders = $pdo->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id ORDER BY o.created_at DESC LIMIT 6")->fetchAll();
$lowStockProducts = $pdo->query("SELECT p.product_name, i.quantity, i.stock_status FROM inventory i JOIN products p ON p.product_id = i.product_id WHERE i.stock_status IN ('low_stock','out_of_stock') ORDER BY i.quantity ASC LIMIT 6")->fetchAll();
$topProducts = $pdo->query("SELECT product_name, total_sold, avg_rating FROM products ORDER BY total_sold DESC LIMIT 5")->fetchAll();

// Sales trend for the last 14 days
$salesTrend = $pdo->query("
    SELECT DATE(created_at) d, SUM(total_amount) total FROM orders
    WHERE order_status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) ORDER BY d ASC")->fetchAll();
$trendLabels = []; $trendData = [];
$map = [];
foreach ($salesTrend as $row) $map[$row['d']] = (float)$row['total'];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M d', strtotime($d));
    $trendData[] = $map[$d] ?? 0;
}

// Category sales breakdown
$categorySales = $pdo->query("
    SELECT c.category_name, SUM(oi.line_total) total FROM order_items oi
    JOIN products p ON p.product_id = oi.product_id JOIN categories c ON c.category_id = p.category_id
    JOIN orders o ON o.order_id = oi.order_id WHERE o.order_status != 'cancelled'
    GROUP BY c.category_id ORDER BY total DESC")->fetchAll();

// Order status breakdown
$statusBreakdown = $pdo->query("SELECT order_status, COUNT(*) cnt FROM orders GROUP BY order_status")->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-cash-stack"></i></div>
          <div><h3><?= money($totalSales) ?></h3><span class="label">Total Sales</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(111,168,220,.15);color:var(--sc-info)"><i class="bi bi-bag-check"></i></div>
          <div><h3><?= $totalOrders ?></h3><span class="label">Total Orders</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-people"></i></div>
          <div><h3><?= $totalCustomers ?></h3><span class="label">Customers</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,178,95,.15);color:var(--sc-warning)"><i class="bi bi-box-seam"></i></div>
          <div><h3><?= $totalProducts ?></h3><span class="label">Products</span></div></div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(168,168,173,.15);color:var(--sc-gray)"><i class="bi bi-hourglass-split"></i></div>
          <div><h3><?= $pendingOrders ?></h3><span class="label">Pending Orders</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,178,95,.15);color:var(--sc-warning)"><i class="bi bi-gear"></i></div>
          <div><h3><?= $processingOrders ?></h3><span class="label">Processing</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-house-check"></i></div>
          <div><h3><?= $deliveredOrders ?></h3><span class="label">Delivered</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,102,95,.15);color:var(--sc-danger)"><i class="bi bi-exclamation-triangle"></i></div>
          <div><h3><?= $lowStockCount ?></h3><span class="label">Low Stock Alerts</span></div></div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Sales Trend (Last 14 Days)</h6>
          <canvas id="salesTrendChart" height="90"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Order Status Breakdown</h6>
          <canvas id="statusChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Category Sales</h6>
          <canvas id="categoryChart" height="180"></canvas>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Top Selling Products</h6></div>
          <?php foreach ($topProducts as $p): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small"><?= e($p['product_name']) ?></span>
              <span class="small text-gold"><?= (int)$p['total_sold'] ?> sold</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Recent Orders</h6><a href="<?= BASE_URL ?>admin/orders/index.php" class="small text-gold">View all</a></div>
          <div class="table-responsive"><table class="table align-middle">
            <thead class="sc-thead"><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td><a href="<?= BASE_URL ?>admin/orders/details.php?id=<?= $o['order_id'] ?>" class="text-white small"><?= e($o['order_number']) ?></a></td>
                  <td class="small"><?= e($o['full_name']) ?></td>
                  <td class="small"><?= money($o['total_amount']) ?></td>
                  <td><?= order_status_badge($o['order_status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table></div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Low Stock Products</h6><a href="<?= BASE_URL ?>admin/inventory/index.php" class="small text-gold">Manage</a></div>
          <?php if (empty($lowStockProducts)): ?><p class="text-secondary small">All products are well stocked.</p><?php endif; ?>
          <?php foreach ($lowStockProducts as $p): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small"><?= e($p['product_name']) ?></span>
              <span><?= stock_status_badge($p['stock_status']) ?> <span class="text-secondary small">(<?= $p['quantity'] ?>)</span></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#a8a8ad';
Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
Chart.defaults.font.family = 'Inter';

new Chart(document.getElementById('salesTrendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($trendLabels) ?>,
    datasets: [{ label: 'Sales (Rs.)', data: <?= json_encode($trendData) ?>, borderColor: '#d4af6a', backgroundColor: 'rgba(212,175,106,.15)', fill: true, tension: .35, pointRadius: 3 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_map(fn($s) => ucfirst($s['order_status']), $statusBreakdown)) ?>,
    datasets: [{ data: <?= json_encode(array_map(fn($s) => (int)$s['cnt'], $statusBreakdown)) ?>,
      backgroundColor: ['#d4af6a','#6fa8dc','#e0b25f','#b9903f','#4caf80','#e0665f'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
});

new Chart(document.getElementById('categoryChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($c) => $c['category_name'], $categorySales)) ?>,
    datasets: [{ label: 'Revenue', data: <?= json_encode(array_map(fn($c) => (float)$c['total'], $categorySales)) ?>, backgroundColor: '#d4af6a', borderRadius: 6 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
