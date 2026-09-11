<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();

if (isset($_GET['read_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['user_id']]);
    redirect(BASE_URL . 'customer/notifications.php');
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['user_id']]);
$notifs = $stmt->fetchAll();
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['user_id']]);

$icons = [
    'order_placed' => 'bag-check', 'order_delivered' => 'house-check', 'order_shipped' => 'truck',
    'order_cancelled' => 'x-circle', 'welcome' => 'stars', 'offer' => 'tag', 'default' => 'bell',
];

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <h3 class="mb-4">Notifications</h3>
    <?php if (empty($notifs)): ?>
      <div class="sc-card text-center py-5 text-secondary">No notifications yet.</div>
    <?php else: ?>
      <?php foreach ($notifs as $n): $icon = $icons[$n['type']] ?? $icons['default']; ?>
        <a href="<?= $n['link'] ? e($n['link']) : '#' ?>" class="sc-card d-flex gap-3 mb-3 text-decoration-none" style="color:var(--sc-white)">
          <div class="sc-stat-icon" style="width:44px;height:44px;background:rgba(212,175,106,.15);color:var(--sc-gold)"><i class="bi bi-<?= $icon ?>"></i></div>
          <div>
            <div class="fw-semibold"><?= e($n['title']) ?></div>
            <div class="text-secondary small"><?= e($n['message']) ?></div>
            <div class="text-secondary small mt-1" style="opacity:.6"><?= friendly_date($n['created_at']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
