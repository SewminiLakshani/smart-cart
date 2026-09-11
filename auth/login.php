<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    $u = current_user();
    redirect(BASE_URL . ($u['role_name'] === 'admin' ? 'admin/dashboard.php' : ($u['role_name'] === 'staff' ? 'staff/dashboard.php' : 'customer/dashboard.php')));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        [$ok, $msg] = attempt_login($email, $password);
        if ($ok) {
            $redirectTo = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            set_flash('success', $msg);
            $u = current_user();
            if ($redirectTo && strpos($redirectTo, '/admin/') === false && strpos($redirectTo, '/staff/') === false) {
                redirect($redirectTo);
            }
            redirect(BASE_URL . ($u['role_name'] === 'admin' ? 'admin/dashboard.php' : ($u['role_name'] === 'staff' ? 'staff/dashboard.php' : 'customer/dashboard.php')));
        } else {
            $errors[] = $msg;
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-auth-wrap">
  <div class="sc-auth-card">
    <div class="text-center mb-4">
      <span class="sc-label">Welcome Back</span>
      <h3 class="mt-2">Login to SmartCart</h3>
    </div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="mb-2">
        <label>Password</label>
        <div class="input-group">
          <input type="password" name="password" id="loginPassword" class="form-control" required>
          <button type="button" class="btn btn-sc-dark js-toggle-password" data-target="#loginPassword"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="text-end mb-3"><a href="<?= BASE_URL ?>auth/forgot-password.php" class="small">Forgot password?</a></div>
      <button class="btn btn-sc-gold w-100" type="submit">Login</button>
    </form>
    <p class="text-center text-secondary small mt-4 mb-0">Don't have an account? <a href="<?= BASE_URL ?>auth/register.php">Create one</a></p>
    <div class="sc-divider"></div>
    <p class="text-center text-secondary small mb-0">Demo accounts — password for all: <code class="text-gold">Password@123</code><br>
      Admin: admin@smartcart.com · Staff: staff@smartcart.com · Customer: nimal@example.com</p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
