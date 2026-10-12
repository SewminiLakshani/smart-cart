<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $productId = (int)$_POST['product_id'];
    $type = $_POST['adjust_type'] === 'reduce' ? 'reduce' : 'add';
    $amount = max(1, (int)$_POST['adjust_amount']);
    adjust_stock($productId, $type === 'add' ? $amount : -$amount, $type, $user['user_id'], 'Adjusted via Inventory panel');
    set_flash('success', 'Stock updated.');
    redirect(BASE_URL . 'admin/inventory/index.php');
}

$filter = $_GET['status'] ?? '';
$where = '1=1'; $params = [];
if ($filter) { $where = 'i.stock_status = ?'; $params[] = $filter; }

$stmt = $pdo->prepare("SELECT p.product_id, p.product_name, i.quantity, i.low_stock_threshold, i.stock_status
                        FROM inventory i JOIN products p ON p.product_id = i.product_id
                        WHERE $where ORDER BY i.quantity ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Inventory';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <a href="?" class="btn btn-sc-dark btn-sm <?= $filter === '' ? 'border-gold' : '' ?>">All</a>
      <a href="?status=available" class="btn btn-sc-dark btn-sm">Available</a>
      <a href="?status=low_stock" class="btn btn-sc-dark btn-sm">Low Stock</a>
      <a href="?status=out_of_stock" class="btn btn-sc-dark btn-sm">Out of Stock</a>
    </div>

    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Product</th><th>Current Stock</th><th>Threshold</th><th>Status</th><th>Quick Adjust</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['product_name']) ?></td>
              <td><strong><?= (int)$r['quantity'] ?></strong></td>
              <td class="text-secondary small"><?= (int)$r['low_stock_threshold'] ?></td>
              <td><?= stock_status_badge($r['stock_status']) ?></td>
              <td>
                <form method="post" class="d-flex gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                  <select name="adjust_type" class="form-select form-select-sm" style="width:100px"><option value="add">Add</option><option value="reduce">Reduce</option></select>
                  <input type="number" name="adjust_amount" class="form-control form-control-sm" style="width:80px" min="1" value="1" required>
                  <button class="btn btn-sc-gold btn-sm">Go</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-secondary py-4">No matching products.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <a href="<?= BASE_URL ?>admin/inventory/stock-history.php" class="btn btn-sc-outline btn-sm mt-3">View Full Stock History</a>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
