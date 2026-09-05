<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$code = trim($_POST['coupon_code'] ?? '');
if ($code === '') json_out(['success' => false, 'message' => 'Please enter a coupon code.']);

$rawTotals = compute_cart_totals($user['user_id']); // current subtotal without new coupon
[$valid, $message, $coupon, $discount] = validate_and_calc_coupon($code, $rawTotals['subtotal']);

if (!$valid) {
    json_out(['success' => false, 'message' => $message]);
}

$_SESSION['applied_coupon_code'] = strtoupper($code);
$totals = compute_cart_totals($user['user_id']);

json_out([
    'success' => true,
    'message' => $message,
    'summary_html' => render_cart_summary_html($totals),
]);
