<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('staff');
$user = current_user();
$pdo = db();
$sid = $user['user_id'];

$assignedCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE assigned_staff_id = ?"); $assignedCount->execute([$sid]); $assignedCount = (int)$assignedCount->fetchColumn();
$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE assigned_staff_id = ? AND order_status IN ('pending','confirmed','processing')"); $pendingCount->execute([$sid]); $pendingCount = (int)$pendingCount->fetchColumn();
$shippedCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE assigned_staff_id = ? AND order_status = 'shipped'"); $shippedCount->execute([$sid]); $shippedCount = (int)$shippedCount->fetchColumn();
$deliveredCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE assigned_staff_id = ? AND order_status = 'delivered'"); $deliveredCount->execute([$sid]); $deliveredCount = (int)$deliveredCount->fetchColumn();

$recentAssigned = $pdo->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.assigned_staff_id = ? ORDER BY o.created_at DESC LIMIT 6");
$recentAssigned->execute([$sid]);
$recentAssigned = $recentAssigned->fetchAll();

$lowStock = $pdo->query("SELECT p.product_name, i.quantity, i.stock_status FROM inventory i JOIN products p ON p.product_id = i.product_id WHERE i.stock_status IN ('low_stock','out_of_stock') ORDER BY i.quantity ASC LIMIT 6")->fetchAll();

$pageTitle = 'Staff Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <div class="mb-4"><h3>Welcome, <?= e($user['full_name']) ?></h3><p class="text-secondary">Here's your assignment overview.</p></div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-bag-check"></i></div><div><h3><?= $assignedCount ?></h3><span class="label">Assigned Orders</span></div></div></div>
      <div class="col-6 col-lg-3"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,178,95,.15);color:var(--sc-warning)"><i class="bi bi-hourglass-split"></i></div><div><h3><?= $pendingCount ?></h3><span class="label">Pending Action</span></div></div></div>
      <div class="col-6 col-lg-3"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(111,168,220,.15);color:var(--sc-info)"><i class="bi bi-truck"></i></div><div><h3><?= $shippedCount ?></h3><span class="label">Shipped</span></div></div></div>
      <div class="col-6 col-lg-3"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-house-check"></i></div><div><h3><?= $deliveredCount ?></h3><span class="label">Delivered</span></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Recently Assigned Orders</h6><a href="<?= BASE_URL ?>staff/orders.php" class="small text-gold">View all</a></div>
          <?php if (empty($recentAssigned)): ?><p class="text-secondary small">No orders assigned to you yet.</p><?php endif; ?>
          <table class="table align-middle mb-0">
            <?php foreach ($recentAssigned as $o): ?>
              <tr>
                <td><a href="<?= BASE_URL ?>staff/orders.php?id=<?= $o['order_id'] ?>" class="text-white small"><?= e($o['order_number']) ?></a></td>
                <td class="small"><?= e($o['full_name']) ?></td>
                <td><?= order_status_badge($o['order_status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Low Stock Alerts</h6><a href="<?= BASE_URL ?>staff/inventory.php" class="small text-gold">View all</a></div>
          <?php foreach ($lowStock as $p): ?>
            <div class="d-flex justify-content-between mb-2"><span class="small"><?= e($p['product_name']) ?></span><?= stock_status_badge($p['stock_status']) ?></div>
          <?php endforeach; ?>
          <?php if (empty($lowStock)): ?><p class="text-secondary small">All products well stocked.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
