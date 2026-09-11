<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$itemId = (int)($_POST['cart_item_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("DELETE ci FROM cart_items ci JOIN cart c ON c.cart_id = ci.cart_id WHERE ci.cart_item_id = ? AND c.user_id = ?");
$stmt->execute([$itemId, $user['user_id']]);

$totals = compute_cart_totals($user['user_id']);

json_out([
    'success' => true,
    'cart_count' => cart_count($user['user_id']),
    'summary_html' => render_cart_summary_html($totals),
]);
