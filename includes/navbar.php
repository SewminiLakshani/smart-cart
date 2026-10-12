<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$cartCnt = $user ? cart_count($user['user_id']) : 0;
$wishCnt = $user ? wishlist_count($user['user_id']) : 0;
?>
<nav class="navbar navbar-expand-lg sc-navbar">
  <div class="sc-container-fluid d-flex align-items-center justify-content-between w-100">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php">Smart<span>Cart</span></a>

    <button class="navbar-toggler border-0 sc-navtoggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#scMobileNav">
      <i class="bi bi-list fs-2"></i>
    </button>

    <div class="collapse navbar-collapse d-none d-lg-flex">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link js-nav-home <?= $currentPage === 'index.php' ? 'active' : '' ?>" data-server-active="<?= $currentPage === 'index.php' ? '1' : '0' ?>" href="<?= BASE_URL ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= ($currentPage === 'shop.php' && !isset($_GET['deals'])) ? 'active' : '' ?>" href="<?= BASE_URL ?>products/shop.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link js-nav-categories" href="<?= BASE_URL ?>index.php#categories">Categories</a></li>
        <li class="nav-item"><a class="nav-link <?= ($currentPage === 'shop.php' && isset($_GET['deals'])) ? 'active' : '' ?>" href="<?= BASE_URL ?>products/shop.php?deals=1">Deals</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">Contact Us</a></li>
      </ul>
    </div>

    <div class="d-flex align-items-center gap-2">
      <form action="<?= BASE_URL ?>products/shop.php" method="get" class="position-relative d-none d-md-block me-1">
        <input type="search" name="q" class="form-control form-control-sm js-search-input" placeholder="Search products..." style="width:210px; border-radius:30px;" autocomplete="off">
        <div class="js-search-suggestions position-absolute top-100 start-0 w-100 mt-1" style="z-index:1000;"></div>
      </form>

      <?php $showShopIcons = !$user || $user['role_name'] === 'customer'; ?>
      <?php if ($showShopIcons): ?>
      <a href="<?= BASE_URL ?>customer/wishlist.php" class="btn-sc-icon sc-icon-badge" title="Wishlist">
        <i class="bi bi-heart"></i>
        <span class="badge js-wishlist-count" style="display:<?= $wishCnt ? 'flex' : 'none' ?>"><?= $wishCnt ?></span>
      </a>
      <a href="<?= BASE_URL ?>customer/cart.php" class="btn-sc-icon sc-icon-badge" title="Cart">
        <i class="bi bi-bag"></i>
        <span class="badge js-cart-count" style="display:<?= $cartCnt ? 'flex' : 'none' ?>"><?= $cartCnt ?></span>
      </a>
      <?php endif; ?>

      <?php if ($user): ?>
        <div class="dropdown">
          <a href="#" class="btn-sc-icon" data-bs-toggle="dropdown" title="Account"><i class="bi bi-person"></i></a>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li><h6 class="dropdown-header text-gold"><?= e($user['full_name']) ?></h6></li>
            <?php if ($user['role_name'] === 'admin'): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
            <?php elseif ($user['role_name'] === 'staff'): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>staff/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Staff Dashboard</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>customer/profile.php"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>auth/login.php" class="btn-sc-icon" title="Login"><i class="bi bi-person"></i></a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Mobile Offcanvas Nav -->
<div class="offcanvas offcanvas-start bg-sc-charcoal" tabindex="-1" id="scMobileNav">
  <div class="offcanvas-header">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php">Smart<span class="text-gold">Cart</span></a>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav flex-column gap-2">
      <li><a class="nav-link js-nav-home <?= $currentPage === 'index.php' ? 'active' : '' ?>" data-server-active="<?= $currentPage === 'index.php' ? '1' : '0' ?>" href="<?= BASE_URL ?>index.php">Home</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>products/shop.php">Shop</a></li>
      <li><a class="nav-link js-nav-categories" href="<?= BASE_URL ?>index.php#categories">Categories</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>products/shop.php?deals=1">Deals</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>about.php">About Us</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>contact.php">Contact Us</a></li>
      <?php if ($showShopIcons): ?>
      <li><a class="nav-link" href="<?= BASE_URL ?>customer/wishlist.php">Wishlist</a></li>
      <li><a class="nav-link" href="<?= BASE_URL ?>customer/cart.php">Cart</a></li>
      <?php endif; ?>
      <?php if (!$user): ?><li><a class="nav-link text-gold" href="<?= BASE_URL ?>auth/login.php">Login / Register</a></li><?php endif; ?>
    </ul>
  </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Quick View</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>
