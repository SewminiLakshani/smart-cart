<?php
require __DIR__ . '/_bootstrap.php';
require_ajax_csrf();
$user = require_ajax_login();

$productId = (int)($_POST['product_id'] ?? 0);
$pdo = db();
$wishlistId = get_or_create_wishlist($user['user_id']);

$stmt = $pdo->prepare("SELECT wishlist_item_id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
$stmt->execute([$wishlistId, $productId]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $pdo->prepare("DELETE FROM wishlist_items WHERE wishlist_item_id = ?")->execute([$existing]);
    $action = 'removed';
    $message = 'Removed from wishlist.';
} else {
    $pdo->prepare("INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?,?)")->execute([$wishlistId, $productId]);
    $action = 'added';
    $message = 'Added to wishlist.';
}

json_out([
    'success' => true,
    'action' => $action,
    'message' => $message,
    'wishlist_count' => wishlist_count($user['user_id']),
]);
