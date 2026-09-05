<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$topSelling = $pdo->query("SELECT product_name, total_sold, avg_rating, review_count FROM products ORDER BY total_sold DESC LIMIT 10")->fetchAll();
$lowStock = $pdo->query("SELECT p.product_name, i.quantity FROM inventory i JOIN products p ON p.product_id=i.product_id WHERE i.stock_status IN ('low_stock','out_of_stock') ORDER BY i.quantity ASC LIMIT 10")->fetchAll();
$categoryPerf = $pdo->query("SELECT c.category_name, COUNT(p.product_id) product_count, COALESCE(SUM(p.total_sold),0) total_sold
                              FROM categories c LEFT JOIN products p ON p.category_id = c.category_id
                              GROUP BY c.category_id ORDER BY total_sold DESC")->fetchAll();

$pageTitle = 'Product Report';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php require __DIR__ . '/tabs.php'; ?>

    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Best Selling Products</h6>
          <canvas id="topProductsChart" height="220"></canvas>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Category Performance</h6>
          <canvas id="categoryPerfChart" height="220"></canvas>
        </div>
      </div>
    </div>

    <div class="sc-card">
      <h6 class="text-gold mb-3">Low Stock / Out of Stock Products</h6>
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Product</th><th>Quantity</th></tr></thead>
        <tbody>
          <?php foreach ($lowStock as $p): ?><tr><td><?= e($p['product_name']) ?></td><td><?= (int)$p['quantity'] ?></td></tr><?php endforeach; ?>
          <?php if (empty($lowStock)): ?><tr><td colspan="2" class="text-center text-secondary py-3">All products well stocked.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#a8a8ad'; Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
new Chart(document.getElementById('topProductsChart'), {
  type: 'bar',
  data: { labels: <?= json_encode(array_map(fn($p) => $p['product_name'], $topSelling)) ?>,
    datasets: [{ label: 'Units Sold', data: <?= json_encode(array_map(fn($p) => (int)$p['total_sold'], $topSelling)) ?>, backgroundColor: '#d4af6a', borderRadius: 6 }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});
new Chart(document.getElementById('categoryPerfChart'), {
  type: 'doughnut',
  data: { labels: <?= json_encode(array_map(fn($c) => $c['category_name'], $categoryPerf)) ?>,
    datasets: [{ data: <?= json_encode(array_map(fn($c) => (int)$c['total_sold'], $categoryPerf)) ?>, backgroundColor: ['#d4af6a','#6fa8dc','#e0b25f','#b9903f','#4caf80','#e0665f'] }] },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } }
});
</script>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
