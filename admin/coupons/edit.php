<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE coupon_id = ?"); $stmt->execute([$id]);
$coupon = $stmt->fetch();
if (!$coupon) { set_flash('danger', 'Coupon not found.'); redirect(BASE_URL . 'admin/coupons/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percentage';
    $value = (float)($_POST['discount_value'] ?? 0);
    $minOrder = (float)($_POST['min_order_amount'] ?? 0);
    $maxDiscount = $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null;
    $usageLimit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($value <= 0) $errors[] = 'Please enter a valid discount value.';
    if (!$startDate || !$endDate || strtotime($endDate) <= strtotime($startDate)) $errors[] = 'Please provide a valid date range.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE coupons SET discount_type=?, discount_value=?, min_order_amount=?, max_discount_amount=?, usage_limit=?, start_date=?, end_date=?, status=? WHERE coupon_id=?")
            ->execute([$type, $value, $minOrder, $maxDiscount, $usageLimit, $startDate, $endDate, $status, $id]);
        set_flash('success', 'Coupon updated.');
        redirect(BASE_URL . 'admin/coupons/index.php');
    }
}
$pageTitle = 'Edit Coupon';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="sc-card" style="max-width:640px">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label>Coupon Code</label><input type="text" class="form-control" value="<?= e($coupon['coupon_code']) ?>" disabled></div>
        <div class="col-md-6"><label>Discount Type</label>
          <select name="discount_type" class="form-select">
            <option value="percentage" <?= $coupon['discount_type']==='percentage'?'selected':'' ?>>Percentage (%)</option>
            <option value="fixed" <?= $coupon['discount_type']==='fixed'?'selected':'' ?>>Fixed Amount (Rs.)</option>
          </select>
        </div>
        <div class="col-md-6"><label>Discount Value</label><input type="number" step="0.01" name="discount_value" class="form-control" required value="<?= e($coupon['discount_value']) ?>"></div>
        <div class="col-md-6"><label>Max Discount</label><input type="number" step="0.01" name="max_discount_amount" class="form-control" value="<?= e($coupon['max_discount_amount']) ?>"></div>
        <div class="col-md-6"><label>Minimum Order Amount</label><input type="number" step="0.01" name="min_order_amount" class="form-control" value="<?= e($coupon['min_order_amount']) ?>"></div>
        <div class="col-md-6"><label>Usage Limit</label><input type="number" name="usage_limit" class="form-control" value="<?= e($coupon['usage_limit']) ?>"></div>
        <div class="col-md-6"><label>Start Date</label><input type="datetime-local" name="start_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($coupon['start_date'])) ?>"></div>
        <div class="col-md-6"><label>End Date</label><input type="datetime-local" name="end_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($coupon['end_date'])) ?>"></div>
        <div class="col-md-6"><label>Status</label>
          <select name="status" class="form-select"><option value="active" <?= $coupon['status']==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $coupon['status']==='inactive'?'selected':'' ?>>Inactive</option></select>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-sc-gold">Update Coupon</button> <a href="<?= BASE_URL ?>admin/coupons/index.php" class="btn btn-sc-outline">Cancel</a></div>
      </div>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
