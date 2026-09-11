<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = ['1=1']; $params = [];
if ($status) { $where[] = 'o.order_status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = '(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)'; $like = "%$q%"; array_push($params, $like, $like, $like); }
$whereSql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.user_id = o.user_id WHERE $whereSql");
$total->execute($params);
$total = (int)$total->fetchColumn();
$pg = paginate($total, $page, $perPage);

$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.user_id = o.user_id
                        WHERE $whereSql ORDER BY o.created_at DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <form method="get" class="d-flex flex-wrap gap-2 mb-3">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Search order # / customer" value="<?= e($q) ?>" style="width:240px">
      <select name="status" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sc-dark btn-sm">Filter</button>
    </form>

    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td class="small"><?= e($o['full_name']) ?><br><span class="text-secondary" style="font-size:11px"><?= e($o['email']) ?></span></td>
              <td class="small text-secondary"><?= friendly_date($o['created_at'], 'M d, Y') ?></td>
              <td><?= money($o['total_amount']) ?></td>
              <td class="text-uppercase small"><?= $o['payment_method'] ?></td>
              <td><?= order_status_badge($o['order_status']) ?></td>
              <td><a href="<?= BASE_URL ?>admin/orders/details.php?id=<?= $o['order_id'] ?>" class="text-gold small">Manage</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?><tr><td colspan="7" class="text-center text-secondary py-4">No orders found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3"><?= render_pagination($pg['currentPage'], $pg['totalPages'], $status ? '&status=' . $status : '') ?></div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
