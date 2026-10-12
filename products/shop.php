<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = db();
$user = current_user();
$wishlistIds = [];
if ($user) {
    $wid = get_or_create_wishlist($user['user_id']);
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist_items WHERE wishlist_id = ?");
    $stmt->execute([$wid]);
    $wishlistIds = array_column($stmt->fetchAll(), 'product_id');
}

/* ---- Read filters ---- */
$q = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$brandIds = array_filter(array_map('intval', (array)($_GET['brand'] ?? [])));
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$minRating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$availability = $_GET['availability'] ?? '';
$deals = isset($_GET['deals']);
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

/* ---- Build query ---- */
$where = ["p.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = "(p.product_name LIKE ? OR p.description LIKE ? OR b.brand_name LIKE ? OR c.category_name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}
if (!empty($brandIds)) {
    $where[] = "p.brand_id IN (" . implode(',', array_fill(0, count($brandIds), '?')) . ")";
    foreach ($brandIds as $b) $params[] = $b;
}
if ($minPrice !== null) { $where[] = "p.price >= ?"; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = "p.price <= ?"; $params[] = $maxPrice; }
if ($minRating > 0) { $where[] = "p.avg_rating >= ?"; $params[] = $minRating; }
if ($availability === 'in_stock') { $where[] = "i.stock_status IN ('available','low_stock')"; }
if ($availability === 'out_of_stock') { $where[] = "i.stock_status = 'out_of_stock'"; }
if ($deals) { $where[] = "(p.discount_price IS NOT NULL OR p.is_flash_sale = 1)"; }

$whereSql = implode(' AND ', $where);

$orderSql = match ($sort) {
    'price_low' => 'ORDER BY COALESCE(p.discount_price, p.price) ASC',
    'price_high' => 'ORDER BY COALESCE(p.discount_price, p.price) DESC',
    'rating' => 'ORDER BY p.avg_rating DESC',
    'bestselling' => 'ORDER BY p.total_sold DESC',
    'featured' => 'ORDER BY p.is_featured DESC, p.created_at DESC',
    default => 'ORDER BY p.created_at DESC',
};

$baseFrom = "FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN brands b ON b.brand_id = p.brand_id
             LEFT JOIN inventory i ON i.product_id = p.product_id
             WHERE $whereSql";

$countStmt = $pdo->prepare("SELECT COUNT(*) $baseFrom");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();

$pg = paginate($totalItems, $page, $perPage);

$listStmt = $pdo->prepare("SELECT p.*, c.category_name, b.brand_name, i.stock_status
                            $baseFrom $orderSql LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$listStmt->execute($params);
$products = $listStmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE status='active' ORDER BY brand_name")->fetchAll();

/* Preserve query string (minus page) for pagination links */
$qsParams = $_GET;
unset($qsParams['page']);
$baseQuery = $qsParams ? '&' . http_build_query($qsParams) : '';

$pageTitle = 'Shop';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-container-fluid pt-4">
  <nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Home</a></li>
    <li class="breadcrumb-item active">Shop</li>
  </ol></nav>
</div>

<section class="sc-container-fluid pb-5">
  <div class="row g-4">
    <!-- Sidebar Filters -->
    <div class="col-lg-3">
      <form method="get" id="filterForm" class="sc-card">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <h6 class="text-gold mb-3">Filters</h6>

        <label class="d-block mb-2">Category</label>
        <select name="category" class="form-select form-select-sm mb-3" onchange="document.getElementById('filterForm').submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label class="d-block mb-2">Brand</label>
        <div class="mb-3" style="max-height:160px; overflow-y:auto;">
          <?php foreach ($brands as $brand): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="brand[]" value="<?= $brand['brand_id'] ?>" id="brand<?= $brand['brand_id'] ?>"
                <?= in_array($brand['brand_id'], $brandIds) ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
              <label class="form-check-label small" for="brand<?= $brand['brand_id'] ?>"><?= e($brand['brand_name']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <label class="d-block mb-2">Price Range (Rs.)</label>
        <div class="d-flex gap-2 mb-3">
          <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= e($minPrice ?? '') ?>">
          <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= e($maxPrice ?? '') ?>">
        </div>

        <label class="d-block mb-2">Minimum Rating</label>
        <select name="rating" class="form-select form-select-sm mb-3" onchange="document.getElementById('filterForm').submit()">
          <option value="0">Any Rating</option>
          <?php for ($i = 4; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= $minRating === $i ? 'selected' : '' ?>><?= $i ?>★ & up</option>
          <?php endfor; ?>
        </select>

        <label class="d-block mb-2">Availability</label>
        <select name="availability" class="form-select form-select-sm mb-3" onchange="document.getElementById('filterForm').submit()">
          <option value="">All</option>
          <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
          <option value="out_of_stock" <?= $availability === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
        </select>

        <button class="btn btn-sc-gold btn-sm w-100 mb-2" type="submit">Apply Filters</button>
        <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-outline btn-sm w-100">Clear All</a>
      </form>
    </div>

    <!-- Product Grid -->
    <div class="col-lg-9">
      <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <div>
          <?php if ($q !== ''): ?>
            <p class="mb-0">Search results for "<strong><?= e($q) ?></strong>" — <?= $totalItems ?> product<?= $totalItems === 1 ? '' : 's' ?> found</p>
          <?php else: ?>
            <p class="mb-0 text-secondary"><?= $totalItems ?> product<?= $totalItems === 1 ? '' : 's' ?> found</p>
          <?php endif; ?>
        </div>
        <form method="get" id="sortForm" class="d-flex align-items-center gap-2">
          <?php foreach ($_GET as $k => $v) { if ($k !== 'sort' && $k !== 'page' && !is_array($v)) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; } ?>
          <label class="mb-0 small text-secondary">Sort:</label>
          <select name="sort" class="form-select form-select-sm" style="width:180px" onchange="document.getElementById('sortForm').submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
            <option value="bestselling" <?= $sort === 'bestselling' ? 'selected' : '' ?>>Best Selling</option>
          </select>
        </form>
      </div>

      <?php if (empty($products)): ?>
        <div class="sc-card text-center py-5">
          <i class="bi bi-search fs-1 text-gold mb-3 d-block"></i>
          <h5>No products found</h5>
          <p class="text-secondary">Try adjusting your search or filters.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($products as $product): ?>
            <div class="col-6 col-md-4"><?php include __DIR__ . '/../includes/product-card.php'; ?></div>
          <?php endforeach; ?>
        </div>
        <div class="mt-5"><?= render_pagination($pg['currentPage'], $pg['totalPages'], $baseQuery) ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
