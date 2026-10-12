<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$productId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
$stmt->execute([$productId]);
$hasOrders = (int)$stmt->fetchColumn() > 0;

if ($hasOrders) {
    // Preserve order history integrity — deactivate instead of hard delete
    $pdo->prepare("UPDATE products SET status = 'inactive' WHERE product_id = ?")->execute([$productId]);
    set_flash('warning', 'This product has order history, so it was deactivated instead of deleted (to preserve order records).');
} else {
    $imgs = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $imgs->execute([$productId]);
    foreach ($imgs->fetchAll() as $img) {
        $path = __DIR__ . '/../../' . $img['image_path'];
        if (strpos($img['image_path'], 'uploads/') === 0 && file_exists($path)) @unlink($path);
    }
    $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$productId]);
    set_flash('success', 'Product deleted successfully.');
}
redirect(BASE_URL . 'admin/products/index.php');
