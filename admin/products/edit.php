<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();
$errors = [];

$productId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, i.quantity FROM products p LEFT JOIN inventory i ON i.product_id = p.product_id WHERE p.product_id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) { set_flash('danger', 'Product not found.'); redirect(BASE_URL . 'admin/products/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['delete_image'])) {
        $imgId = (int)$_POST['delete_image'];
        $img = $pdo->prepare("SELECT * FROM product_images WHERE image_id = ? AND product_id = ?");
        $img->execute([$imgId, $productId]);
        $img = $img->fetch();
        if ($img) {
            $filePath = __DIR__ . '/../../' . $img['image_path'];
            if (strpos($img['image_path'], 'uploads/') === 0 && file_exists($filePath)) @unlink($filePath);
            $pdo->prepare("DELETE FROM product_images WHERE image_id = ?")->execute([$imgId]);
        }
        redirect(BASE_URL . 'admin/products/edit.php?id=' . $productId);
    }

    if (isset($_POST['set_main_image'])) {
        $imgId = (int)$_POST['set_main_image'];
        $pdo->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE image_id = ? AND product_id = ?")->execute([$imgId, $productId]);
        redirect(BASE_URL . 'admin/products/edit.php?id=' . $productId);
    }

    if (isset($_POST['adjust_stock'])) {
        $type = $_POST['adjust_type'] === 'reduce' ? 'reduce' : 'add';
        $amount = max(1, (int)$_POST['adjust_amount']);
        $note = trim($_POST['adjust_note'] ?? '') ?: ($type === 'add' ? 'Manual stock addition' : 'Manual stock reduction');
        adjust_stock($productId, $type === 'add' ? $amount : -$amount, $type === 'add' ? 'add' : 'reduce', $user['user_id'], $note);
        set_flash('success', 'Stock updated successfully.');
        redirect(BASE_URL . 'admin/products/edit.php?id=' . $productId);
    }

    if (isset($_POST['update_product'])) {
        $name = trim($_POST['product_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $brandId = (int)($_POST['brand_id'] ?? 0) ?: null;
        $description = trim($_POST['description'] ?? '');
        $specifications = trim($_POST['specifications'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $discountPrice = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
        $sku = trim($_POST['sku'] ?? '') ?: null;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if (strlen($name) < 2) $errors[] = 'Product name is required.';
        if (!$categoryId) $errors[] = 'Please select a category.';
        if ($price <= 0) $errors[] = 'Please enter a valid price.';

        $newImagePaths = [];
        if (empty($errors) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $i => $name_) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = ['name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i],
                         'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
                try { $p = handle_image_upload($file, 'product'); if ($p) $newImagePaths[] = $p; }
                catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
            }
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE products SET category_id=?, brand_id=?, product_name=?, description=?, specifications=?, price=?, discount_price=?, sku=?, is_featured=?, status=? WHERE product_id=?")
                ->execute([$categoryId, $brandId, $name, $description, $specifications, $price, $discountPrice, $sku, $isFeatured, $status, $productId]);

            foreach ($newImagePaths as $path) {
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?,?,0,99)")->execute([$productId, $path]);
            }
            set_flash('success', 'Product updated successfully.');
            redirect(BASE_URL . 'admin/products/edit.php?id=' . $productId);
        }
    }
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name")->fetchAll();
$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC");
$images->execute([$productId]);
$images = $images->fetchAll();

$pageTitle = 'Edit Product';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <form method="post" enctype="multipart/form-data" class="sc-card">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-8"><label>Product Name</label><input type="text" name="product_name" class="form-control" required value="<?= e($product['product_name']) ?>"></div>
            <div class="col-md-4"><label>SKU</label><input type="text" name="sku" class="form-control" value="<?= e($product['sku']) ?>"></div>
            <div class="col-md-4"><label>Category</label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= $c['category_id'] == $product['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label>Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">No brand</option>
                <?php foreach ($brands as $b): ?><option value="<?= $b['brand_id'] ?>" <?= $b['brand_id'] == $product['brand_id'] ? 'selected' : '' ?>><?= e($b['brand_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label>Status</label>
              <select name="status" class="form-select">
                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
            <div class="col-12"><label>Description</label><textarea name="description" rows="3" class="form-control"><?= e($product['description']) ?></textarea></div>
            <div class="col-12"><label>Specifications</label><textarea name="specifications" rows="2" class="form-control"><?= e($product['specifications']) ?></textarea></div>
            <div class="col-md-6"><label>Price (Rs.)</label><input type="number" step="0.01" name="price" class="form-control" required value="<?= e($product['price']) ?>"></div>
            <div class="col-md-6"><label>Discount Price (Rs.)</label><input type="number" step="0.01" name="discount_price" class="form-control" value="<?= e($product['discount_price']) ?>"></div>
            <div class="col-12"><label>Add More Images</label><input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
            <div class="col-12 form-check"><input type="checkbox" name="is_featured" id="isFeatured" class="form-check-input" <?= $product['is_featured'] ? 'checked' : '' ?>><label class="form-check-label" for="isFeatured">Featured Product</label></div>
            <div class="col-12"><button type="submit" name="update_product" class="btn btn-sc-gold">Update Product</button></div>
          </div>
        </form>

        <div class="sc-card mt-4">
          <h6 class="text-gold mb-3">Product Images</h6>
          <div class="row g-3">
            <?php foreach ($images as $img): ?>
              <div class="col-3 text-center">
                <img src="<?= e(BASE_URL . $img['image_path']) ?>" class="rounded mb-2" style="width:100%;aspect-ratio:1/1;object-fit:cover">
                <div class="d-flex justify-content-center gap-1">
                  <form method="post"><?= csrf_field() ?><input type="hidden" name="set_main_image" value="<?= $img['image_id'] ?>">
                    <button class="btn btn-sc-dark btn-sm" title="Set main" <?= $img['is_main'] ? 'disabled' : '' ?>><i class="bi bi-star<?= $img['is_main'] ? '-fill text-gold' : '' ?>"></i></button></form>
                  <form method="post" onsubmit="return confirm('Delete this image?')"><?= csrf_field() ?><input type="hidden" name="delete_image" value="<?= $img['image_id'] ?>">
                    <button class="btn btn-sc-dark btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Stock Management</h6>
          <p class="mb-3">Current Stock: <strong class="text-gold"><?= (int)$product['quantity'] ?></strong></p>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-2"><select name="adjust_type" class="form-select form-select-sm"><option value="add">Add Stock</option><option value="reduce">Reduce Stock</option></select></div>
            <div class="mb-2"><input type="number" name="adjust_amount" class="form-control form-control-sm" min="1" placeholder="Quantity" required></div>
            <div class="mb-2"><input type="text" name="adjust_note" class="form-control form-control-sm" placeholder="Note (optional)"></div>
            <button type="submit" name="adjust_stock" class="btn btn-sc-gold btn-sm w-100">Update Stock</button>
          </form>
          <a href="<?= BASE_URL ?>admin/inventory/stock-history.php?product_id=<?= $productId ?>" class="d-block text-center text-gold small mt-3">View Stock History &rarr;</a>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
