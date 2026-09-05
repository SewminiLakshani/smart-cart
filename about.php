<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About Us';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="sc-section">
  <div class="sc-container-fluid">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="sc-label">Our Story</span>
        <h2 class="sc-section-title">Redefining Premium Shopping Since Day One</h2>
        <p class="text-secondary">SmartCart was founded on a simple idea: online shopping should feel as refined and trustworthy as stepping into a curated boutique. We bring together premium electronics, fashion, beauty and lifestyle products from brands we believe in — all in one seamless, secure destination.</p>
        <p class="text-secondary">Every product on SmartCart is selected with care, every order handled with attention to detail, and every customer treated as part of our community.</p>
      </div>
      <div class="col-lg-6">
        <img src="<?= BASE_URL ?>assets/images/hero.svg" class="img-fluid rounded" style="max-height:420px; width:100%; object-fit:cover" alt="About SmartCart">
      </div>
    </div>
  </div>
</section>

<section class="sc-section sc-section-sm bg-sc-charcoal">
  <div class="sc-container-fluid">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="sc-card h-100">
          <i class="bi bi-bullseye fs-2 text-gold mb-3 d-block"></i>
          <h5>Our Mission</h5>
          <p class="text-secondary small mb-0">To make premium, quality products accessible through a shopping experience built on trust, convenience and elegance.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="sc-card h-100">
          <i class="bi bi-eye fs-2 text-gold mb-3 d-block"></i>
          <h5>Our Vision</h5>
          <p class="text-secondary small mb-0">To become the region's most trusted destination for curated, premium online shopping across every category we serve.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="sc-card h-100">
          <i class="bi bi-award fs-2 text-gold mb-3 d-block"></i>
          <h5>Why Choose Us</h5>
          <p class="text-secondary small mb-0">Curated quality, secure checkout, fast delivery, responsive support and a shopping experience designed around you.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="sc-section">
  <div class="sc-container-fluid">
    <span class="sc-label">What We Offer</span>
    <h2 class="sc-section-title mb-4">Services & Features</h2>
    <div class="row g-4">
      <?php
      $features = [
        ['bi-truck', 'Fast & Reliable Delivery', 'Standard and express delivery options with real-time order tracking.'],
        ['bi-shield-check', 'Secure Checkout', 'Bank-grade security practices protect every transaction and account.'],
        ['bi-arrow-repeat', 'Easy Returns', 'Eligible orders can be cancelled or returned hassle-free.'],
        ['bi-headset', 'Dedicated Support', 'Our support team is here to help before and after your purchase.'],
      ];
      foreach ($features as $f): ?>
        <div class="col-6 col-md-3 text-center">
          <i class="bi <?= $f[0] ?> fs-1 text-gold mb-3 d-block"></i>
          <h6><?= $f[1] ?></h6>
          <p class="text-secondary small"><?= $f[2] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
