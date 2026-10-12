<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (strlen($fullName) < 2) $errors[] = 'Please enter your full name.';
        if (!is_valid_phone($phone)) $errors[] = 'Please enter a valid phone number.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?")->execute([$fullName, $phone, $user['user_id']]);
            $_SESSION['full_name'] = $fullName;
            set_flash('success', 'Profile updated successfully.');
            redirect(BASE_URL . 'customer/profile.php');
        }
    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (!is_valid_password($new)) {
            $errors[] = 'New password must be at least 8 characters and include a letter and a number.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
            set_flash('success', 'Password changed successfully.');
            redirect(BASE_URL . 'customer/profile.php');
        }
    }
    $user = current_user();
}

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <h3 class="mb-4">My Profile</h3>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Profile Information</h6>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
            <div class="mb-3"><label>Email Address</label><input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled></div>
            <div class="mb-3"><label>Phone Number</label><input type="tel" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required></div>
            <button type="submit" name="update_profile" class="btn btn-sc-gold">Save Changes</button>
          </form>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="sc-card">
          <h6 class="text-gold mb-3">Change Password</h6>
          <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
            <div class="mb-3"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required minlength="8"></div>
            <button type="submit" name="change_password" class="btn btn-sc-outline">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
