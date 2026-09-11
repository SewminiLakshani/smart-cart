<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();

$status = $_GET['status'] ?? '';
$where = "WHERE user_id = ?";
$params = [$user['user_id']];
if ($status !== '') { $where .= " AND order_status = ?"; $params[] = $status; }

$stmt = $pdo->prepare("SELECT * FROM orders $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <h3 class="mb-0">My Orders</h3>
      <form method="get" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if (empty($orders)): ?>
      <div class="sc-card text-center py-5">
        <i class="bi bi-bag fs-1 text-gold mb-3 d-block"></i>
        <h5>No orders found</h5>
        <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-gold mt-2">Start Shopping</a>
      </div>
    <?php else: ?>
      <div class="sc-table-wrap">
        <table class="table align-middle mb-0">
          <thead class="sc-thead"><tr><th>Order #</th><th>Date</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o):
              $cnt = $pdo->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id = ?"); $cnt->execute([$o['order_id']]); $itemCount = (int)$cnt->fetchColumn(); ?>
              <tr>
                <td><?= e($o['order_number']) ?></td>
                <td class="small text-secondary"><?= friendly_date($o['created_at'], 'M d, Y') ?></td>
                <td><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></td>
                <td class="text-uppercase small"><?= $o['payment_method'] === 'cod' ? 'COD' : 'Card' ?></td>
                <td><?= money($o['total_amount']) ?></td>
                <td><?= order_status_badge($o['order_status']) ?></td>
                <td class="d-flex gap-2">
                  <a href="<?= BASE_URL ?>customer/order-details.php?id=<?= $o['order_id'] ?>" class="text-gold small">View</a>
                  <a href="<?= BASE_URL ?>customer/track-order.php?id=<?= $o['order_id'] ?>" class="text-gold small">Track</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
