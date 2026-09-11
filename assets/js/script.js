/* =====================================================================
   SMARTCART — Global JavaScript
   ===================================================================== */
(function () {
  'use strict';

  const BASE_URL = document.body.dataset.baseUrl || '/smartcart/';
  const NAV_OFFSET = 90; // accounts for the sticky navbar height + a little breathing room

  /* ---------------- Smooth, offset-aware scroll to any #anchor ---------------- */
  function scrollToHash(hash, smooth) {
    if (!hash) return;
    let el;
    try { el = document.querySelector(hash); } catch (err) { return; }
    if (!el) return;
    const top = el.getBoundingClientRect().top + window.pageYOffset - NAV_OFFSET;
    window.scrollTo({ top: Math.max(0, top), behavior: smooth ? 'smooth' : 'auto' });
  }

  /* ---------------- Resolve any pending #anchor once everything (incl. images) has finished loading ---------------- */
  window.addEventListener('load', function () {
    if (window.__scPendingHash) {
      const hash = window.__scPendingHash;
      history.replaceState(null, '', window.location.pathname + window.location.search + hash);
      if (typeof syncCategoriesNavActive === 'function') syncCategoriesNavActive();
      setTimeout(() => scrollToHash(hash, true), 50);
      window.__scPendingHash = null;
    }
  });

  // If a page is restored from the back/forward cache mid-fade, make sure it's visible again.
  window.addEventListener('pageshow', function () {
    document.body.classList.remove('sc-page-leaving');
  });

  /* ---------------- Smooth fade-out transition before internal navigation ---------------- */
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link) return;
    if (link.target === '_blank' || link.hasAttribute('download') || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const href = link.getAttribute('href') || '';
    if (href === '' || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
    if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('data-bs-dismiss')) return;

    let url;
    try { url = new URL(link.href, window.location.href); } catch (err) { return; }
    if (url.origin !== window.location.origin) return; // let external links behave normally

    if (url.pathname === window.location.pathname && url.hash) {
      // Same-page anchor link (e.g. clicking "Categories" while already on the homepage) — smooth scroll, no reload.
      const target = document.querySelector(url.hash);
      if (target) {
        e.preventDefault();
        history.pushState(null, '', url.hash);
        if (typeof syncCategoriesNavActive === 'function') syncCategoriesNavActive();
        scrollToHash(url.hash, true);
      }
      return;
    }

    // Real navigation to a different page (or the same page without a hash) — fade out, then go.
    e.preventDefault();
    document.body.classList.add('sc-page-leaving');
    setTimeout(() => { window.location.href = link.href; }, 200);
  });

  // Fade out on form submissions too (login, checkout, etc.) — but only if nothing cancelled the submit
  // (e.g. client-side validation failing). Capture phase + deferred check so this works correctly even
  // when a form's own handler calls stopPropagation() (like our .needs-validation forms do).
  document.addEventListener('submit', function (e) {
    if (e.target.target === '_blank') return;
    setTimeout(function () {
      if (!e.defaultPrevented) document.body.classList.add('sc-page-leaving');
    }, 0);
  }, true);

  /* ---------------- Toast helper ---------------- */
  function showToast(message, type = 'success') {
    let container = document.querySelector('.sc-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'sc-toast-container';
      document.body.appendChild(container);
    }
    const id = 'toast-' + Date.now();
    const icon = type === 'success' ? 'bi-check-circle-fill' : type === 'danger' ? 'bi-x-circle-fill' : 'bi-info-circle-fill';
    const toastHtml = `
      <div id="${id}" class="toast align-items-center border-0 mb-2" role="alert" data-bs-delay="3200">
        <div class="d-flex">
          <div class="toast-body"><i class="bi ${icon} text-gold me-2"></i>${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`;
    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }
  window.scToast = showToast;

  /* ---------------- Generic AJAX POST helper ---------------- */
  async function ajaxPost(url, data) {
    const formData = new URLSearchParams(data);
    const res = await fetch(BASE_URL + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    });
    return res.json();
  }
  window.scAjaxPost = ajaxPost;

  /* ---------------- CSRF token (read from meta) ---------------- */
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  /* ---------------- Add to Cart ---------------- */
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-add-cart');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    const qtyInput = document.querySelector(`.js-qty-input[data-for="${productId}"]`);
    const quantity = qtyInput ? qtyInput.value : (btn.dataset.qty || 1);
    btn.disabled = true;
    try {
      const resp = await ajaxPost('ajax/cart_add.php', { product_id: productId, quantity, csrf_token: csrfToken() });
      if (resp.success) {
        showToast(resp.message || 'Added to cart', 'success');
        const countEl = document.querySelector('.js-cart-count');
        if (countEl && typeof resp.cart_count !== 'undefined') {
          countEl.textContent = resp.cart_count;
          countEl.style.display = resp.cart_count > 0 ? 'flex' : 'none';
        }
      } else {
        showToast(resp.message || 'Could not add to cart', 'danger');
        if (resp.login_required) window.location.href = BASE_URL + 'auth/login.php';
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'danger');
    } finally {
      btn.disabled = false;
    }
  });

  /* ---------------- Wishlist toggle ---------------- */
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-wishlist-toggle');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    try {
      const resp = await ajaxPost('ajax/wishlist_toggle.php', { product_id: productId, csrf_token: csrfToken() });
      if (resp.success) {
        btn.classList.toggle('active', resp.action === 'added');
        const icon = btn.querySelector('i');
        if (icon) icon.className = resp.action === 'added' ? 'bi bi-heart-fill' : 'bi bi-heart';
        showToast(resp.message, 'success');
        const countEl = document.querySelector('.js-wishlist-count');
        if (countEl && typeof resp.wishlist_count !== 'undefined') {
          countEl.textContent = resp.wishlist_count;
          countEl.style.display = resp.wishlist_count > 0 ? 'flex' : 'none';
        }
        if (btn.dataset.removeOnToggle === '1' && resp.action === 'removed') {
          const card = btn.closest('.js-wishlist-item');
          if (card) card.remove();
        }
      } else {
        showToast(resp.message || 'Please log in first', 'danger');
        if (resp.login_required) window.location.href = BASE_URL + 'auth/login.php';
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'danger');
    }
  });

  /* ---------------- Quantity stepper (generic, works on any .sc-quantity) ---------------- */
  document.addEventListener('click', function (e) {
    const stepBtn = e.target.closest('.js-qty-step');
    if (!stepBtn) return;
    const wrap = stepBtn.closest('.sc-quantity');
    const input = wrap.querySelector('input');
    let val = parseInt(input.value || '1', 10);
    const min = parseInt(input.min || '1', 10);
    const max = parseInt(input.max || '99999', 10);
    val = stepBtn.dataset.dir === 'up' ? Math.min(max, val + 1) : Math.max(min, val - 1);
    input.value = val;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* ---------------- Cart page: update quantity / remove via AJAX ---------------- */
  document.addEventListener('change', async function (e) {
    const input = e.target.closest('.js-cart-qty');
    if (!input) return;
    const itemId = input.dataset.itemId;
    const resp = await ajaxPost('ajax/cart_update.php', { cart_item_id: itemId, quantity: input.value, csrf_token: csrfToken() });
    if (resp.success) {
      document.querySelectorAll('.js-cart-subtotal').forEach((el) => { if (el.dataset.itemId === itemId) el.textContent = resp.line_total; });
      const summary = document.querySelector('.js-order-summary');
      if (summary && resp.summary_html) summary.innerHTML = resp.summary_html;
      const countEl = document.querySelector('.js-cart-count');
      if (countEl) countEl.textContent = resp.cart_count;
    } else {
      showToast(resp.message || 'Could not update cart', 'danger');
    }
  });

  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-cart-remove');
    if (!btn) return;
    e.preventDefault();
    const itemId = btn.dataset.itemId;
    const resp = await ajaxPost('ajax/cart_remove.php', { cart_item_id: itemId, csrf_token: csrfToken() });
    if (resp.success) {
      const row = document.querySelector(`.js-cart-row[data-item-id="${itemId}"]`);
      if (row) row.remove();
      showToast('Item removed from cart', 'success');
      const countEl = document.querySelector('.js-cart-count');
      if (countEl) countEl.textContent = resp.cart_count;
      const summary = document.querySelector('.js-order-summary');
      if (summary && resp.summary_html) summary.innerHTML = resp.summary_html;
      if (resp.cart_count == 0) window.location.reload();
    }
  });

  /* ---------------- Apply coupon (checkout) ---------------- */
  const couponForm = document.querySelector('.js-coupon-form');
  if (couponForm) {
    couponForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const code = couponForm.querySelector('input[name="coupon_code"]').value;
      const resp = await ajaxPost('ajax/apply_coupon.php', { coupon_code: code, csrf_token: csrfToken() });
      const msgBox = document.querySelector('.js-coupon-message');
      if (msgBox) {
        msgBox.textContent = resp.message;
        msgBox.className = 'js-coupon-message small mt-2 ' + (resp.success ? 'text-success' : 'text-danger');
      }
      if (resp.success) {
        const summary = document.querySelector('.js-order-summary');
        if (summary && resp.summary_html) summary.innerHTML = resp.summary_html;
      }
    });
  }

  /* ---------------- Quick View modal ---------------- */
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-quick-view');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    const modalEl = document.getElementById('quickViewModal');
    const body = modalEl.querySelector('.modal-body');
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-sc mx-auto"></div></div>';
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    try {
      const res = await fetch(BASE_URL + 'ajax/quick_view.php?product_id=' + encodeURIComponent(productId));
      body.innerHTML = await res.text();
    } catch (err) {
      body.innerHTML = '<p class="text-danger p-4">Could not load product details.</p>';
    }
  });

  /* ---------------- Live search suggestions ---------------- */
  const searchInput = document.querySelector('.js-search-input');
  if (searchInput) {
    let debounceTimer;
    const box = document.querySelector('.js-search-suggestions');
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      const q = searchInput.value.trim();
      if (q.length < 2) { if (box) box.innerHTML = ''; return; }
      debounceTimer = setTimeout(async () => {
        const res = await fetch(BASE_URL + 'ajax/search_suggest.php?q=' + encodeURIComponent(q));
        const html = await res.text();
        if (box) box.innerHTML = html;
      }, 300);
    });
    document.addEventListener('click', function (e) {
      if (box && !searchInput.contains(e.target) && !box.contains(e.target)) box.innerHTML = '';
    });
  }

  /* ---------------- Flash sale countdown ---------------- */
  document.querySelectorAll('.js-countdown').forEach(function (el) {
    const end = new Date(el.dataset.end).getTime();
    function tick() {
      const now = Date.now();
      let diff = Math.max(0, end - now);
      const d = Math.floor(diff / 86400000); diff -= d * 86400000;
      const h = Math.floor(diff / 3600000); diff -= h * 3600000;
      const m = Math.floor(diff / 60000); diff -= m * 60000;
      const s = Math.floor(diff / 1000);
      const set = (sel, val) => { const t = el.querySelector(sel); if (t) t.textContent = String(val).padStart(2, '0'); };
      set('.cd-d', d); set('.cd-h', h); set('.cd-m', m); set('.cd-s', s);
      if (end - now <= 0) { clearInterval(timer); el.closest('.js-flash-wrapper')?.classList.add('d-none'); }
    }
    tick();
    const timer = setInterval(tick, 1000);
  });

  /* ---------------- Password visibility toggle ---------------- */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-toggle-password');
    if (!btn) return;
    const input = document.querySelector(btn.dataset.target);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });

  /* ---------------- Bootstrap client-side validation ---------------- */
  document.querySelectorAll('.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  /* ---------------- Confirmation dialogs (delete etc.) ---------------- */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-confirm');
    if (!btn) return;
    const msg = btn.dataset.confirm || 'Are you sure?';
    if (!confirm(msg)) e.preventDefault();
  });

  /* ---------------- Mobile sidebar toggle (dashboards) ---------------- */
  const sidebarToggle = document.querySelector('.js-sidebar-toggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      document.querySelector('.sc-sidebar')?.classList.toggle('show');
    });
  }

  /* ---------------- Star rating input (reviews) ---------------- */
  document.querySelectorAll('.js-star-input').forEach(function (wrap) {
    const stars = wrap.querySelectorAll('i');
    const hidden = wrap.parentElement.querySelector('input[name="rating"]');
    stars.forEach(function (star, idx) {
      star.addEventListener('click', function () {
        hidden.value = idx + 1;
        stars.forEach((s, i) => s.className = i <= idx ? 'bi bi-star-fill' : 'bi bi-star');
      });
    });
  });

  /* ---------------- Highlight "Categories" nav link when on #categories (and un-highlight Home) ---------------- */
  function syncCategoriesNavActive() {
    const onCategories = window.location.hash === '#categories';
    document.querySelectorAll('.js-nav-categories').forEach(function (link) {
      link.classList.toggle('active', onCategories);
    });
    document.querySelectorAll('.js-nav-home').forEach(function (link) {
      const serverActive = link.dataset.serverActive === '1';
      link.classList.toggle('active', serverActive && !onCategories);
    });
  }
  syncCategoriesNavActive();
  window.addEventListener('hashchange', syncCategoriesNavActive);

  /* ---------------- Navbar scroll shadow ---------------- */
  const nav = document.querySelector('.sc-navbar');
  if (nav) {
    window.addEventListener('scroll', function () {
      nav.style.boxShadow = window.scrollY > 10 ? '0 6px 20px rgba(0,0,0,.35)' : 'none';
    });
  }
})();
