<footer class="sc-footer">
  <div class="sc-container-fluid">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="navbar-brand d-inline-block mb-3" href="<?= BASE_URL ?>index.php">Smart<span class="text-gold">Cart</span></a>
        <p class="small">A curated collection of premium products designed to elevate your everyday experience. Quality, elegance and trust — delivered to your door.</p>
        <div class="mt-3">
          <a href="#" class="sc-social"><i class="bi bi-facebook"></i></a>
          <a href="#" class="sc-social"><i class="bi bi-instagram"></i></a>
          <a href="#" class="sc-social"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="sc-social"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Quick Links</h6>
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>products/shop.php">Shop</a></li>
          <li><a href="<?= BASE_URL ?>about.php">About Us</a></li>
          <li><a href="<?= BASE_URL ?>contact.php">Contact Us</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Categories</h6>
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="<?= BASE_URL ?>products/shop.php?category=electronics">Electronics</a></li>
          <li><a href="<?= BASE_URL ?>products/shop.php?category=fashion">Fashion</a></li>
          <li><a href="<?= BASE_URL ?>products/shop.php?category=beauty">Beauty</a></li>
          <li><a href="<?= BASE_URL ?>products/shop.php?category=home-living">Home & Living</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6>Contact</h6>
        <ul class="list-unstyled d-flex flex-column gap-2 small">
          <li><i class="bi bi-geo-alt text-gold me-2"></i>142 Galle Road, Colombo 03, Sri Lanka</li>
          <li><i class="bi bi-envelope text-gold me-2"></i>support@smartcart.com</li>
          <li><i class="bi bi-telephone text-gold me-2"></i>+94 11 234 5678</li>
        </ul>
      </div>
    </div>
    <div class="sc-footer-bottom d-flex flex-wrap justify-content-between">
      <span>&copy; <?= date('Y') ?> SmartCart. All rights reserved. — HNDIT Final Project</span>
      <span>Built with PHP · MySQL · Bootstrap 5</span>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SmartCart global script -->
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>
