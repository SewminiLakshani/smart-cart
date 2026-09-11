<?php
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$pdo = db();
$stmt = $pdo->prepare("SELECT user_id, reset_token_expires FROM users WHERE reset_token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch();
$valid = $row && strtotime($row['reset_token_expires']) > time();

$errors = [];
$done = false;

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!is_valid_password($password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?")
            ->execute([$hash, $row['user_id']]);
        $done = true;
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-auth-wrap">
  <div class="sc-auth-card">
    <div class="text-center mb-4">
      <span class="sc-label">Account Recovery</span>
      <h3 class="mt-2">Reset Password</h3>
    </div>
    <?php if (!$valid): ?>
      <div class="alert alert-danger small">This password reset link is invalid or has expired. Please request a new one.</div>
      <a href="<?= BASE_URL ?>auth/forgot-password.php" class="btn btn-sc-gold w-100">Request New Link</a>
    <?php elseif ($done): ?>
      <div class="alert alert-success small">Your password has been reset successfully.</div>
      <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-sc-gold w-100">Login Now</a>
    <?php else: ?>
      <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3">
          <label>New Password</label>
          <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button class="btn btn-sc-gold w-100" type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
