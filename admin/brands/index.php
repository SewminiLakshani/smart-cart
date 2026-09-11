<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE brands SET status = IF(status='active','inactive','active') WHERE brand_id = ?")->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . 'admin/brands/index.php');
}

$brands = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.brand_id) AS product_count FROM brands b ORDER BY b.brand_name")->fetchAll();
$pageTitle = 'Brands';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <div class="d-flex justify-content-end mb-3"><a href="<?= BASE_URL ?>admin/brands/add.php" class="btn btn-sc-gold btn-sm">+ Add Brand</a></div>
    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Brand</th><th>Products</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($brands as $b): ?>
            <tr>
              <td><?= e($b['brand_name']) ?></td>
              <td><?= (int)$b['product_count'] ?></td>
              <td><span class="badge sc-badge bg-<?= $b['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($b['status']) ?></span></td>
              <td class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/brands/edit.php?id=<?= $b['brand_id'] ?>" class="text-gold small">Edit</a>
                <a href="?toggle=<?= $b['brand_id'] ?>" class="text-secondary small">Toggle</a>
                <a href="<?= BASE_URL ?>admin/brands/delete.php?id=<?= $b['brand_id'] ?>" class="text-danger small js-confirm" data-confirm="Delete this brand?">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
