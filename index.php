<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = db();
$user = current_user();
$wishlistIds = [];
if ($user) {
    $wid = get_or_create_wishlist($user['user_id']);
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist_items WHERE wishlist_id = ?");
    $stmt->execute([$wid]);
    $wishlistIds = array_column($stmt->fetchAll(), 'product_id');
}

$productCols = "p.*, c.category_name, b.brand_name, i.stock_status";
$productJoins = "FROM products p LEFT JOIN categories c ON c.category_id = p.category_id
                 LEFT JOIN brands b ON b.brand_id = p.brand_id
                 LEFT JOIN inventory i ON i.product_id = p.product_id";

$featured = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' AND p.is_featured=1 ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
$newArrivals = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
$bestSellers = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' ORDER BY p.total_sold DESC LIMIT 8")->fetchAll();
$flashSale = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' AND p.is_flash_sale=1 AND p.flash_sale_end >= NOW() ORDER BY p.flash_sale_end ASC LIMIT 4")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();
$reviews = $pdo->query("SELECT r.*, u.full_name, p.product_name FROM reviews r
                         JOIN users u ON u.user_id = r.user_id JOIN products p ON p.product_id = r.product_id
                         WHERE r.status='approved' ORDER BY r.created_at DESC LIMIT 6")->fetchAll();

$recommended = [];
$recentlyViewed = [];
if ($user) {
    $stmt = $pdo->prepare("SELECT product_id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 8");
    $stmt->execute([$user['user_id']]);
    $rvIds = array_column($stmt->fetchAll(), 'product_id');
    $recentlyViewed = fetch_products($rvIds);

    // Simple recommendation: products from categories the user recently viewed / ordered, excluding already viewed
    $stmt = $pdo->prepare("
        SELECT DISTINCT p2.product_id FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        JOIN products p1 ON p1.product_id = oi.product_id
        JOIN products p2 ON p2.category_id = p1.category_id AND p2.product_id != p1.product_id
        WHERE o.user_id = ? AND p2.status='active' LIMIT 8");
    $stmt->execute([$user['user_id']]);
    $recIds = array_column($stmt->fetchAll(), 'product_id');
    if (empty($recIds)) {
        $recommended = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' ORDER BY p.avg_rating DESC LIMIT 8")->fetchAll();
    } else {
        $recommended = fetch_products($recIds);
    }
} else {
    $recommended = $pdo->query("SELECT $productCols $productJoins WHERE p.status='active' ORDER BY p.avg_rating DESC LIMIT 8")->fetchAll();
}

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<!-- ============ HERO ============ -->
<section class="sc-hero">
  <div class="sc-container-fluid w-100">
    <div class="row align-items-center gy-5">
      <div class="col-lg-6">
        <span class="sc-label sc-hero-label"><i class="bi bi-gem me-1"></i> Curated For You</span>
        <h1>Premium Shopping,<br><em>Redefined.</em></h1>
        <p class="lead">Discover a curated collection of products designed to elevate your everyday experience.</p>
        <div class="sc-hero-actions d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-gold">Shop Now</a>
          <a href="<?= BASE_URL ?>products/shop.php?sort=featured" class="btn btn-sc-outline">Explore Collection</a>
        </div>
        <div class="sc-hero-stats">
          <div class="stat"><b>15K+</b><span>Happy Customers</span></div>
          <div class="stat"><b>180+</b><span>Premium Products</span></div>
          <div class="stat"><b>4.8★</b><span>Average Rating</span></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sc-hero-visual position-relative">
          <div class="sc-hero-glow"></div>
          <img src="<?= BASE_URL ?>assets/images/hero.jpg" alt="SmartCart premium product showcase">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURED CATEGORIES ============ -->
<section class="sc-section" id="categories">
  <div class="sc-container-fluid">
    <span class="sc-label">Browse</span>
    <h2 class="sc-section-title">Featured Categories</h2>
    <p class="sc-section-sub">Explore our most-loved collections, curated for every lifestyle.</p>
    <div class="row g-4">
      <?php foreach ($categories as $cat): ?>
        <div class="col-6 col-md-4">
          <a href="<?= BASE_URL ?>products/shop.php?category=<?= e($cat['slug']) ?>" class="sc-category-card d-block text-white text-decoration-none">
            <img src="<?= BASE_URL . e(category_image_path($cat['slug'])) ?>" alt="<?= e($cat['category_name']) ?>">
            <div>
              <small>Shop the edit</small>
              <h5><?= e($cat['category_name']) ?></h5>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="sc-section sc-section-sm bg-sc-charcoal">
  <div class="sc-container-fluid">
    <span class="sc-label">Handpicked</span>
    <h2 class="sc-section-title">Featured Products</h2>
    <p class="sc-section-sub">A selection of our finest products, chosen for their exceptional quality.</p>
    <div class="row g-4">
      <?php foreach ($featured as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FLASH SALE ============ -->
<?php if (!empty($flashSale)): $flashEnd = $flashSale[0]['flash_sale_end']; ?>
<section class="sc-section sc-flash-section js-flash-wrapper">
  <div class="sc-container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
      <div>
        <span class="sc-label"><i class="bi bi-lightning-charge-fill me-1"></i>Limited Time</span>
        <h2 class="sc-section-title mb-0">Flash Sale</h2>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="sc-countdown js-countdown" data-end="<?= e($flashEnd) ?>">
          <div class="box"><b class="cd-d">00</b><span>Days</span></div>
          <div class="box"><b class="cd-h">00</b><span>Hrs</span></div>
          <div class="box"><b class="cd-m">00</b><span>Min</span></div>
          <div class="box"><b class="cd-s">00</b><span>Sec</span></div>
        </div>
        <a href="<?= BASE_URL ?>products/shop.php?deals=1" class="btn btn-sc-gold d-none d-md-inline-block">Shop Deals</a>
      </div>
    </div>
    <div class="row g-4">
      <?php foreach ($flashSale as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ NEW ARRIVALS ============ -->
<section class="sc-section">
  <div class="sc-container-fluid">
    <span class="sc-label">Just In</span>
    <h2 class="sc-section-title">New Arrivals</h2>
    <p class="sc-section-sub">The latest additions to our premium collection.</p>
    <div class="row g-4">
      <?php foreach ($newArrivals as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ BEST SELLERS ============ -->
<section class="sc-section sc-section-sm bg-sc-charcoal">
  <div class="sc-container-fluid">
    <span class="sc-label">Customer Favorites</span>
    <h2 class="sc-section-title">Best Sellers</h2>
    <p class="sc-section-sub">Loved and repeatedly purchased by the SmartCart community.</p>
    <div class="row g-4">
      <?php foreach ($bestSellers as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ RECOMMENDED PRODUCTS ============ -->
<?php if (!empty($recommended)): ?>
<section class="sc-section">
  <div class="sc-container-fluid">
    <span class="sc-label">For You</span>
    <h2 class="sc-section-title">Recommended Products</h2>
    <p class="sc-section-sub">Suggestions based on <?= $user ? 'your shopping activity' : 'top-rated products' ?>.</p>
    <div class="row g-4">
      <?php foreach ($recommended as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ RECENTLY VIEWED ============ -->
<?php if (!empty($recentlyViewed)): ?>
<section class="sc-section sc-section-sm bg-sc-charcoal">
  <div class="sc-container-fluid">
    <span class="sc-label">Continue Browsing</span>
    <h2 class="sc-section-title">Recently Viewed Products</h2>
    <div class="row g-4">
      <?php foreach ($recentlyViewed as $product): ?>
        <div class="col-6 col-md-4 col-lg-3"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ CUSTOMER REVIEWS ============ -->
<section class="sc-section">
  <div class="sc-container-fluid">
    <span class="sc-label">Testimonials</span>
    <h2 class="sc-section-title">What Our Customers Say</h2>
    <div class="row g-4">
      <?php foreach ($reviews as $rev): ?>
        <div class="col-md-6 col-lg-4">
          <div class="sc-review-card">
            <div class="stars">
              <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : '' ?>"></i><?php endfor; ?>
            </div>
            <p>&ldquo;<?= e($rev['review_text']) ?>&rdquo;</p>
            <div class="d-flex align-items-center gap-2 mt-3">
              <div class="sc-avatar-circle"><?= e(strtoupper(substr($rev['full_name'], 0, 1))) ?></div>
              <div>
                <div class="fw-semibold small"><?= e($rev['full_name']) ?></div>
                <div class="text-secondary small">on <?= e($rev['product_name']) ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="sc-newsletter">
  <div class="sc-container-fluid text-center">
    <span class="sc-label">Stay In The Loop</span>
    <h2 class="sc-section-title">Join Our Newsletter</h2>
    <p class="sc-section-sub">Be the first to know about new arrivals, flash sales and exclusive offers.</p>
    <form id="newsletterForm" class="d-flex justify-content-center mx-auto" style="max-width:460px" novalidate>
      <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
      <button type="submit" class="btn btn-sc-gold">Subscribe</button>
    </form>
    <div id="newsletterMsg" class="small mt-2"></div>
  </div>
</section>

<script>
document.getElementById('newsletterForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const email = this.email.value;
  const resp = await scAjaxPost('ajax/newsletter_subscribe.php', { email, csrf_token: document.querySelector('meta[name="csrf-token"]').content });
  const msg = document.getElementById('newsletterMsg');
  msg.textContent = resp.message;
  msg.className = 'small mt-2 ' + (resp.success ? 'text-success' : 'text-danger');
  if (resp.success) this.reset();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
