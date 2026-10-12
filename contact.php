<?php
require_once __DIR__ . '/includes/auth.php';
$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (strlen($name) < 2) $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($subject) < 3) $errors[] = 'Please enter a subject.';
    if (strlen($message) < 10) $errors[] = 'Your message should be at least 10 characters.';

    if (empty($errors)) {
        db()->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)")
            ->execute([$name, $email, $subject, $message]);
        $sent = true;
    }
}

$pageTitle = 'Contact Us';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="sc-section">
  <div class="sc-container-fluid">
    <div class="text-center mb-5">
      <span class="sc-label">Get In Touch</span>
      <h2 class="sc-section-title">Contact Us</h2>
      <p class="sc-section-sub">We'd love to hear from you. Reach out with any questions.</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="sc-card mb-3"><i class="bi bi-geo-alt text-gold fs-4 me-3"></i><strong>Address</strong><p class="text-secondary small mt-2 mb-0">142 Galle Road, Colombo 03, Sri Lanka</p></div>
        <div class="sc-card mb-3"><i class="bi bi-envelope text-gold fs-4 me-3"></i><strong>Email</strong><p class="text-secondary small mt-2 mb-0">support@smartcart.com</p></div>
        <div class="sc-card mb-3"><i class="bi bi-telephone text-gold fs-4 me-3"></i><strong>Phone</strong><p class="text-secondary small mt-2 mb-0">+94 11 234 5678</p></div>
        <div class="sc-card">
          <strong class="d-block mb-3">Follow Us</strong>
          <a href="#" class="sc-social"><i class="bi bi-facebook"></i></a>
          <a href="#" class="sc-social"><i class="bi bi-instagram"></i></a>
          <a href="#" class="sc-social"><i class="bi bi-twitter-x"></i></a>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="sc-card">
          <?php if ($sent): ?>
            <div class="alert alert-success">Thank you! Your message has been sent. We'll get back to you soon.</div>
          <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>
            <form method="post" class="needs-validation" novalidate>
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6"><label>Name</label><input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>"></div>
                <div class="col-md-6"><label>Email</label><input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>"></div>
                <div class="col-12"><label>Subject</label><input type="text" name="subject" class="form-control" required value="<?= e($_POST['subject'] ?? '') ?>"></div>
                <div class="col-12"><label>Message</label><textarea name="message" rows="5" class="form-control" required><?= e($_POST['message'] ?? '') ?></textarea></div>
                <div class="col-12"><button type="submit" class="btn btn-sc-gold">Send Message</button></div>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
