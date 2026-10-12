<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$selectedColor = trim($_POST['selected_color'] ?? '') ?: null;

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

// If this product has color options, a valid one must be selected before it can be added.
$availableColors = get_product_colors($productId);
if (!empty($availableColors)) {
    $validNames = array_column($availableColors, 'color_name');
    if ($selectedColor === null || !in_array($selectedColor, $validNames, true)) {
        json_out(['success' => false, 'message' => 'Please select a color before adding this product to your cart.']);
    }
}

$cartId = get_or_create_cart($user['user_id']);
$stmt = $pdo->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND selected_color <=> ?");
$stmt->execute([$cartId, $productId, $selectedColor]);
$existing = $stmt->fetch();
$existingQty = $existing ? (int)$existing['quantity'] : 0;
$newQty = $existingQty + $quantity;

if ($newQty > (int)$product['stock_qty']) {
    json_out(['success' => false, 'message' => 'Only ' . $product['stock_qty'] . ' units available in stock.']);
}

if ($existing) {
    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?")->execute([$newQty, $existing['cart_item_id']]);
} else {
    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, selected_color) VALUES (?,?,?,?)")->execute([$cartId, $productId, $quantity, $selectedColor]);
}

json_out([
    'success' => true,
    'message' => e($product['product_name']) . ' added to your cart.',
    'cart_count' => cart_count($user['user_id']),
]);
