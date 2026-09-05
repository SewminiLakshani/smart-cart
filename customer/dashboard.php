<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();
$uid = $user['user_id'];

$totalOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?"); $totalOrders->execute([$uid]); $totalOrders = (int)$totalOrders->fetchColumn();
$pendingOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status IN ('pending','confirmed','processing')"); $pendingOrders->execute([$uid]); $pendingOrders = (int)$pendingOrders->fetchColumn();
$completedOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status = 'delivered'"); $completedOrders->execute([$uid]); $completedOrders = (int)$completedOrders->fetchColumn();
$wishCount = wishlist_count($uid);

$recentOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recentOrders->execute([$uid]);
$recentOrders = $recentOrders->fetchAll();

$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$uid]);
$notifs = $notifs->fetchAll();

$recommended = $pdo->query("SELECT p.*, c.category_name, b.brand_name, i.stock_status FROM products p
                             LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN brands b ON b.brand_id=p.brand_id
                             LEFT JOIN inventory i ON i.product_id=p.product_id
                             WHERE p.status='active' ORDER BY p.avg_rating DESC LIMIT 4")->fetchAll();
$wishlistIds = [];
$wid = get_or_create_wishlist($uid);
$w = $pdo->prepare("SELECT product_id FROM wishlist_items WHERE wishlist_id = ?"); $w->execute([$wid]);
$wishlistIds = array_column($w->fetchAll(), 'product_id');

$pageTitle = 'My Dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <div class="mb-4">
      <h3>Welcome back, <?= e($user['full_name']) ?> 👋</h3>
      <p class="text-secondary">Here's what's happening with your account.</p>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-bag-check"></i></div>
          <div><h3><?= $totalOrders ?></h3><span class="label">Total Orders</span></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,178,95,.15);color:var(--sc-warning)"><i class="bi bi-hourglass-split"></i></div>
          <div><h3><?= $pendingOrders ?></h3><span class="label">Pending Orders</span></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(76,175,128,.15);color:var(--sc-success)"><i class="bi bi-check2-circle"></i></div>
          <div><h3><?= $completedOrders ?></h3><span class="label">Completed</span></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="sc-stat-card"><div class="sc-stat-icon" style="background:rgba(224,102,95,.15);color:var(--sc-danger)"><i class="bi bi-heart"></i></div>
          <div><h3><?= $wishCount ?></h3><span class="label">Wishlist Items</span></div></div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Recent Orders</h6><a href="<?= BASE_URL ?>customer/orders.php" class="small text-gold">View all</a></div>
          <?php if (empty($recentOrders)): ?>
            <p class="text-secondary small">You haven't placed any orders yet. <a href="<?= BASE_URL ?>products/shop.php">Start shopping</a></p>
          <?php else: ?>
          <div class="table-responsive"><table class="table align-middle">
            <thead class="sc-thead"><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td><?= e($o['order_number']) ?></td>
                  <td class="small text-secondary"><?= friendly_date($o['created_at'], 'M d, Y') ?></td>
                  <td><?= money($o['total_amount']) ?></td>
                  <td><?= order_status_badge($o['order_status']) ?></td>
                  <td><a href="<?= BASE_URL ?>customer/order-details.php?id=<?= $o['order_id'] ?>" class="text-gold small">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="sc-card">
          <div class="sc-card-header"><h6 class="mb-0">Recent Notifications</h6><a href="<?= BASE_URL ?>customer/notifications.php" class="small text-gold">View all</a></div>
          <?php if (empty($notifs)): ?><p class="text-secondary small">No notifications yet.</p><?php endif; ?>
          <?php foreach ($notifs as $n): ?>
            <div class="d-flex gap-2 mb-3">
              <i class="bi bi-bell text-gold mt-1"></i>
              <div>
                <div class="small fw-semibold"><?= e($n['title']) ?></div>
                <div class="small text-secondary"><?= e($n['message']) ?></div>
                <div class="small text-secondary" style="opacity:.6"><?= friendly_date($n['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <h5 class="mb-3">Recommended For You</h5>
      <div class="row g-4">
        <?php foreach ($recommended as $product): ?>
          <div class="col-6 col-md-3"><?php include __DIR__ . '/../includes/product-card.php'; ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
