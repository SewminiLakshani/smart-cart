<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();

if (isset($_GET['toggle'])) {
    $pid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE products SET status = IF(status='active','inactive','active') WHERE product_id = ?")->execute([$pid]);
    set_flash('success', 'Product status updated.');
    redirect(BASE_URL . 'admin/products/index.php');
}

$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1=1']; $params = [];
if ($q !== '') { $where[] = "p.product_name LIKE ?"; $params[] = "%$q%"; }
if ($categoryId) { $where[] = "p.category_id = ?"; $params[] = $categoryId; }
$whereSql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$total->execute($params);
$total = (int)$total->fetchColumn();
$pg = paginate($total, $page, $perPage);

$stmt = $pdo->prepare("SELECT p.*, c.category_name, b.brand_name, i.quantity, i.stock_status
                        FROM products p LEFT JOIN categories c ON c.category_id=p.category_id
                        LEFT JOIN brands b ON b.brand_id=p.brand_id LEFT JOIN inventory i ON i.product_id=p.product_id
                        WHERE $whereSql ORDER BY p.created_at DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

$pageTitle = 'Products';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <form method="get" class="d-flex gap-2">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search products..." value="<?= e($q) ?>" style="width:220px">
        <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= $categoryId === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-sc-dark btn-sm">Filter</button>
      </form>
      <a href="<?= BASE_URL ?>admin/products/add.php" class="btn btn-sc-gold btn-sm">+ Add Product</a>
    </div>

    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Product</th><th>Category</th><th>Brand</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= e(product_main_image($p['product_id'])) ?>" style="width:42px;height:42px;object-fit:cover;border-radius:6px">
                  <span class="small"><?= e($p['product_name']) ?></span>
                </div>
              </td>
              <td class="small"><?= e($p['category_name']) ?></td>
              <td class="small"><?= e($p['brand_name'] ?? '-') ?></td>
              <td class="small"><?= money($p['discount_price'] ?? $p['price']) ?></td>
              <td><?= stock_status_badge($p['stock_status'] ?? 'available') ?> <span class="small text-secondary">(<?= (int)$p['quantity'] ?>)</span></td>
              <td><span class="badge sc-badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
              <td class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/products/edit.php?id=<?= $p['product_id'] ?>" class="text-gold small">Edit</a>
                <a href="?toggle=<?= $p['product_id'] ?>" class="text-secondary small">Toggle</a>
                <a href="<?= BASE_URL ?>admin/products/delete.php?id=<?= $p['product_id'] ?>" class="text-danger small js-confirm" data-confirm="Delete this product permanently?">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?><tr><td colspan="7" class="text-center text-secondary py-4">No products found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3"><?= render_pagination($pg['currentPage'], $pg['totalPages']) ?></div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
