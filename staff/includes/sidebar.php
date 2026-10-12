<?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="sc-sidebar">
  <div class="sc-side-title">Staff Panel</div>
  <a href="<?= BASE_URL ?>staff/dashboard.php" class="sc-side-link <?= $cur === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="<?= BASE_URL ?>staff/orders.php" class="sc-side-link <?= $cur === 'orders.php' ? 'active' : '' ?>"><i class="bi bi-bag-check"></i> Assigned Orders</a>
  <a href="<?= BASE_URL ?>staff/inventory.php" class="sc-side-link <?= $cur === 'inventory.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-data"></i> Inventory</a>
  <div class="sc-side-title">Account</div>
  <a href="<?= BASE_URL ?>auth/logout.php" class="sc-side-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
</aside>
