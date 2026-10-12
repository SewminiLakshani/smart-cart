<?php
require_once __DIR__ . '/../includes/functions.php';
$q = trim($_GET['q'] ?? '');
redirect(BASE_URL . 'products/shop.php' . ($q !== '' ? '?q=' . urlencode($q) : ''));
