<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

$pdo = db();
$stmt = $pdo->prepare("SELECT p.*, i.quantity AS stock_qty FROM products p LEFT JOIN inventory i ON i.product_id = p.product_id WHERE p.product_id = ? AND p.status='active'");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    json_out(['success' => false, 'message' => 'Product not found.']);
}
if ((int)$product['stock_qty'] <= 0) {
    json_out(['success' => false, 'message' => 'This product is currently out of stock.']);
}

$cartId = get_or_create_cart($user['user_id']);
$stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
$stmt->execute([$cartId, $productId]);
$existingQty = (int)$stmt->fetchColumn();
$newQty = $existingQty + $quantity;

if ($newQty > (int)$product['stock_qty']) {
    json_out(['success' => false, 'message' => 'Only ' . $product['stock_qty'] . ' units available in stock.']);
}

if ($existingQty > 0) {
    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?")->execute([$newQty, $cartId, $productId]);
} else {
    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?)")->execute([$cartId, $productId, $quantity]);
}

json_out([
    'success' => true,
    'message' => e($product['product_name']) . ' added to your cart.',
    'cart_count' => cart_count($user['user_id']),
]);
