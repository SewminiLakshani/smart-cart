<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$productId = (int)($_GET['product_id'] ?? 0);
$where = '1=1'; $params = [];
if ($productId) { $where = 'sh.product_id = ?'; $params[] = $productId; }

$stmt = $pdo->prepare("SELECT sh.*, p.product_name, u.full_name AS changed_by_name
                        FROM stock_history sh JOIN products p ON p.product_id = sh.product_id
                        LEFT JOIN users u ON u.user_id = sh.user_id
                        WHERE $where ORDER BY sh.created_at DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Stock History';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Date</th><th>Product</th><th>Type</th><th>Change</th><th>Previous</th><th>New</th><th>By</th><th>Note</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="small text-secondary"><?= friendly_date($r['created_at']) ?></td>
              <td class="small"><?= e($r['product_name']) ?></td>
              <td><span class="badge sc-badge bg-<?= $r['change_type'] === 'add' ? 'success' : ($r['change_type'] === 'reduce' ? 'danger' : 'secondary') ?>"><?= ucfirst($r['change_type']) ?></span></td>
              <td class="<?= $r['change_quantity'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $r['change_quantity'] >= 0 ? '+' : '' ?><?= (int)$r['change_quantity'] ?></td>
              <td><?= (int)$r['previous_quantity'] ?></td>
              <td><?= (int)$r['new_quantity'] ?></td>
              <td class="small"><?= e($r['changed_by_name'] ?? 'System') ?></td>
              <td class="small text-secondary"><?= e($r['note']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-secondary py-4">No stock history yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
