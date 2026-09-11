<?php
require __DIR__ . '/../includes/auth.php';
header('Content-Type: text/html; charset=utf-8');

$productId = (int)($_GET['product_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("SELECT p.*, i.quantity AS stock_qty, i.stock_status FROM products p
                        LEFT JOIN inventory i ON i.product_id = p.product_id
                        WHERE p.product_id = ? AND p.status='active'");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) { echo '<p class="text-danger p-4">Product not found.</p>'; exit; }

$img = product_main_image($productId);
$price = effective_price($product);
$discount = discount_percent($product);
$user = current_user();
$inWishlist = false;
if ($user) {
    $wid = get_or_create_wishlist($user['user_id']);
    $s = $pdo->prepare("SELECT 1 FROM wishlist_items WHERE wishlist_id=? AND product_id=?");
    $s->execute([$wid, $productId]);
    $inWishlist = (bool)$s->fetchColumn();
}
?>
<div class="row g-4">
  <div class="col-md-5">
    <img src="<?= e($img) ?>" class="img-fluid rounded" alt="<?= e($product['product_name']) ?>">
  </div>
  <div class="col-md-7">
    <h4><?= e($product['product_name']) ?></h4>
    <div class="sc-product-rating mb-2">
      <?php $r = round($product['avg_rating']); for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$r?'-fill':'' ?>"></i><?php endfor; ?>
      <span>(<?= (int)$product['review_count'] ?> reviews)</span>
    </div>
    <div class="mb-3">
      <span class="sc-price-now fs-4"><?= money($price) ?></span>
      <?php if ($discount > 0): ?> <span class="sc-price-old"><?= money($product['price']) ?></span> <span class="badge sc-badge bg-warning text-dark">-<?= $discount ?>%</span><?php endif; ?>
    </div>
    <p class="text-secondary small"><?= e(mb_substr((string)$product['description'], 0, 220)) ?><?= mb_strlen((string)$product['description']) > 220 ? '…' : '' ?></p>
    <p><?= stock_status_badge($product['stock_status'] ?? 'available') ?></p>
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="sc-quantity">
        <button type="button" class="js-qty-step" data-dir="down">-</button>
        <input type="number" class="js-qty-input" data-for="<?= $productId ?>" value="1" min="1" max="<?= (int)$product['stock_qty'] ?>">
        <button type="button" class="js-qty-step" data-dir="up">+</button>
      </div>
      <button class="btn btn-sc-gold js-add-cart" data-product-id="<?= $productId ?>" <?= ($product['stock_qty'] ?? 0) <= 0 ? 'disabled' : '' ?>><i class="bi bi-bag-plus me-1"></i>Add to Cart</button>
      <button class="btn-sc-icon js-wishlist-toggle <?= $inWishlist ? 'active' : '' ?>" data-product-id="<?= $productId ?>"><i class="bi bi-heart<?= $inWishlist ? '-fill' : '' ?>"></i></button>
    </div>
    <a href="<?= BASE_URL ?>products/product-details.php?slug=<?= e($product['slug']) ?>" class="text-gold small">View full details &rarr;</a>
  </div>
</div>
