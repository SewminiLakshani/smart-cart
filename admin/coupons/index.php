<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE coupons SET status = IF(status='active','inactive','active') WHERE coupon_id = ?")->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/coupons/index.php');
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$pageTitle = 'Coupons';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <div class="d-flex justify-content-end mb-3"><a href="<?= BASE_URL ?>admin/coupons/add.php" class="btn btn-sc-gold btn-sm">+ Add Coupon</a></div>
    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Usage</th><th>Valid Until</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $c):
            $expired = strtotime($c['end_date']) < time(); ?>
            <tr>
              <td><strong class="text-gold"><?= e($c['coupon_code']) ?></strong></td>
              <td><?= $c['discount_type'] === 'percentage' ? $c['discount_value'] . '%' : money($c['discount_value']) ?></td>
              <td class="small"><?= money($c['min_order_amount']) ?></td>
              <td class="small"><?= (int)$c['used_count'] ?> / <?= $c['usage_limit'] ?? '∞' ?></td>
              <td class="small text-secondary"><?= friendly_date($c['end_date'], 'M d, Y') ?></td>
              <td>
                <?php if ($expired): ?><span class="badge sc-badge bg-secondary">Expired</span>
                <?php else: ?><span class="badge sc-badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($c['status']) ?></span><?php endif; ?>
              </td>
              <td class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/coupons/edit.php?id=<?= $c['coupon_id'] ?>" class="text-gold small">Edit</a>
                <a href="?toggle=<?= $c['coupon_id'] ?>" class="text-secondary small">Toggle</a>
                <a href="<?= BASE_URL ?>admin/coupons/delete.php?id=<?= $c['coupon_id'] ?>" class="text-danger small js-confirm" data-confirm="Delete this coupon?">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($coupons)): ?><tr><td colspan="7" class="text-center text-secondary py-4">No coupons yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
