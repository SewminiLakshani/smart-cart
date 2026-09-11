<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();

$wid = get_or_create_wishlist($user['user_id']);
$stmt = $pdo->prepare("SELECT p.*, c.category_name, b.brand_name, i.stock_status, wi.wishlist_item_id
                        FROM wishlist_items wi JOIN products p ON p.product_id = wi.product_id
                        LEFT JOIN categories c ON c.category_id = p.category_id LEFT JOIN brands b ON b.brand_id = p.brand_id
                        LEFT JOIN inventory i ON i.product_id = p.product_id
                        WHERE wi.wishlist_id = ? ORDER BY wi.added_at DESC");
$stmt->execute([$wid]);
$items = $stmt->fetchAll();
$wishlistIds = array_column($items, 'product_id');

$pageTitle = 'My Wishlist';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <h3 class="mb-4">My Wishlist (<?= count($items) ?>)</h3>
    <?php if (empty($items)): ?>
      <div class="sc-card text-center py-5">
        <i class="bi bi-heart fs-1 text-gold mb-3 d-block"></i>
        <h5>Your wishlist is empty</h5>
        <p class="text-secondary">Save products you love to shop them later.</p>
        <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-gold">Browse Products</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $product): ?>
          <div class="col-6 col-md-4 col-lg-3 js-wishlist-item">
            <?php include __DIR__ . '/../includes/product-card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
<script>
document.querySelectorAll('.js-wishlist-toggle').forEach(b => b.dataset.removeOnToggle = '1');
</script>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
