<?php
$cur = basename($_SERVER['SCRIPT_NAME']);
$dir = basename(dirname($_SERVER['SCRIPT_NAME']));
function sc_admin_link($url, $icon, $label, $activeDirs = [], $activeFiles = []) {
    global $cur, $dir;
    $isActive = in_array($dir, $activeDirs, true) || in_array($cur, $activeFiles, true);
    echo '<a href="' . $url . '" class="sc-side-link ' . ($isActive ? 'active' : '') . '"><i class="bi ' . $icon . '"></i> ' . $label . '</a>';
}
?>
<aside class="sc-sidebar">
  <div class="sc-side-title">Overview</div>
  <?php sc_admin_link(BASE_URL . 'admin/dashboard.php', 'bi-speedometer2', 'Dashboard', [], ['dashboard.php']); ?>

  <div class="sc-side-title">Catalog</div>
  <?php sc_admin_link(BASE_URL . 'admin/products/index.php', 'bi-box-seam', 'Products', ['products']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/categories/index.php', 'bi-tags', 'Categories', ['categories']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/brands/index.php', 'bi-award', 'Brands', ['brands']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/inventory/index.php', 'bi-clipboard-data', 'Inventory', ['inventory']); ?>

  <div class="sc-side-title">Sales</div>
  <?php sc_admin_link(BASE_URL . 'admin/orders/index.php', 'bi-bag-check', 'Orders', ['orders']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/customers/index.php', 'bi-people', 'Customers', ['customers']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/coupons/index.php', 'bi-ticket-perforated', 'Coupons', ['coupons']); ?>

  <div class="sc-side-title">Engagement</div>
  <?php sc_admin_link(BASE_URL . 'admin/reviews/index.php', 'bi-star', 'Reviews', ['reviews']); ?>
  <?php sc_admin_link(BASE_URL . 'admin/notifications/index.php', 'bi-bell', 'Notifications', ['notifications']); ?>

  <div class="sc-side-title">Insights</div>
  <?php sc_admin_link(BASE_URL . 'admin/reports/sales.php', 'bi-graph-up-arrow', 'Reports', ['reports']); ?>

  <div class="sc-side-title">Account</div>
  <?php sc_admin_link(BASE_URL . 'auth/logout.php', 'bi-box-arrow-right', 'Logout'); ?>
</aside>
