<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['brand_name'] ?? '');
    if (strlen($name) < 2) $errors[] = 'Brand name is required.';
    if (empty($errors)) {
        $slug = slugify($name);
        $chk = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE slug = ?"); $chk->execute([$slug]);
        if ($chk->fetchColumn() > 0) $slug .= '-' . substr(uniqid(), -4);
        $pdo->prepare("INSERT INTO brands (brand_name, slug, status) VALUES (?,?,'active')")->execute([$name, $slug]);
        set_flash('success', 'Brand added successfully.');
        redirect(BASE_URL . 'admin/brands/index.php');
    }
}
$pageTitle = 'Add Brand';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="sc-card" style="max-width:480px">
      <?= csrf_field() ?>
      <div class="mb-3"><label>Brand Name</label><input type="text" name="brand_name" class="form-control" required></div>
      <button type="submit" class="btn btn-sc-gold">Save Brand</button>
      <a href="<?= BASE_URL ?>admin/brands/index.php" class="btn btn-sc-outline">Cancel</a>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
