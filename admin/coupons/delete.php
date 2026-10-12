<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare("DELETE FROM coupons WHERE coupon_id = ?")->execute([$id]);
set_flash('success', 'Coupon deleted.');
redirect(BASE_URL . 'admin/coupons/index.php');
