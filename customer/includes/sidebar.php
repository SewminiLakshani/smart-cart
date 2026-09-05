<?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="sc-sidebar with-navbar">
  <div class="sc-side-title">My Account</div>
  <a href="<?= BASE_URL ?>customer/dashboard.php" class="sc-side-link <?= $cur === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="<?= BASE_URL ?>customer/profile.php" class="sc-side-link <?= $cur === 'profile.php' ? 'active' : '' ?>"><i class="bi bi-person"></i> Profile</a>
  <a href="<?= BASE_URL ?>customer/addresses.php" class="sc-side-link <?= $cur === 'addresses.php' ? 'active' : '' ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
  <div class="sc-side-title">Shopping</div>
  <a href="<?= BASE_URL ?>customer/orders.php" class="sc-side-link <?= in_array($cur, ['orders.php','order-details.php','track-order.php']) ? 'active' : '' ?>"><i class="bi bi-bag-check"></i> Orders</a>
  <a href="<?= BASE_URL ?>customer/wishlist.php" class="sc-side-link <?= $cur === 'wishlist.php' ? 'active' : '' ?>"><i class="bi bi-heart"></i> Wishlist</a>
  <a href="<?= BASE_URL ?>customer/cart.php" class="sc-side-link <?= $cur === 'cart.php' ? 'active' : '' ?>"><i class="bi bi-bag"></i> Cart</a>
  <a href="<?= BASE_URL ?>customer/notifications.php" class="sc-side-link <?= $cur === 'notifications.php' ? 'active' : '' ?>"><i class="bi bi-bell"></i> Notifications</a>
  <div class="sc-side-title">Account</div>
  <a href="<?= BASE_URL ?>auth/logout.php" class="sc-side-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
</aside>
