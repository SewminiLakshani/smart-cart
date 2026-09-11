<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();
$uid = $user['user_id'];

$totalsPreview = compute_cart_totals($uid);
if (empty($totalsPreview['items'])) {
    set_flash('warning', 'Your cart is empty. Add products before checking out.');
    redirect(BASE_URL . 'customer/cart.php');
}

$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addresses->execute([$uid]);
$addresses = $addresses->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Determine / create shipping address
    $addressId = (int)($_POST['address_id'] ?? 0);
    $newAddress = null;
    if ($addressId === 0 && isset($_POST['new_full_name'])) {
        $fn = trim($_POST['new_full_name']); $ph = trim($_POST['new_phone']); $al = trim($_POST['new_address_line']);
        $ct = trim($_POST['new_city']); $pv = trim($_POST['new_province']); $pc = trim($_POST['new_postal_code'] ?? '');
        if (strlen($fn) < 2 || !is_valid_phone($ph) || $al === '' || $ct === '' || $pv === '') {
            $errors[] = 'Please complete all required delivery address fields.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line, city, province, postal_code, address_type, is_default) VALUES (?,?,?,?,?,?,?,?,0)");
            $stmt->execute([$uid, $fn, $ph, $al, $ct, $pv, $pc, 'home']);
            $addressId = (int)$pdo->lastInsertId();
        }
    } elseif ($addressId === 0) {
        $errors[] = 'Please select or add a delivery address.';
    }

    $deliveryMethod = in_array($_POST['delivery_method'] ?? '', ['standard', 'express']) ? $_POST['delivery_method'] : 'standard';
    $paymentMethod = in_array($_POST['payment_method'] ?? '', ['cod', 'card']) ? $_POST['payment_method'] : 'cod';

    // Simulated card validation (never stored beyond last 4 digits) — matches real card-number standards:
    // digits-only, 13–19 length, Luhn checksum for the number; MM/YY (not expired) for expiry; 3–4 digits for CVV.
    $cardLast4 = null;
    if ($paymentMethod === 'card') {
        $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');

        if (!is_valid_card_number($cardNumber)) {
            $errors[] = 'That card number doesn\'t look valid — please check the digits and try again.';
        } elseif (!is_valid_card_expiry($cardExpiry)) {
            $errors[] = 'Please enter a valid, non-expired expiry date in MM/YY format.';
        } elseif (!preg_match('/^\d{3,4}$/', $cardCvv)) {
            $errors[] = 'CVV must be 3 or 4 digits.';
        } else {
            $cardLast4 = substr($cardNumber, -4);
        }
    }

    if (empty($errors)) {
        $addr = $pdo->prepare("SELECT * FROM addresses WHERE address_id = ? AND user_id = ?");
        $addr->execute([$addressId, $uid]);
        $addr = $addr->fetch();

        if (!$addr) {
            $errors[] = 'Invalid delivery address selected.';
        } else {
            $totals = compute_cart_totals($uid, $deliveryMethod);

            // Final stock re-validation before placing the order
            $stockOk = true;
            foreach ($totals['items'] as $it) {
                if ($it['quantity'] > (int)$it['stock_qty']) { $stockOk = false; break; }
            }
            if (!$stockOk) {
                $errors[] = 'Some items in your cart exceed available stock. Please review your cart.';
            } else {
                $pdo->beginTransaction();
                try {
                    $orderNumber = generate_order_number();
                    $pdo->prepare("INSERT INTO orders (order_number, user_id, address_id, coupon_id, subtotal, discount_amount, delivery_fee, total_amount, delivery_method, payment_method, order_status, shipping_full_name, shipping_phone, shipping_address, shipping_city, shipping_province, shipping_postal_code)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([
                            $orderNumber, $uid, $addressId, $totals['coupon']['coupon_id'] ?? null,
                            $totals['subtotal'], $totals['discount'], $totals['delivery_fee'], $totals['total'],
                            $deliveryMethod, $paymentMethod, 'pending',
                            $addr['full_name'], $addr['phone'], $addr['address_line'], $addr['city'], $addr['province'], $addr['postal_code'],
                        ]);
                    $orderId = (int)$pdo->lastInsertId();

                    foreach ($totals['items'] as $it) {
                        $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?)")
                            ->execute([$orderId, $it['product_id'], $it['product_name'], $it['unit_price'], $it['quantity'], $it['line_total']]);
                        // Deduct stock
                        adjust_stock((int)$it['product_id'], -1 * (int)$it['quantity'], 'order', $uid, 'Order ' . $orderNumber);
                        $pdo->prepare("UPDATE products SET total_sold = total_sold + ? WHERE product_id = ?")->execute([$it['quantity'], $it['product_id']]);
                    }

                    $pdo->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_status, card_last_four) VALUES (?,?,?,?,?)")
                        ->execute([$orderId, $paymentMethod, $totals['total'], $paymentMethod === 'card' ? 'paid' : 'pending', $cardLast4]);

                    $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?, 'pending', 'Order placed by customer', ?)")
                        ->execute([$orderId, $uid]);

                    if ($totals['coupon']) {
                        $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_applied) VALUES (?,?,?,?)")
                            ->execute([$totals['coupon']['coupon_id'], $uid, $orderId, $totals['discount']]);
                        $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = ?")->execute([$totals['coupon']['coupon_id']]);
                    }

                    // Clear cart
                    $cartId = get_or_create_cart($uid);
                    $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
                    unset($_SESSION['applied_coupon_code']);

                    $pdo->commit();

                    create_notification($uid, 'customer', 'Order Placed', "Your order $orderNumber has been placed successfully.", 'order_placed', BASE_URL . 'customer/order-details.php?id=' . $orderId);
                    notify_admins_and_staff('New Order Received', "A new order $orderNumber has been placed.", 'new_order', '/admin/orders/details.php?id=' . $orderId);

                    redirect(BASE_URL . 'customer/order-details.php?id=' . $orderId . '&placed=1');
                } catch (Throwable $ex) {
                    $pdo->rollBack();
                    error_log('Checkout failed: ' . $ex->getMessage());
                    $errors[] = 'Something went wrong while placing your order. Please try again.';
                }
            }
        }
    }
}

$totals = compute_cart_totals($uid);
$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-container-fluid py-5">
  <div class="sc-checkout-steps">
    <div class="cstep active"><span class="n">1</span> Information</div>
    <div class="cstep active"><span class="n">2</span> Delivery Address</div>
    <div class="cstep active"><span class="n">3</span> Delivery Method</div>
    <div class="cstep active"><span class="n">4</span> Payment</div>
    <div class="cstep active"><span class="n">5</span> Confirmation</div>
  </div>

  <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" id="checkoutForm" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
      <div class="col-lg-8">

        <!-- Step 1: Customer Info -->
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3"><i class="bi bi-person me-2"></i>Customer Information</h6>
          <p class="mb-1"><strong><?= e($user['full_name']) ?></strong></p>
          <p class="text-secondary small mb-0"><?= e($user['email']) ?> · <?= e($user['phone']) ?></p>
        </div>

        <!-- Step 2: Address -->
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3"><i class="bi bi-geo-alt me-2"></i>Delivery Address</h6>
          <?php if (!empty($addresses)): foreach ($addresses as $addr): ?>
            <div class="form-check mb-2 p-3 rounded" style="background:var(--sc-charcoal-3)">
              <input class="form-check-input" type="radio" name="address_id" id="addr<?= $addr['address_id'] ?>" value="<?= $addr['address_id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> required>
              <label class="form-check-label" for="addr<?= $addr['address_id'] ?>">
                <strong><?= e($addr['full_name']) ?></strong> (<?= e(ucfirst($addr['address_type'])) ?>)<br>
                <span class="small text-secondary"><?= e($addr['phone']) ?> — <?= e($addr['address_line']) ?>, <?= e($addr['city']) ?>, <?= e($addr['province']) ?> <?= e($addr['postal_code']) ?></span>
              </label>
            </div>
          <?php endforeach; endif; ?>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="address_id" id="addrNew" value="0" <?= empty($addresses) ? 'checked' : '' ?>>
            <label class="form-check-label" for="addrNew">Use a new address</label>
          </div>
          <div id="newAddressFields" class="row g-2 mt-2" style="<?= empty($addresses) ? '' : 'display:none' ?>">
            <div class="col-md-6"><input type="text" name="new_full_name" class="form-control form-control-sm" placeholder="Full Name"></div>
            <div class="col-md-6"><input type="tel" name="new_phone" class="form-control form-control-sm" placeholder="Phone Number"></div>
            <div class="col-12"><input type="text" name="new_address_line" class="form-control form-control-sm" placeholder="Address Line"></div>
            <div class="col-md-4"><input type="text" name="new_city" class="form-control form-control-sm" placeholder="City"></div>
            <div class="col-md-4"><input type="text" name="new_province" class="form-control form-control-sm" placeholder="Province"></div>
            <div class="col-md-4"><input type="text" name="new_postal_code" class="form-control form-control-sm" placeholder="Postal Code"></div>
          </div>
        </div>

        <!-- Step 3: Delivery Method -->
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3"><i class="bi bi-truck me-2"></i>Delivery Method</h6>
          <div class="form-check mb-2 p-3 rounded" style="background:var(--sc-charcoal-3)">
            <input class="form-check-input js-delivery-method" type="radio" name="delivery_method" id="dmStandard" value="standard" checked>
            <label class="form-check-label d-flex justify-content-between" for="dmStandard"><span>Standard Delivery (3-5 days)</span><strong><?= money(DEFAULT_DELIVERY_STANDARD) ?></strong></label>
          </div>
          <div class="form-check p-3 rounded" style="background:var(--sc-charcoal-3)">
            <input class="form-check-input js-delivery-method" type="radio" name="delivery_method" id="dmExpress" value="express">
            <label class="form-check-label d-flex justify-content-between" for="dmExpress"><span>Express Delivery (1-2 days)</span><strong><?= money(DEFAULT_DELIVERY_EXPRESS) ?></strong></label>
          </div>
          <p class="text-secondary small mt-2 mb-0">Free delivery on orders over <?= money(FREE_DELIVERY_THRESHOLD) ?>.</p>
        </div>

        <!-- Step 4: Payment -->
        <div class="sc-card mb-4">
          <h6 class="text-gold mb-3"><i class="bi bi-credit-card me-2"></i>Payment Method</h6>
          <div class="form-check mb-2 p-3 rounded" style="background:var(--sc-charcoal-3)">
            <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pmCod" value="cod" checked>
            <label class="form-check-label" for="pmCod">Cash on Delivery</label>
          </div>
          <div class="form-check p-3 rounded" style="background:var(--sc-charcoal-3)">
            <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pmCard" value="card">
            <label class="form-check-label" for="pmCard">Card Payment (simulated / demo)</label>
          </div>
          <div id="cardFields" class="row g-2 mt-3" style="display:none">
            <div class="col-12 alert alert-info small">This is a demo checkout. No real payment is processed and card data is never stored — only the last 4 digits are kept for the order record.</div>
            <div class="col-12">
              <input type="text" inputmode="numeric" id="cardNumberInput" name="card_number" class="form-control form-control-sm" placeholder="Card Number (e.g. 4111 1111 1111 1111)" maxlength="23" autocomplete="cc-number">
              <div class="invalid-feedback" id="cardNumberError">Enter a valid card number (13–19 digits, standard checksum).</div>
            </div>
            <div class="col-6">
              <input type="text" inputmode="numeric" id="cardExpiryInput" name="card_expiry" class="form-control form-control-sm" placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
              <div class="invalid-feedback" id="cardExpiryError">Enter a valid, non-expired MM/YY date.</div>
            </div>
            <div class="col-6">
              <input type="text" inputmode="numeric" id="cardCvvInput" name="card_cvv" class="form-control form-control-sm" placeholder="CVV" maxlength="4" autocomplete="cc-csc">
              <div class="invalid-feedback" id="cardCvvError">Enter a valid 3 or 4 digit CVV.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 5: Confirmation / Summary -->
      <div class="col-lg-4">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Order Summary</h6>
          <?php foreach ($totals['items'] as $item): ?>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-secondary"><?= e($item['product_name']) ?> × <?= $item['quantity'] ?></span>
              <span><?= money($item['line_total']) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="sc-divider"></div>
          <div class="js-order-summary"><?= render_cart_summary_html($totals) ?></div>
          <button type="submit" class="btn btn-sc-gold w-100 mt-4"><i class="bi bi-lock me-1"></i>Place Order</button>
          <a href="<?= BASE_URL ?>customer/cart.php" class="btn btn-sc-outline w-100 mt-2">Back to Cart</a>
        </div>
      </div>
    </div>
  </form>
</div>
<script>
document.querySelectorAll('input[name="address_id"]').forEach(r => r.addEventListener('change', function () {
  document.getElementById('newAddressFields').style.display = this.value === '0' ? 'flex' : 'none';
}));
document.querySelectorAll('.js-payment-method').forEach(r => r.addEventListener('change', function () {
  document.getElementById('cardFields').style.display = this.value === 'card' ? 'flex' : 'none';
}));
document.querySelectorAll('.js-delivery-method').forEach(r => r.addEventListener('change', async function () {
  const resp = await scAjaxPost('ajax/recalc_summary.php', { delivery_method: this.value, csrf_token: document.querySelector('meta[name="csrf-token"]').content });
  if (resp.success) document.querySelector('.js-order-summary').innerHTML = resp.summary_html;
}));

/* ---------------- Card Number: live 4-4-4-4 formatting + Luhn checksum validation ---------------- */
function luhnCheck(digitsOnly) {
  let sum = 0, alt = false;
  for (let i = digitsOnly.length - 1; i >= 0; i--) {
    let n = parseInt(digitsOnly[i], 10);
    if (alt) { n *= 2; if (n > 9) n -= 9; }
    sum += n; alt = !alt;
  }
  return digitsOnly.length >= 13 && digitsOnly.length <= 19 && sum % 10 === 0;
}
const cardNumberInput = document.getElementById('cardNumberInput');
if (cardNumberInput) {
  cardNumberInput.addEventListener('input', function () {
    const digits = this.value.replace(/\D/g, '').slice(0, 19);
    this.value = digits.replace(/(.{4})/g, '$1 ').trim();
    this.classList.remove('is-invalid');
  });
  cardNumberInput.addEventListener('blur', function () {
    const digits = this.value.replace(/\D/g, '');
    if (digits.length === 0) { this.classList.remove('is-invalid'); return; }
    this.classList.toggle('is-invalid', !luhnCheck(digits));
  });
}

/* ---------------- Expiry: live MM/YY formatting + not-expired validation ---------------- */
const cardExpiryInput = document.getElementById('cardExpiryInput');
if (cardExpiryInput) {
  cardExpiryInput.addEventListener('input', function () {
    let digits = this.value.replace(/\D/g, '').slice(0, 4);
    if (digits.length >= 3) digits = digits.slice(0, 2) + '/' + digits.slice(2);
    this.value = digits;
    this.classList.remove('is-invalid');
  });
  cardExpiryInput.addEventListener('blur', function () {
    const m = this.value.match(/^(\d{2})\/(\d{2})$/);
    if (!m) { this.classList.toggle('is-invalid', this.value.length > 0); return; }
    const month = parseInt(m[1], 10), year = 2000 + parseInt(m[2], 10);
    const endOfMonth = new Date(year, month, 0, 23, 59, 59);
    const valid = month >= 1 && month <= 12 && endOfMonth >= new Date();
    this.classList.toggle('is-invalid', !valid);
  });
}

/* ---------------- CVV: digits only, 3-4 length ---------------- */
const cardCvvInput = document.getElementById('cardCvvInput');
if (cardCvvInput) {
  cardCvvInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 4);
    this.classList.remove('is-invalid');
  });
  cardCvvInput.addEventListener('blur', function () {
    if (this.value.length === 0) { this.classList.remove('is-invalid'); return; }
    this.classList.toggle('is-invalid', !/^\d{3,4}$/.test(this.value));
  });
}

/* ---------------- Block submit with a card payment until all card fields pass validation ---------------- */
document.getElementById('checkoutForm').addEventListener('submit', function (e) {
  const cardSelected = document.getElementById('pmCard') && document.getElementById('pmCard').checked;
  if (!cardSelected) return;
  const digits = cardNumberInput.value.replace(/\D/g, '');
  cardNumberInput.classList.toggle('is-invalid', !luhnCheck(digits));
  cardExpiryInput.dispatchEvent(new Event('blur'));
  cardCvvInput.dispatchEvent(new Event('blur'));
  if (document.querySelector('#cardFields .is-invalid')) {
    e.preventDefault();
    scToast('Please fix the highlighted card details before placing your order.', 'danger');
  }
});
</script>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
