<?php
/**
 * Shared <head>. Expects (optionally) $pageTitle to be set before include.
 * Must be included AFTER config/database.php + includes/functions.php are loaded.
 */
$flashes = get_flashes();
$user = current_user();
// Admin & Staff panels keep the dark theme; every other page (storefront + customer account) gets the light theme.
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isBackend = (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/staff/') !== false);
$bodyThemeClass = $isBackend ? 'sc-theme-dark' : 'sc-theme-light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
  // If this URL has a #anchor (e.g. Categories link), stop the browser's own instant/jarring jump-to-anchor.
  // We restore the anchor and animate to it smoothly ourselves once the page has fully finished loading
  // (see assets/js/script.js) — this avoids the "jumps to top, then jumps again once images load" glitch.
  if (window.location.hash) {
    window.__scPendingHash = window.location.hash;
    history.replaceState(null, '', window.location.pathname + window.location.search);
  }
</script>
<noscript><style>body{opacity:1 !important;}</style></noscript>
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
<body data-base-url="<?= BASE_URL ?>" class="<?= $bodyThemeClass ?>">

<?php if (!empty($flashes)): ?>
  <div class="position-fixed top-0 start-50 translate-middle-x mt-3 sc-flash-toast" style="z-index:2000; width:min(92%,480px)">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
