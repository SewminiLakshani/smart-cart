<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$totals = compute_cart_totals($user['user_id']);

$pageTitle = 'Shopping Cart';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-container-fluid py-5">
  <h3 class="mb-4"><i class="bi bi-bag me-2"></i>Shopping Cart</h3>

  <?php if (empty($totals['items'])): ?>
    <div class="sc-card text-center py-5">
      <i class="bi bi-bag-x fs-1 text-gold mb-3 d-block"></i>
      <h5>Your cart is empty</h5>
      <p class="text-secondary">Looks like you haven't added anything yet.</p>
      <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-gold">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="sc-table-wrap">
        <table class="table align-middle mb-0">
          <thead class="sc-thead"><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($totals['items'] as $item): ?>
              <tr class="js-cart-row" data-item-id="<?= $item['cart_item_id'] ?>">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <img src="<?= e(product_main_image($item['product_id'])) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px">
                    <div>
                      <a href="<?= BASE_URL ?>products/product-details.php?slug=<?= e($item['slug']) ?>" class="text-white small fw-semibold"><?= e($item['product_name']) ?></a>
                      <?php if ((int)$item['stock_qty'] < $item['quantity']): ?><div class="text-danger small">Only <?= $item['stock_qty'] ?> left in stock</div><?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= money($item['unit_price']) ?></td>
                <td>
                  <div class="sc-quantity">
                    <button type="button" class="js-qty-step" data-dir="down">-</button>
                    <input type="number" class="js-cart-qty" data-item-id="<?= $item['cart_item_id'] ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= (int)$item['stock_qty'] ?>">
                    <button type="button" class="js-qty-step" data-dir="up">+</button>
                  </div>
                </td>
                <td class="js-cart-subtotal" data-item-id="<?= $item['cart_item_id'] ?>"><?= money($item['line_total']) ?></td>
                <td><button class="btn btn-sc-icon js-cart-remove" data-item-id="<?= $item['cart_item_id'] ?>"><i class="bi bi-trash"></i></button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="<?= BASE_URL ?>products/shop.php" class="btn btn-sc-outline mt-3"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
    </div>

    <div class="col-lg-4">
      <div class="sc-card">
        <h6 class="text-gold mb-3">Order Summary</h6>
        <form class="js-coupon-form d-flex gap-2 mb-2">
          <input type="text" name="coupon_code" class="form-control form-control-sm" placeholder="Coupon code" value="<?= e($totals['coupon']['coupon_code'] ?? '') ?>">
          <button class="btn btn-sc-dark btn-sm" type="submit">Apply</button>
        </form>
        <div class="js-coupon-message small mb-3"></div>
        <div class="js-order-summary"><?= render_cart_summary_html($totals) ?></div>
        <a href="<?= BASE_URL ?>customer/checkout.php" class="btn btn-sc-gold w-100 mt-4">Proceed to Checkout</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
