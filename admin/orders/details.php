<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email, u.phone AS customer_phone FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) { set_flash('danger', 'Order not found.'); redirect(BASE_URL . 'admin/orders/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['order_status'];
        $note = trim($_POST['status_note'] ?? '');
        $validStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
        if (in_array($newStatus, $validStatuses, true)) {
            $pdo->beginTransaction();
            try {
                if ($newStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
                    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $items->execute([$orderId]);
                    foreach ($items->fetchAll() as $it) {
                        adjust_stock((int)$it['product_id'], (int)$it['quantity'], 'return', $user['user_id'], 'Order #' . $orderId . ' cancelled by admin');
                    }
                }
                $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
                $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)")
                    ->execute([$orderId, $newStatus, $note ?: 'Status updated by admin', $user['user_id']]);
                if ($newStatus === 'delivered') {
                    $pdo->prepare("UPDATE payments SET payment_status = 'paid', paid_at = NOW() WHERE order_id = ? AND payment_method = 'cod'")->execute([$orderId]);
                }
                $pdo->commit();
                $labels = ['pending'=>'Order Received','confirmed'=>'Order Confirmed','processing'=>'Order Processing','shipped'=>'Order Shipped','delivered'=>'Order Delivered','cancelled'=>'Order Cancelled'];
                create_notification((int)$order['user_id'], 'customer', $labels[$newStatus], "Your order {$order['order_number']} is now " . $newStatus . ".", 'order_' . $newStatus, BASE_URL . 'customer/order-details.php?id=' . $orderId);
                set_flash('success', 'Order status updated.');
            } catch (Throwable $ex) {
                $pdo->rollBack();
                set_flash('danger', 'Could not update order status.');
            }
        }
        redirect(BASE_URL . 'admin/orders/details.php?id=' . $orderId);
    }

    if (isset($_POST['assign_staff'])) {
        $staffId = (int)$_POST['staff_id'] ?: null;
        $pdo->prepare("UPDATE orders SET assigned_staff_id = ? WHERE order_id = ?")->execute([$staffId, $orderId]);
        if ($staffId) {
            create_notification($staffId, 'staff', 'Order Assigned', "You have been assigned order {$order['order_number']}.", 'order_assigned', BASE_URL . 'staff/orders.php?id=' . $orderId);
        }
        set_flash('success', 'Staff assignment updated.');
        redirect(BASE_URL . 'admin/orders/details.php?id=' . $orderId);
    }
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$orderId]);
$items = $items->fetchAll();

$payment = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$payment->execute([$orderId]);
$payment = $payment->fetch();

$history = $pdo->prepare("SELECT h.*, u.full_name FROM order_status_history h LEFT JOIN users u ON u.user_id = h.changed_by WHERE h.order_id = ? ORDER BY h.created_at DESC");
$history->execute([$orderId]);
$history = $history->fetchAll();

$staffList = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 3 AND status = 'active'")->fetchAll();

$pageTitle = 'Order #' . $order['order_number'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div><h4 class="mb-0"><?= e($order['order_number']) ?></h4><p class="text-secondary small mb-0">Placed <?= friendly_date($order['created_at']) ?></p></div>
      <?= order_status_badge($order['order_status']) ?>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="sc-table-wrap mb-4">
          <table class="table align-middle mb-0">
            <thead class="sc-thead"><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td><?= money($it['unit_price']) ?></td><td><?= $it['quantity'] ?></td><td><?= money($it['line_total']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="row g-4">
          <div class="col-md-6">
            <div class="sc-card h-100">
              <h6 class="text-gold mb-3">Customer Info</h6>
              <p class="mb-1"><?= e($order['full_name']) ?></p>
              <p class="text-secondary small mb-1"><?= e($order['email']) ?></p>
              <p class="text-secondary small mb-0"><?= e($order['customer_phone']) ?></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="sc-card h-100">
              <h6 class="text-gold mb-3">Shipping Address</h6>
              <p class="mb-1"><?= e($order['shipping_full_name']) ?> — <?= e($order['shipping_phone']) ?></p>
              <p class="text-secondary small mb-0"><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?></p>
            </div>
          </div>
        </div>

        <div class="sc-card mt-4">
          <h6 class="text-gold mb-3">Status History</h6>
          <ul class="list-unstyled mb-0">
            <?php foreach ($history as $h): ?>
              <li class="mb-2 d-flex gap-3">
                <i class="bi bi-circle-fill text-gold" style="font-size:7px;margin-top:6px"></i>
                <div><span class="small fw-semibold"><?= ucfirst($h['status']) ?></span> <span class="text-secondary small">— <?= e($h['note']) ?> (<?= e($h['full_name'] ?? 'System') ?>, <?= friendly_date($h['created_at']) ?>)</span></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3">Order Summary</h6>
          <div class="d-flex justify-content-between small mb-2"><span class="text-secondary">Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
          <?php if ($order['discount_amount'] > 0): ?><div class="d-flex justify-content-between small mb-2 text-success"><span>Discount</span><span>-<?= money($order['discount_amount']) ?></span></div><?php endif; ?>
          <div class="d-flex justify-content-between small mb-2"><span class="text-secondary">Delivery</span><span><?= $order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'FREE' ?></span></div>
          <div class="sc-divider"></div>
          <div class="d-flex justify-content-between fs-5"><span>Total</span><strong class="text-gold"><?= money($order['total_amount']) ?></strong></div>
          <?php if ($payment): ?><p class="small text-secondary mt-3 mb-0">Payment: <?= strtoupper($order['payment_method']) ?> — <span class="badge sc-badge bg-<?= $payment['payment_status']==='paid'?'success':'secondary' ?>"><?= ucfirst($payment['payment_status']) ?></span></p><?php endif; ?>
        </div>

        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3">Update Order Status</h6>
          <form method="post">
            <?= csrf_field() ?>
            <select name="order_status" class="form-select form-select-sm mb-2">
              <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="status_note" class="form-control form-control-sm mb-2" placeholder="Note (optional)">
            <button type="submit" name="update_status" class="btn btn-sc-gold btn-sm w-100">Update Status</button>
          </form>
        </div>

        <div class="sc-card">
          <h6 class="text-gold mb-3">Assign to Staff</h6>
          <form method="post">
            <?= csrf_field() ?>
            <select name="staff_id" class="form-select form-select-sm mb-2">
              <option value="">Unassigned</option>
              <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['user_id'] ?>" <?= $order['assigned_staff_id'] == $s['user_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_staff" class="btn btn-sc-outline btn-sm w-100">Assign</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
