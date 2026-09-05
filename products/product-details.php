<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = db();
$user = current_user();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT p.*, c.category_name, c.slug AS category_slug, b.brand_name, i.quantity AS stock_qty, i.stock_status
                        FROM products p LEFT JOIN categories c ON c.category_id = p.category_id
                        LEFT JOIN brands b ON b.brand_id = p.brand_id
                        LEFT JOIN inventory i ON i.product_id = p.product_id
                        WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    echo '<div class="sc-container-fluid py-5 text-center"><i class="bi bi-exclamation-circle fs-1 text-gold mb-3 d-block"></i><h3>Product Not Found</h3><p class="text-secondary">The product you are looking for does not exist or is no longer available.</p><a href="' . BASE_URL . 'products/shop.php" class="btn btn-sc-gold">Back to Shop</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$productId = (int)$product['product_id'];

// Track recently viewed for logged-in customers
if ($user && $user['role_name'] === 'customer') {
    track_recently_viewed($user['user_id'], $productId);
}

$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC");
$images->execute([$productId]);
$images = $images->fetchAll();
if (empty($images)) $images = [['image_path' => 'assets/images/products/placeholder.svg']];

$reviewsStmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.user_id = r.user_id
                               WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
$reviewsStmt->execute([$productId]);
$reviews = $reviewsStmt->fetchAll();

$wishlistIds = [];
if ($user) {
    $wid = get_or_create_wishlist($user['user_id']);
    $w = $pdo->prepare("SELECT product_id FROM wishlist_items WHERE wishlist_id = ?");
    $w->execute([$wid]);
    $wishlistIds = array_column($w->fetchAll(), 'product_id');
}

// Related products (same category)
$related = $pdo->prepare("SELECT p.*, c.category_name, b.brand_name, i.stock_status FROM products p
                           LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN brands b ON b.brand_id=p.brand_id
                           LEFT JOIN inventory i ON i.product_id=p.product_id
                           WHERE p.category_id = ? AND p.product_id != ? AND p.status='active' LIMIT 4");
$related->execute([$product['category_id'], $productId]);
$related = $related->fetchAll();

// Customers also bought (co-purchase)
$alsoBought = $pdo->prepare("
    SELECT DISTINCT p2.*, c.category_name, b.brand_name, i.stock_status FROM order_items oi1
    JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi2.product_id != oi1.product_id
    JOIN products p2 ON p2.product_id = oi2.product_id
    LEFT JOIN categories c ON c.category_id=p2.category_id LEFT JOIN brands b ON b.brand_id=p2.brand_id
    LEFT JOIN inventory i ON i.product_id=p2.product_id
    WHERE oi1.product_id = ? AND p2.status='active' LIMIT 4");
$alsoBought->execute([$productId]);
$alsoBought = $alsoBought->fetchAll();

// Can the current user review? must have a delivered order containing this product, and not already reviewed
$canReview = false;
if ($user && $user['role_name'] === 'customer') {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
                           WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'delivered'");
    $chk->execute([$user['user_id'], $productId]);
    $purchased = (int)$chk->fetchColumn() > 0;
    $chk2 = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?");
    $chk2->execute([$user['user_id'], $productId]);
    $alreadyReviewed = (int)$chk2->fetchColumn() > 0;
    $canReview = $purchased && !$alreadyReviewed;
}

$reviewMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    verify_csrf();
    require_login();
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
    $text = trim($_POST['review_text'] ?? '');
    if ($canReview && $rating >= 1 && $text !== '') {
        $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text, status) VALUES (?,?,?,?,'approved')")
            ->execute([$productId, $user['user_id'], $rating, $text]);
        // recalc avg rating
        $agg = $pdo->prepare("SELECT AVG(rating) avg_r, COUNT(*) cnt FROM reviews WHERE product_id = ? AND status='approved'");
        $agg->execute([$productId]);
        $a = $agg->fetch();
        $pdo->prepare("UPDATE products SET avg_rating = ?, review_count = ? WHERE product_id = ?")->execute([round($a['avg_r'], 2), $a['cnt'], $productId]);
        notify_admins_and_staff('New Review', $user['full_name'] . ' reviewed ' . $product['product_name'], 'new_review', '/admin/reviews/index.php');
        set_flash('success', 'Thank you! Your review has been posted.');
        redirect(BASE_URL . 'products/product-details.php?slug=' . $slug . '#reviews');
    } else {
        $reviewMsg = 'Please provide a rating and a written review.';
    }
}

$price = effective_price($product);
$discount = discount_percent($product);

$pageTitle = $product['product_name'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-container-fluid pt-4">
  <nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>products/shop.php">Shop</a></li>
    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>products/shop.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($product['product_name']) ?></li>
  </ol></nav>
</div>

<section class="sc-container-fluid pb-5">
  <div class="row g-5">
    <!-- Gallery -->
    <div class="col-lg-6">
      <div class="sc-card p-2 mb-3">
        <img id="mainProductImage" src="<?= e(BASE_URL . $images[0]['image_path']) ?>" class="w-100 rounded" style="aspect-ratio:1/1;object-fit:cover" alt="<?= e($product['product_name']) ?>">
      </div>
      <?php if (count($images) > 1): ?>
      <div class="d-flex gap-2">
        <?php foreach ($images as $img): ?>
          <img src="<?= e(BASE_URL . $img['image_path']) ?>" class="rounded js-thumb" style="width:70px;height:70px;object-fit:cover;cursor:pointer;border:1px solid var(--sc-border)"
               onclick="document.getElementById('mainProductImage').src=this.src">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="col-lg-6">
      <span class="sc-label"><?= e($product['brand_name'] ?? 'SmartCart') ?></span>
      <h2 class="mt-2"><?= e($product['product_name']) ?></h2>
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="sc-product-rating">
          <?php $r = round($product['avg_rating']); for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= $r ? '-fill' : '' ?>"></i><?php endfor; ?>
        </div>
        <span class="text-secondary small"><?= (int)$product['review_count'] ?> reviews · <?= (int)$product['total_sold'] ?> sold</span>
      </div>

      <div class="d-flex align-items-baseline gap-3 mb-3">
        <span class="sc-price-now fs-2"><?= money($price) ?></span>
        <?php if ($discount > 0): ?>
          <span class="sc-price-old fs-6"><?= money($product['price']) ?></span>
          <span class="badge sc-badge bg-warning text-dark">-<?= $discount ?>% OFF</span>
        <?php endif; ?>
      </div>

      <p class="mb-2"><?= stock_status_badge($product['stock_status'] ?? 'available') ?>
        <?php if (($product['stock_status'] ?? '') !== 'out_of_stock'): ?>
          <span class="text-secondary small ms-2"><?= (int)$product['stock_qty'] ?> units available</span>
        <?php endif; ?>
      </p>

      <p class="text-secondary"><?= nl2br(e($product['description'])) ?></p>

      <?php if (($product['stock_qty'] ?? 0) > 0): ?>
      <div class="d-flex align-items-center gap-3 my-4">
        <div class="sc-quantity">
          <button type="button" class="js-qty-step" data-dir="down">-</button>
          <input type="number" class="js-qty-input" data-for="<?= $productId ?>" value="1" min="1" max="<?= (int)$product['stock_qty'] ?>">
          <button type="button" class="js-qty-step" data-dir="up">+</button>
        </div>
        <button class="btn btn-sc-gold js-add-cart flex-grow-1" data-product-id="<?= $productId ?>"><i class="bi bi-bag-plus me-1"></i>Add to Cart</button>
        <button class="btn-sc-icon js-wishlist-toggle <?= in_array($productId, $wishlistIds) ? 'active' : '' ?>" data-product-id="<?= $productId ?>">
          <i class="bi bi-heart<?= in_array($productId, $wishlistIds) ? '-fill' : '' ?>"></i>
        </button>
      </div>
      <?php else: ?>
        <div class="alert alert-warning mt-3">This product is currently out of stock. Add it to your wishlist to be notified.</div>
        <button class="btn-sc-icon js-wishlist-toggle <?= in_array($productId, $wishlistIds) ? 'active' : '' ?>" data-product-id="<?= $productId ?>">
          <i class="bi bi-heart<?= in_array($productId, $wishlistIds) ? '-fill' : '' ?>"></i> Add to Wishlist
        </button>
      <?php endif; ?>

      <?php if (!empty($product['specifications'])): ?>
      <div class="sc-card mt-4">
        <h6 class="text-gold mb-3">Specifications</h6>
        <ul class="list-unstyled small text-secondary mb-0">
          <?php foreach (explode('|', $product['specifications']) as $spec): ?>
            <li class="mb-2"><i class="bi bi-check2 text-gold me-2"></i><?= e(trim($spec)) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reviews -->
  <div class="row mt-5" id="reviews">
    <div class="col-lg-8">
      <h4 class="mb-4">Customer Reviews (<?= count($reviews) ?>)</h4>
      <?php if (empty($reviews)): ?>
        <p class="text-secondary">No reviews yet. Be the first to review this product!</p>
      <?php endif; ?>
      <?php foreach ($reviews as $rev): ?>
        <div class="sc-card mb-3">
          <div class="d-flex justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <div class="sc-avatar-circle"><?= e(strtoupper(substr($rev['full_name'], 0, 1))) ?></div>
              <div>
                <div class="fw-semibold small"><?= e($rev['full_name']) ?></div>
                <div class="text-secondary small"><?= friendly_date($rev['created_at'], 'M d, Y') ?></div>
              </div>
            </div>
            <div class="text-gold small">
              <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : '' ?>"></i><?php endfor; ?>
            </div>
          </div>
          <p class="mt-3 mb-0 text-secondary"><?= nl2br(e($rev['review_text'])) ?></p>
        </div>
      <?php endforeach; ?>

      <?php if ($canReview): ?>
        <div class="sc-card mt-4">
          <h6 class="text-gold mb-3">Write a Review</h6>
          <?php if ($reviewMsg): ?><div class="alert alert-danger small"><?= e($reviewMsg) ?></div><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="rating" value="0">
            <div class="js-star-input fs-4 text-gold mb-3">
              <?php for ($i = 0; $i < 5; $i++): ?><i class="bi bi-star" style="cursor:pointer"></i><?php endfor; ?>
            </div>
            <textarea name="review_text" class="form-control mb-3" rows="3" placeholder="Share your experience with this product..." required></textarea>
            <button type="submit" name="submit_review" class="btn btn-sc-gold">Submit Review</button>
          </form>
        </div>
      <?php elseif ($user && $user['role_name'] === 'customer'): ?>
        <p class="text-secondary small mt-3"><i class="bi bi-info-circle me-1"></i>Only customers who have received this product can leave a review.</p>
      <?php endif; ?>
    </div>
    <div class="col-lg-4">
      <div class="sc-card text-center">
        <h1 class="text-gold mb-0"><?= number_format($product['avg_rating'], 1) ?></h1>
        <div class="text-gold mb-2">
          <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= round($product['avg_rating']) ? '-fill' : '' ?>"></i><?php endfor; ?>
        </div>
        <p class="text-secondary small">Based on <?= (int)$product['review_count'] ?> reviews</p>
      </div>
    </div>
  </div>

  <!-- Related / Also Bought -->
  <?php if (!empty($related)): ?>
  <div class="mt-5">
    <h4 class="mb-4">You May Also Like</h4>
    <div class="row g-4">
      <?php foreach ($related as $product): ?>
        <div class="col-6 col-md-3"><?php include __DIR__ . '/../includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($alsoBought)): ?>
  <div class="mt-5">
    <h4 class="mb-4">Customers Also Bought</h4>
    <div class="row g-4">
      <?php foreach ($alsoBought as $product): ?>
        <div class="col-6 col-md-3"><?php include __DIR__ . '/../includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
