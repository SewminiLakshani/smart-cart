<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $audience = in_array($_POST['audience'] ?? '', ['customer','staff','all']) ? $_POST['audience'] : 'customer';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($title !== '' && $message !== '') {
        create_notification(null, $audience, $title, $message, 'announcement');
        set_flash('success', 'Notification broadcast sent.');
    } else {
        set_flash('danger', 'Please enter both a title and a message.');
    }
    redirect(BASE_URL . 'admin/notifications/index.php');
}

$notifs = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 50")->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Send Broadcast Notification</h6>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label>Audience</label>
              <select name="audience" class="form-select">
                <option value="customer">All Customers</option>
                <option value="staff">All Staff</option>
                <option value="all">Everyone</option>
              </select>
            </div>
            <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="mb-3"><label>Message</label><textarea name="message" class="form-control" rows="4" required></textarea></div>
            <button type="submit" class="btn btn-sc-gold w-100">Send Notification</button>
          </form>
        </div>
      </div>
      <div class="col-lg-7">
        <h6 class="text-gold mb-3">System Notifications Log</h6>
        <?php foreach ($notifs as $n): ?>
          <div class="sc-card mb-3">
            <div class="d-flex justify-content-between">
              <strong><?= e($n['title']) ?></strong>
              <span class="badge sc-badge bg-secondary text-uppercase"><?= e($n['audience']) ?></span>
            </div>
            <p class="text-secondary small mb-1 mt-1"><?= e($n['message']) ?></p>
            <span class="text-secondary small" style="opacity:.6"><?= friendly_date($n['created_at']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (empty($notifs)): ?><div class="sc-card text-center text-secondary py-4">No system notifications yet.</div><?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
