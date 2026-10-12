<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active') WHERE category_id = ?")->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/categories/index.php');
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS product_count FROM categories c ORDER BY c.category_name")->fetchAll();

$pageTitle = 'Categories';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <div class="d-flex justify-content-end mb-3"><a href="<?= BASE_URL ?>admin/categories/add.php" class="btn btn-sc-gold btn-sm">+ Add Category</a></div>
    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Category</th><th>Slug</th><th>Products</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><?= e($c['category_name']) ?></td>
              <td class="small text-secondary"><?= e($c['slug']) ?></td>
              <td><?= (int)$c['product_count'] ?></td>
              <td><span class="badge sc-badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/categories/edit.php?id=<?= $c['category_id'] ?>" class="text-gold small">Edit</a>
                <a href="?toggle=<?= $c['category_id'] ?>" class="text-secondary small">Toggle</a>
                <a href="<?= BASE_URL ?>admin/categories/delete.php?id=<?= $c['category_id'] ?>" class="text-danger small js-confirm" data-confirm="Delete this category?">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
