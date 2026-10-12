<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('staff');
$user = current_user();
$pdo = db();
$sid = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['order_status'];
    $validNext = ['confirmed','processing','shipped','delivered'];

    $chk = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND assigned_staff_id = ?");
    $chk->execute([$orderId, $sid]);
    $order = $chk->fetch();

    if ($order && in_array($newStatus, $validNext, true)) {
        $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
        $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)")
            ->execute([$orderId, $newStatus, 'Updated by staff', $sid]);
        if ($newStatus === 'delivered') {
            $pdo->prepare("UPDATE payments SET payment_status='paid', paid_at=NOW() WHERE order_id=? AND payment_method='cod'")->execute([$orderId]);
        }
        create_notification((int)$order['user_id'], 'customer', 'Order Update', "Your order {$order['order_number']} is now $newStatus.", 'order_' . $newStatus, BASE_URL . 'customer/order-details.php?id=' . $orderId);
        set_flash('success', 'Order status updated.');
    } else {
        set_flash('danger', 'You are not authorized to update this order.');
    }
    redirect(BASE_URL . 'staff/orders.php');
}

$status = $_GET['status'] ?? '';
$where = "assigned_staff_id = ?"; $params = [$sid];
if ($status) { $where .= " AND order_status = ?"; $params[] = $status; }

$stmt = $pdo->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id WHERE $where ORDER BY o.created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Assigned Orders';
require __DIR__ . '/../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <h3 class="mb-4">Assigned Orders</h3>
    <form method="get" class="mb-3">
      <select name="status" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['pending','confirmed','processing','shipped','delivered'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td class="small"><?= e($o['full_name']) ?></td>
              <td class="small"><?= money($o['total_amount']) ?></td>
              <td><?= order_status_badge($o['order_status']) ?></td>
              <td>
                <?php if (in_array($o['order_status'], ['pending','confirmed','processing','shipped'])): ?>
                <form method="post" class="d-flex gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                  <select name="order_status" class="form-select form-select-sm" style="width:140px">
                    <?php foreach (['confirmed','processing','shipped','delivered'] as $s): ?>
                      <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sc-gold btn-sm">Update</button>
                </form>
                <?php else: ?><span class="text-secondary small">-</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?><tr><td colspan="5" class="text-center text-secondary py-4">No orders assigned.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
