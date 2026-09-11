<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$errors = [];
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?"); $stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) { set_flash('danger', 'Category not found.'); redirect(BASE_URL . 'admin/categories/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    if (strlen($name) < 2) $errors[] = 'Category name is required.';
    $imagePath = $category['image'];
    if (empty($errors) && !empty($_FILES['image']['name'])) {
        try { $imagePath = handle_image_upload($_FILES['image'], 'category') ?? $imagePath; } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (empty($errors)) {
        $pdo->prepare("UPDATE categories SET category_name=?, description=?, image=?, status=? WHERE category_id=?")
            ->execute([$name, $description, $imagePath, $status, $id]);
        set_flash('success', 'Category updated.');
        redirect(BASE_URL . 'admin/categories/index.php');
    }
}
$pageTitle = 'Edit Category';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="sc-card" style="max-width:560px">
      <?= csrf_field() ?>
      <div class="mb-3"><label>Category Name</label><input type="text" name="category_name" class="form-control" required value="<?= e($category['category_name']) ?>"></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= e($category['description']) ?></textarea></div>
      <div class="mb-3"><label>Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $category['status']==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $category['status']==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="mb-3"><label>Replace Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
      <button type="submit" class="btn btn-sc-gold">Update Category</button>
      <a href="<?= BASE_URL ?>admin/categories/index.php" class="btn btn-sc-outline">Cancel</a>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
