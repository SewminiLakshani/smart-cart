<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?"); $chk->execute([$id]);
if ((int)$chk->fetchColumn() > 0) {
    set_flash('danger', 'Cannot delete: this category has products assigned. Reassign or remove those products first.');
} else {
    $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$id]);
    set_flash('success', 'Category deleted.');
}
redirect(BASE_URL . 'admin/categories/index.php');
