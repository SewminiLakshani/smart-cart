<?php
/**
 * Shared <head>. Expects (optionally) $pageTitle to be set before include.
 * Must be included AFTER config/database.php + includes/functions.php are loaded.
 */
$flashes = get_flashes();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' — Premium Shopping, Redefined' ?></title>
<meta name="description" content="SmartCart — a curated, premium online shopping destination for electronics, fashion, beauty, home and more.">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22 fill=%22%23d4af6a%22>S</text></svg>">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- SmartCart custom design system -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body data-base-url="<?= BASE_URL ?>">

<?php if (!empty($flashes)): ?>
  <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:2000; width:min(92%,480px)">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
