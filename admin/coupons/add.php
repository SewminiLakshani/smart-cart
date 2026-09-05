<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $type = $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percentage';
    $value = (float)($_POST['discount_value'] ?? 0);
    $minOrder = (float)($_POST['min_order_amount'] ?? 0);
    $maxDiscount = $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null;
    $usageLimit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if (strlen($code) < 3) $errors[] = 'Coupon code must be at least 3 characters.';
    if ($value <= 0) $errors[] = 'Please enter a valid discount value.';
    if ($type === 'percentage' && $value > 100) $errors[] = 'Percentage discount cannot exceed 100%.';
    if (!$startDate || !$endDate || strtotime($endDate) <= strtotime($startDate)) $errors[] = 'Please provide a valid date range.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE coupon_code = ?"); $chk->execute([$code]);
        if ($chk->fetchColumn() > 0) { $errors[] = 'This coupon code already exists.'; }
    }

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO coupons (coupon_code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, start_date, end_date, status) VALUES (?,?,?,?,?,?,?,?,'active')")
            ->execute([$code, $type, $value, $minOrder, $maxDiscount, $usageLimit, $startDate, $endDate]);
        set_flash('success', 'Coupon created successfully.');
        redirect(BASE_URL . 'admin/coupons/index.php');
    }
}
$pageTitle = 'Add Coupon';
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
        <div class="col-md-6"><label>Coupon Code</label><input type="text" name="coupon_code" class="form-control text-uppercase" required placeholder="e.g. SAVE20"></div>
        <div class="col-md-6"><label>Discount Type</label>
          <select name="discount_type" class="form-select"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (Rs.)</option></select>
        </div>
        <div class="col-md-6"><label>Discount Value</label><input type="number" step="0.01" name="discount_value" class="form-control" required></div>
        <div class="col-md-6"><label>Max Discount (for %) — optional</label><input type="number" step="0.01" name="max_discount_amount" class="form-control"></div>
        <div class="col-md-6"><label>Minimum Order Amount</label><input type="number" step="0.01" name="min_order_amount" class="form-control" value="0"></div>
        <div class="col-md-6"><label>Usage Limit — optional</label><input type="number" name="usage_limit" class="form-control"></div>
        <div class="col-md-6"><label>Start Date</label><input type="datetime-local" name="start_date" class="form-control" required></div>
        <div class="col-md-6"><label>End Date</label><input type="datetime-local" name="end_date" class="form-control" required></div>
        <div class="col-12"><button type="submit" class="btn btn-sc-gold">Create Coupon</button> <a href="<?= BASE_URL ?>admin/coupons/index.php" class="btn btn-sc-outline">Cancel</a></div>
      </div>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
