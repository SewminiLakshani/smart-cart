<?php
require __DIR__ . '/../includes/functions.php';
header('Content-Type: text/html; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { exit; }

$stmt = db()->prepare("SELECT p.product_id, p.product_name, p.slug, p.price, p.discount_price, i.stock_status
                        FROM products p LEFT JOIN inventory i ON i.product_id = p.product_id
                        WHERE p.status='active' AND (p.product_name LIKE ? OR p.description LIKE ?)
                        ORDER BY p.product_name ASC LIMIT 6");
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
$results = $stmt->fetchAll();
?>
<div class="sc-card p-2" style="max-height:360px; overflow-y:auto;">
<?php if (empty($results)): ?>
  <p class="text-secondary small mb-0 p-2">No products found for "<?= e($q) ?>".</p>
<?php else: foreach ($results as $r): $price = effective_price($r); ?>
  <a href="<?= BASE_URL ?>products/product-details.php?slug=<?= e($r['slug']) ?>" class="d-flex align-items-center justify-content-between text-decoration-none p-2 rounded" style="color:var(--sc-white); transition:.2s" onmouseover="this.style.background='rgba(212,175,106,.08)'" onmouseout="this.style.background='transparent'">
    <span class="small"><?= e($r['product_name']) ?></span>
    <strong class="text-gold small"><?= money($price) ?></strong>
  </a>
<?php endforeach; ?>
  <a href="<?= BASE_URL ?>products/shop.php?q=<?= urlencode($q) ?>" class="d-block text-center text-gold small mt-1 p-2">See all results &rarr;</a>
<?php endif; ?>
</div>
