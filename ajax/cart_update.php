<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$itemId = (int)($_POST['cart_item_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

$pdo = db();
$stmt = $pdo->prepare("SELECT ci.*, p.product_name, i.quantity AS stock_qty
                        FROM cart_items ci JOIN cart c ON c.cart_id = ci.cart_id
                        JOIN products p ON p.product_id = ci.product_id
                        LEFT JOIN inventory i ON i.product_id = p.product_id
                        WHERE ci.cart_item_id = ? AND c.user_id = ?");
$stmt->execute([$itemId, $user['user_id']]);
$item = $stmt->fetch();

if (!$item) json_out(['success' => false, 'message' => 'Cart item not found.']);

if ($quantity > (int)$item['stock_qty']) {
    json_out(['success' => false, 'message' => 'Only ' . $item['stock_qty'] . ' units available.']);
}

$pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?")->execute([$quantity, $itemId]);

$stmtP = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmtP->execute([$item['product_id']]);
$product = $stmtP->fetch();
$lineTotal = effective_price($product) * $quantity;

$totals = compute_cart_totals($user['user_id']);

json_out([
    'success' => true,
    'line_total' => money($lineTotal),
    'cart_count' => cart_count($user['user_id']),
    'summary_html' => render_cart_summary_html($totals),
]);
