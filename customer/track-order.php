<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['user_id']]);
$order = $stmt->fetch();
if (!$order) { set_flash('danger', 'Order not found.'); redirect(BASE_URL . 'customer/orders.php'); }

$history = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
$history->execute([$orderId]);
$history = $history->fetchAll();
$historyByStatus = [];
foreach ($history as $h) $historyByStatus[$h['status']] = $h;

$steps = ['pending' => 'Order Placed', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$statusOrder = array_keys($steps);
$currentIndex = array_search($order['order_status'], $statusOrder, true);
$isCancelled = $order['order_status'] === 'cancelled';

$pageTitle = 'Track Order';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <h3 class="mb-1">Track Order <?= e($order['order_number']) ?></h3>
    <p class="text-secondary mb-4">Current status: <?= order_status_badge($order['order_status']) ?></p>

    <div class="sc-card">
      <?php if ($isCancelled): ?>
        <div class="alert alert-danger">This order was cancelled on <?= friendly_date($order['updated_at']) ?>.</div>
      <?php else: ?>
        <div class="sc-timeline">
          <?php foreach ($steps as $key => $label): $done = $currentIndex !== false && array_search($key, $statusOrder, true) <= $currentIndex; ?>
            <div class="step <?= $done ? 'done' : '' ?>">
              <div class="dot"><i class="bi bi-<?= $key === 'pending' ? 'bag' : ($key === 'confirmed' ? 'check2' : ($key === 'processing' ? 'gear' : ($key === 'shipped' ? 'truck' : 'house-check'))) ?>"></i></div>
              <span><?= $label ?></span>
              <?php if (isset($historyByStatus[$key])): ?><div class="small text-secondary mt-1" style="opacity:.7"><?= friendly_date($historyByStatus[$key]['created_at'], 'M d, h:i A') ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="sc-divider"></div>
      <h6 class="text-gold mb-3">Status History</h6>
      <ul class="list-unstyled">
        <?php foreach (array_reverse($history) as $h): ?>
          <li class="mb-3 d-flex gap-3">
            <i class="bi bi-circle-fill text-gold" style="font-size:8px; margin-top:6px"></i>
            <div>
              <div class="fw-semibold small"><?= ucfirst($h['status']) ?></div>
              <div class="text-secondary small"><?= e($h['note']) ?> — <?= friendly_date($h['created_at']) ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
