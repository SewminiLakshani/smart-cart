<?php
$adminNotifCount = unread_notification_count($user['user_id'], 'admin');
?>
<div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--sc-border)">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-sc-dark d-lg-none js-sidebar-toggle"><i class="bi bi-list"></i></button>
    <div>
      <h4 class="mb-0"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></h4>
      <small class="text-secondary">Welcome back, <?= e($user['full_name']) ?></small>
    </div>
  </div>
  <div class="d-flex align-items-center gap-3">
    <div class="dropdown">
      <a href="#" class="btn-sc-icon sc-icon-badge" data-bs-toggle="dropdown"><i class="bi bi-bell"></i>
        <?php if ($adminNotifCount): ?><span class="badge"><?= $adminNotifCount ?></span><?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-end p-2" style="width:320px">
        <?php
        $stmt = db()->prepare("SELECT * FROM notifications WHERE (audience='admin' OR audience='all') AND user_id IS NULL ORDER BY created_at DESC LIMIT 6");
        $stmt->execute();
        $notes = $stmt->fetchAll();
        if (!$notes): ?>
          <p class="text-secondary small p-2 mb-0">No notifications yet.</p>
        <?php else: foreach ($notes as $n): ?>
          <a href="<?= $n['link'] ? BASE_URL . ltrim($n['link'], '/') : '#' ?>" class="dropdown-item small py-2">
            <strong class="d-block text-gold"><?= e($n['title']) ?></strong>
            <span class="text-secondary"><?= e($n['message']) ?></span>
          </a>
        <?php endforeach; endif; ?>
        <a href="<?= BASE_URL ?>admin/notifications/index.php" class="dropdown-item text-center text-gold small mt-1">View all</a>
      </div>
    </div>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-sc-outline btn-sm">View Site</a>
  </div>
</div>
