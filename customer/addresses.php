<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('customer');
$user = current_user();
$pdo = db();
$uid = $user['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addressLine = trim($_POST['address_line'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postal = trim($_POST['postal_code'] ?? '');
        $type = in_array($_POST['address_type'] ?? '', ['home','office','other']) ? $_POST['address_type'] : 'home';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        if (strlen($fullName) < 2 || !is_valid_phone($phone) || $addressLine === '' || $city === '' || $province === '') {
            $errors[] = 'Please fill in all required address fields correctly.';
        } else {
            if ($isDefault) {
                $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$uid]);
            }
            if ($addressId > 0) {
                $stmt = $pdo->prepare("UPDATE addresses SET full_name=?, phone=?, address_line=?, city=?, province=?, postal_code=?, address_type=?, is_default=? WHERE address_id=? AND user_id=?");
                $stmt->execute([$fullName, $phone, $addressLine, $city, $province, $postal, $type, $isDefault, $addressId, $uid]);
                set_flash('success', 'Address updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line, city, province, postal_code, address_type, is_default) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$uid, $fullName, $phone, $addressLine, $city, $province, $postal, $type, $isDefault]);
                set_flash('success', 'Address added successfully.');
            }
            redirect(BASE_URL . 'customer/addresses.php');
        }
    } elseif ($action === 'delete') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $pdo->prepare("DELETE FROM addresses WHERE address_id = ? AND user_id = ?")->execute([$addressId, $uid]);
        set_flash('success', 'Address removed.');
        redirect(BASE_URL . 'customer/addresses.php');
    } elseif ($action === 'set_default') {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?")->execute([$addressId, $uid]);
        set_flash('success', 'Default address updated.');
        redirect(BASE_URL . 'customer/addresses.php');
    }
}

$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addresses->execute([$uid]);
$addresses = $addresses->fetchAll();

$editAddress = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM addresses WHERE address_id = ? AND user_id = ?");
    $s->execute([(int)$_GET['edit'], $uid]);
    $editAddress = $s->fetch();
}

$pageTitle = 'My Addresses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <main class="sc-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">My Addresses</h3>
      <button class="btn btn-sc-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="resetAddressForm()">+ Add Address</button>
    </div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger small"><?= e($err) ?></div><?php endforeach; ?>

    <div class="row g-3">
      <?php if (empty($addresses)): ?>
        <div class="col-12"><div class="sc-card text-center py-5 text-secondary">No saved addresses yet. Add one to speed up checkout.</div></div>
      <?php endif; ?>
      <?php foreach ($addresses as $addr): ?>
        <div class="col-md-6">
          <div class="sc-card h-100">
            <div class="d-flex justify-content-between">
              <span class="badge sc-badge bg-secondary text-uppercase"><?= e($addr['address_type']) ?></span>
              <?php if ($addr['is_default']): ?><span class="badge sc-badge bg-warning text-dark">Default</span><?php endif; ?>
            </div>
            <h6 class="mt-2 mb-1"><?= e($addr['full_name']) ?></h6>
            <p class="small text-secondary mb-1"><?= e($addr['phone']) ?></p>
            <p class="small text-secondary mb-3"><?= e($addr['address_line']) ?>, <?= e($addr['city']) ?>, <?= e($addr['province']) ?> <?= e($addr['postal_code']) ?></p>
            <div class="d-flex gap-2">
              <button class="btn btn-sc-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal"
                onclick='fillAddressForm(<?= json_encode($addr) ?>)'>Edit</button>
              <?php if (!$addr['is_default']): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="set_default"><input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                <button class="btn btn-sc-outline btn-sm">Set Default</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Delete this address?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                <button class="btn btn-sc-outline btn-sm text-danger">Delete</button></form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="addressForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="address_id" id="f_address_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="addressModalTitle">Add Address</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label>Full Name</label><input type="text" name="full_name" id="f_full_name" class="form-control" required></div>
          <div class="mb-3"><label>Phone Number</label><input type="tel" name="phone" id="f_phone" class="form-control" required></div>
          <div class="mb-3"><label>Address Line</label><input type="text" name="address_line" id="f_address_line" class="form-control" required></div>
          <div class="row">
            <div class="col-6 mb-3"><label>City</label><input type="text" name="city" id="f_city" class="form-control" required></div>
            <div class="col-6 mb-3"><label>Province</label><input type="text" name="province" id="f_province" class="form-control" required></div>
          </div>
          <div class="row">
            <div class="col-6 mb-3"><label>Postal Code</label><input type="text" name="postal_code" id="f_postal_code" class="form-control"></div>
            <div class="col-6 mb-3"><label>Type</label>
              <select name="address_type" id="f_address_type" class="form-select">
                <option value="home">Home</option><option value="office">Office</option><option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="is_default" id="f_is_default"><label class="form-check-label" for="f_is_default">Set as default address</label></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-sc-gold w-100">Save Address</button></div>
      </form>
    </div>
  </div>
</div>
<script>
function resetAddressForm() {
  document.getElementById('addressModalTitle').textContent = 'Add Address';
  document.getElementById('addressForm').reset();
  document.getElementById('f_address_id').value = '';
}
function fillAddressForm(a) {
  document.getElementById('addressModalTitle').textContent = 'Edit Address';
  document.getElementById('f_address_id').value = a.address_id;
  document.getElementById('f_full_name').value = a.full_name;
  document.getElementById('f_phone').value = a.phone;
  document.getElementById('f_address_line').value = a.address_line;
  document.getElementById('f_city').value = a.city;
  document.getElementById('f_province').value = a.province;
  document.getElementById('f_postal_code').value = a.postal_code || '';
  document.getElementById('f_address_type').value = a.address_type;
  document.getElementById('f_is_default').checked = a.is_default == 1;
}
</script>
<?php require __DIR__ . '/../includes/dash-footer.php'; ?>
