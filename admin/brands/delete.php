<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?"); $chk->execute([$id]);
if ((int)$chk->fetchColumn() > 0) {
    set_flash('danger', 'Cannot delete: this brand has products assigned.');
} else {
    $pdo->prepare("DELETE FROM brands WHERE brand_id = ?")->execute([$id]);
    set_flash('success', 'Brand deleted.');
}
redirect(BASE_URL . 'admin/brands/index.php');
