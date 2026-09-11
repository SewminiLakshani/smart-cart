<?php
require __DIR__ . '/_bootstrap.php';
$user = require_ajax_login();
$deliveryMethod = in_array($_POST['delivery_method'] ?? '', ['standard', 'express']) ? $_POST['delivery_method'] : 'standard';
$totals = compute_cart_totals($user['user_id'], $deliveryMethod);
json_out(['success' => true, 'summary_html' => render_cart_summary_html($totals)]);
