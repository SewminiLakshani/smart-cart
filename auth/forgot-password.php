<?php
require_once __DIR__ . '/../includes/auth.php';
if (is_logged_in()) redirect(BASE_URL . 'index.php');

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(strtolower($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $uid = $stmt->fetchColumn();
        if ($uid) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?")->execute([$token, $expires, $uid]);
            // In production this link would be emailed. For this academic/demo build we surface it directly.
            $_SESSION['demo_reset_link'] = BASE_URL . 'auth/reset-password.php?token=' . $token;
        }
    }
    $sent = true; // Always show generic success (avoid user enumeration)
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-auth-wrap">
  <div class="sc-auth-card">
    <div class="text-center mb-4">
      <span class="sc-label">Account Recovery</span>
      <h3 class="mt-2">Forgot Password</h3>
    </div>
    <?php if ($sent): ?>
      <div class="alert alert-success small">If an account exists for that email, password reset instructions have been generated.</div>
      <?php if (!empty($_SESSION['demo_reset_link'])): ?>
        <div class="alert alert-info small">
          <strong>Demo mode:</strong> no mail server is configured, so here is your reset link directly:<br>
          <a href="<?= e($_SESSION['demo_reset_link']) ?>"><?= e($_SESSION['demo_reset_link']) ?></a>
        </div>
        <?php unset($_SESSION['demo_reset_link']); ?>
      <?php endif; ?>
    <?php else: ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label>Email Address</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <button class="btn btn-sc-gold w-100" type="submit">Send Reset Link</button>
      </form>
    <?php endif; ?>
    <p class="text-center text-secondary small mt-4 mb-0"><a href="<?= BASE_URL ?>auth/login.php">&larr; Back to Login</a></p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
