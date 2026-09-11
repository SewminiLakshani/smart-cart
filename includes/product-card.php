<?php
/**
 * Reusable product card.
 * Expects: $product (row from `products`, plus product_id)
 * Optional: $wishlistIds (array of product_ids already in the logged-in user's wishlist)
 */
$pid = (int)$product['product_id'];
$img = product_main_image($pid);
$price = effective_price($product);
$discount = discount_percent($product);
$inWishlist = isset($wishlistIds) && in_array($pid, $wishlistIds, true);
$stockStatus = $product['stock_status'] ?? 'available';
?>
<div class="sc-product-card">
  <div class="sc-product-media">
    <a href="<?= BASE_URL ?>products/product-details.php?slug=<?= e($product['slug']) ?>">
      <img src="<?= e($img) ?>" alt="<?= e($product['product_name']) ?>" loading="lazy">
    </a>
    <?php if ($discount > 0): ?><span class="sc-badge-discount">-<?= $discount ?>%</span><?php endif; ?>
    <?php if ($stockStatus === 'out_of_stock'): ?>
      <span class="sc-badge-stock badge sc-badge bg-danger">Out of Stock</span>
    <?php elseif ($stockStatus === 'low_stock'): ?>
      <span class="sc-badge-stock badge sc-badge bg-warning">Low Stock</span>
    <?php endif; ?>
    <button class="sc-wishlist-btn js-wishlist-toggle <?= $inWishlist ? 'active' : '' ?>" data-product-id="<?= $pid ?>" title="Add to wishlist">
      <i class="bi bi-heart<?= $inWishlist ? '-fill' : '' ?>"></i>
    </button>
    <button class="btn btn-sc-gold sc-quickview-btn js-quick-view" data-product-id="<?= $pid ?>">Quick View</button>
  </div>
  <div class="sc-product-body">
    <span class="sc-product-brand"><?= e($product['brand_name'] ?? 'SmartCart') ?></span>
    <a href="<?= BASE_URL ?>products/product-details.php?slug=<?= e($product['slug']) ?>" class="sc-product-name" title="<?= e($product['product_name']) ?>"><?= e($product['product_name']) ?></a>
    <div class="sc-product-rating">
      <?php $r = round($product['avg_rating'] ?? 0); for ($i = 1; $i <= 5; $i++): ?>
        <i class="bi bi-star<?= $i <= $r ? '-fill' : '' ?>"></i>
      <?php endfor; ?>
      <span>(<?= (int)($product['review_count'] ?? 0) ?>)</span>
    </div>
    <div class="sc-product-price">
      <span class="sc-price-now"><?= money($price) ?></span>
      <?php if ($discount > 0): ?><span class="sc-price-old"><?= money($product['price']) ?></span><?php endif; ?>
    </div>
    <button class="btn btn-sc-gold btn-sm sc-add-cart-btn js-add-cart" data-product-id="<?= $pid ?>" data-qty="1" <?= $stockStatus === 'out_of_stock' ? 'disabled' : '' ?>>
      <i class="bi bi-bag-plus me-1"></i> <?= $stockStatus === 'out_of_stock' ? 'Out of Stock' : 'Add to Cart' ?>
    </button>
  </div>
</div>
