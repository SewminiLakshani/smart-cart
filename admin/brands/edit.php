<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM brands WHERE brand_id = ?"); $stmt->execute([$id]);
$brand = $stmt->fetch();
if (!$brand) { set_flash('danger', 'Brand not found.'); redirect(BASE_URL . 'admin/brands/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['brand_name'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    if (strlen($name) < 2) $errors[] = 'Brand name is required.';
    if (empty($errors)) {
        $pdo->prepare("UPDATE brands SET brand_name=?, status=? WHERE brand_id=?")->execute([$name, $status, $id]);
        set_flash('success', 'Brand updated.');
        redirect(BASE_URL . 'admin/brands/index.php');
    }
}
$pageTitle = 'Edit Brand';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="sc-card" style="max-width:480px">
      <?= csrf_field() ?>
      <div class="mb-3"><label>Brand Name</label><input type="text" name="brand_name" class="form-control" required value="<?= e($brand['brand_name']) ?>"></div>
      <div class="mb-3"><label>Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $brand['status']==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $brand['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <button type="submit" class="btn btn-sc-gold">Update Brand</button>
      <a href="<?= BASE_URL ?>admin/brands/index.php" class="btn btn-sc-outline">Cancel</a>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
