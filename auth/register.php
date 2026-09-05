<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) redirect(BASE_URL . 'customer/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        [$ok, $msg] = register_customer($fullName, $email, $phone, $password);
        if ($ok) {
            set_flash('success', $msg);
            redirect(BASE_URL . 'auth/login.php');
        } else {
            $errors[] = $msg;
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-auth-wrap">
  <div class="sc-auth-card">
    <div class="text-center mb-4">
      <span class="sc-label">Join SmartCart</span>
      <h3 class="mt-2">Create Your Account</h3>
    </div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($_POST['full_name'] ?? '') ?>" required minlength="2">
      </div>
      <div class="mb-3">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label>Phone Number</label>
        <input type="tel" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>" required pattern="[0-9+\-\s]{7,15}">
      </div>
      <div class="mb-3">
        <label>Password</label>
        <div class="input-group">
          <input type="password" name="password" id="regPassword" class="form-control" required minlength="8">
          <button type="button" class="btn btn-sc-dark js-toggle-password" data-target="#regPassword"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-text text-secondary">At least 8 characters, including a letter and a number.</div>
      </div>
      <div class="mb-3">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="8">
      </div>
      <button class="btn btn-sc-gold w-100" type="submit">Create Account</button>
    </form>
    <p class="text-center text-secondary small mt-4 mb-0">Already have an account? <a href="<?= BASE_URL ?>auth/login.php">Login</a></p>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
