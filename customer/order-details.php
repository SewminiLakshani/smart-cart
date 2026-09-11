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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    verify_csrf();
    if (in_array($order['order_status'], ['pending', 'confirmed'])) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?")->execute([$orderId]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?, 'cancelled', 'Cancelled by customer', ?)")->execute([$orderId, $user['user_id']]);
            $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $it) {
                adjust_stock((int)$it['product_id'], (int)$it['quantity'], 'return', $user['user_id'], 'Order #' . $orderId . ' cancelled');
            }
            $pdo->commit();
            create_notification($user['user_id'], 'customer', 'Order Cancelled', 'Your order ' . $order['order_number'] . ' has been cancelled.', 'order_cancelled', BASE_URL . 'customer/order-details.php?id=' . $orderId);
            notify_admins_and_staff('Order Cancelled', 'Order ' . $order['order_number'] . ' was cancelled by the customer.', 'order_cancelled', '/admin/orders/details.php?id=' . $orderId);
            set_flash('success', 'Your order has been cancelled.');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            set_flash('danger', 'Could not cancel the order. Please try again.');
        }
        redirect(BASE_URL . 'customer/order-details.php?id=' . $orderId);
    }
}

$items = $pdo->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?");
$items->execute([$orderId]);
$items = $items->fetchAll();

$payment = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
$payment->execute([$orderId]);
$payment = $payment->fetch();

$canCancel = in_array($order['order_status'], ['pending', 'confirmed']);
$justPlaced = isset($_GET['placed']);

$pageTitle = 'Order Details';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php if ($justPlaced): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Thank you! Your order has been placed successfully.</div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-0">Order <?= e($order['order_number']) ?></h3>
        <p class="text-secondary small mb-0">Placed on <?= friendly_date($order['created_at']) ?></p>
      </div>
      <div class="d-flex gap-2">
        <?= order_status_badge($order['order_status']) ?>
        <?php if ($canCancel): ?>
          <form method="post" onsubmit="return confirm('Cancel this order?')"><?= csrf_field() ?>
            <button type="submit" name="cancel_order" class="btn btn-sc-outline btn-sm text-danger">Cancel Order</button>
          </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>customer/track-order.php?id=<?= $orderId ?>" class="btn btn-sc-gold btn-sm">Track Order</a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="sc-table-wrap mb-4">
          <table class="table align-middle mb-0">
            <thead class="sc-thead"><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= $it['slug'] ? '<a style="color:var(--sc-white)" href="' . BASE_URL . 'products/product-details.php?slug=' . e($it['slug']) . '">' . e($it['product_name']) . '</a>' : e($it['product_name']) ?></td>
                  <td><?= money($it['unit_price']) ?></td>
                  <td><?= $it['quantity'] ?></td>
                  <td><?= money($it['line_total']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="sc-card">
          <h6 class="text-gold mb-3">Delivery Address</h6>
          <p class="mb-1"><?= e($order['shipping_full_name']) ?> — <?= e($order['shipping_phone']) ?></p>
          <p class="text-secondary mb-0"><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?></p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3">Payment Summary</h6>
          <div class="d-flex justify-content-between small mb-2"><span class="text-secondary">Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
          <?php if ($order['discount_amount'] > 0): ?><div class="d-flex justify-content-between small mb-2 text-success"><span>Discount</span><span>-<?= money($order['discount_amount']) ?></span></div><?php endif; ?>
          <div class="d-flex justify-content-between small mb-2"><span class="text-secondary">Delivery (<?= ucfirst($order['delivery_method']) ?>)</span><span><?= $order['delivery_fee'] > 0 ? money($order['delivery_fee']) : 'FREE' ?></span></div>
          <div class="sc-divider"></div>
          <div class="d-flex justify-content-between fs-5"><span>Total</span><strong class="text-gold"><?= money($order['total_amount']) ?></strong></div>
        </div>
        <div class="sc-card">
          <h6 class="text-gold mb-3">Payment Method</h6>
          <p class="mb-1 text-uppercase"><?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card Payment' ?></p>
          <?php if ($payment): ?>
            <span class="badge sc-badge bg-<?= $payment['payment_status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($payment['payment_status']) ?></span>
            <?php if ($payment['card_last_four']): ?><p class="text-secondary small mt-2 mb-0">Card ending in <?= e($payment['card_last_four']) ?></p><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
