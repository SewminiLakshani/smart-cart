<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (strlen($name) < 2) $errors[] = 'Category name is required.';
    if (empty($errors)) {
        $slug = slugify($name);
        $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?"); $chk->execute([$slug]);
        if ($chk->fetchColumn() > 0) $slug .= '-' . substr(uniqid(), -4);

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            try { $imagePath = handle_image_upload($_FILES['image'], 'category'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        }
        if (empty($errors)) {
            $pdo->prepare("INSERT INTO categories (category_name, slug, description, image, status) VALUES (?,?,?,?,'active')")
                ->execute([$name, $slug, $description, $imagePath]);
            set_flash('success', 'Category added successfully.');
            redirect(BASE_URL . 'admin/categories/index.php');
        }
    }
}
$pageTitle = 'Add Category';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="sc-card" style="max-width:560px">
      <?= csrf_field() ?>
      <div class="mb-3"><label>Category Name</label><input type="text" name="category_name" class="form-control" required></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
      <div class="mb-3"><label>Category Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
      <button type="submit" class="btn btn-sc-gold">Save Category</button>
      <a href="<?= BASE_URL ?>admin/categories/index.php" class="btn btn-sc-outline">Cancel</a>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
