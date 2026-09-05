<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();
$errors = [];

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE status='active' ORDER BY brand_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['product_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brandId = (int)($_POST['brand_id'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '');
    $specifications = trim($_POST['specifications'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
    $sku = trim($_POST['sku'] ?? '') ?: null;
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if (strlen($name) < 2) $errors[] = 'Product name is required.';
    if (!$categoryId) $errors[] = 'Please select a category.';
    if ($price <= 0) $errors[] = 'Please enter a valid price.';
    if ($discountPrice !== null && $discountPrice >= $price) $errors[] = 'Discount price must be lower than the regular price.';

    $imagePaths = [];
    if (empty($errors) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $name_) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i],
            ];
            try {
                $path = handle_image_upload($file, 'product');
                if ($path) $imagePaths[] = $path;
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $slug = slugify($name) . '-' . substr(uniqid(), -5);
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO products (category_id, brand_id, product_name, slug, description, specifications, price, discount_price, sku, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$categoryId, $brandId, $name, $slug, $description, $specifications, $price, $discountPrice, $sku, $isFeatured, $status]);
            $productId = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO inventory (product_id, quantity, low_stock_threshold, stock_status) VALUES (?,?,?,?)")
                ->execute([$productId, $stock, LOW_STOCK_THRESHOLD, $stock <= 0 ? 'out_of_stock' : ($stock <= LOW_STOCK_THRESHOLD ? 'low_stock' : 'available')]);

            $pdo->prepare("INSERT INTO stock_history (product_id, user_id, previous_quantity, change_quantity, new_quantity, change_type, note) VALUES (?,?,0,?,?,?,?)")
                ->execute([$productId, $user['user_id'], $stock, $stock, 'add', 'Initial stock on product creation']);

            if (empty($imagePaths)) $imagePaths[] = 'assets/images/products/placeholder.svg';
            foreach ($imagePaths as $i => $path) {
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?,?,?,?)")
                    ->execute([$productId, $path, $i === 0 ? 1 : 0, $i]);
            }

            $pdo->commit();
            set_flash('success', 'Product added successfully.');
            redirect(BASE_URL . 'admin/products/index.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            $errors[] = 'Could not save the product. Please try again.';
        }
    }
}

$pageTitle = 'Add Product';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="sc-card needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-8"><label>Product Name</label><input type="text" name="product_name" class="form-control" required value="<?= e($_POST['product_name'] ?? '') ?>"></div>
        <div class="col-md-4"><label>SKU</label><input type="text" name="sku" class="form-control" value="<?= e($_POST['sku'] ?? '') ?>"></div>

        <div class="col-md-4"><label>Category</label>
          <select name="category_id" class="form-select" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $c): ?><option value="<?= $c['category_id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4"><label>Brand</label>
          <select name="brand_id" class="form-select">
            <option value="">No brand</option>
            <?php foreach ($brands as $b): ?><option value="<?= $b['brand_id'] ?>"><?= e($b['brand_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4"><label>Status</label>
          <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>

        <div class="col-12"><label>Description</label><textarea name="description" rows="3" class="form-control"><?= e($_POST['description'] ?? '') ?></textarea></div>
        <div class="col-12"><label>Specifications <span class="text-secondary">(separate with | )</span></label><textarea name="specifications" rows="2" class="form-control" placeholder="e.g. Bluetooth 5.3 | 40h battery | ANC"><?= e($_POST['specifications'] ?? '') ?></textarea></div>

        <div class="col-md-4"><label>Price (Rs.)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
        <div class="col-md-4"><label>Discount Price (Rs.)</label><input type="number" step="0.01" name="discount_price" class="form-control"></div>
        <div class="col-md-4"><label>Initial Stock Quantity</label><input type="number" name="stock" class="form-control" value="0" min="0"></div>

        <div class="col-12"><label>Product Images (multiple allowed)</label><input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>

        <div class="col-12 form-check"><input type="checkbox" name="is_featured" id="isFeatured" class="form-check-input"><label class="form-check-label" for="isFeatured">Mark as Featured Product</label></div>

        <div class="col-12"><button type="submit" class="btn btn-sc-gold">Save Product</button> <a href="<?= BASE_URL ?>admin/products/index.php" class="btn btn-sc-outline">Cancel</a></div>
      </div>
    </form>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
