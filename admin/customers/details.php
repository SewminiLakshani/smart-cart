<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role_id = 1");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { set_flash('danger', 'Customer not found.'); redirect(BASE_URL . 'admin/customers/index.php'); }

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$id]);
$orders = $orders->fetchAll();

$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ?");
$addresses->execute([$id]);
$addresses = $addresses->fetchAll();

$totalSpent = array_sum(array_map(fn($o) => $o['order_status'] !== 'cancelled' ? (float)$o['total_amount'] : 0, $orders));

$pageTitle = 'Customer Details';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="sc-card text-center mb-4">
          <div class="sc-avatar-circle mx-auto mb-3" style="width:64px;height:64px;font-size:24px"><?= e(strtoupper(substr($customer['full_name'],0,1))) ?></div>
          <h5><?= e($customer['full_name']) ?></h5>
          <p class="text-secondary small mb-1"><?= e($customer['email']) ?></p>
          <p class="text-secondary small mb-3"><?= e($customer['phone']) ?></p>
          <span class="badge sc-badge bg-<?= $customer['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($customer['status']) ?></span>
          <div class="sc-divider"></div>
          <p class="small text-secondary mb-0">Joined <?= friendly_date($customer['created_at'], 'M d, Y') ?></p>
          <p class="small text-secondary">Last login: <?= friendly_date($customer['last_login']) ?></p>
        </div>
        <div class="sc-card">
          <h6 class="text-gold mb-3">Saved Addresses</h6>
          <?php if (empty($addresses)): ?><p class="text-secondary small mb-0">No saved addresses.</p><?php endif; ?>
          <?php foreach ($addresses as $a): ?>
            <div class="mb-3 small">
              <strong><?= e($a['full_name']) ?></strong> (<?= ucfirst($a['address_type']) ?>)<br>
              <span class="text-secondary"><?= e($a['address_line']) ?>, <?= e($a['city']) ?>, <?= e($a['province']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-bag-check"></i></div><div><h3><?= count($orders) ?></h3><span class="label">Total Orders</span></div></div></div>
          <div class="col-6"><div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-cash-stack"></i></div><div><h3><?= money($totalSpent) ?></h3><span class="label">Total Spent</span></div></div></div>
        </div>
        <div class="sc-table-wrap">
          <table class="table align-middle mb-0">
            <thead class="sc-thead"><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><a href="<?= BASE_URL ?>admin/orders/details.php?id=<?= $o['order_id'] ?>" class="text-white small"><?= e($o['order_number']) ?></a></td>
                  <td class="small text-secondary"><?= friendly_date($o['created_at'], 'M d, Y') ?></td>
                  <td class="small"><?= money($o['total_amount']) ?></td>
                  <td><?= order_status_badge($o['order_status']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($orders)): ?><tr><td colspan="4" class="text-center text-secondary py-4">No orders yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
