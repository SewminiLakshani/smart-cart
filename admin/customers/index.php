<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE users SET status = IF(status='active','banned','active') WHERE user_id = ? AND role_id = 1")->execute([(int)$_GET['toggle']]);
    set_flash('success', 'Customer status updated.');
    redirect(BASE_URL . 'admin/customers/index.php');
}

$q = trim($_GET['q'] ?? '');
$where = "role_id = 1"; $params = [];
if ($q !== '') { $where .= " AND (full_name LIKE ? OR email LIKE ?)"; $like = "%$q%"; array_push($params, $like, $like); }

$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS order_count,
                        (SELECT COALESCE(SUM(total_amount),0) FROM orders o WHERE o.user_id = u.user_id AND o.order_status != 'cancelled') AS total_spent
                        FROM users u WHERE $where ORDER BY u.created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <form method="get" class="mb-3"><input type="text" name="q" class="form-control form-control-sm" style="width:260px" placeholder="Search by name or email" value="<?= e($q) ?>"></form>
    <div class="sc-table-wrap">
      <table class="table align-middle mb-0">
        <thead class="sc-thead"><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><?= e($c['full_name']) ?></td>
              <td class="small text-secondary"><?= e($c['email']) ?></td>
              <td class="small"><?= e($c['phone']) ?></td>
              <td><?= (int)$c['order_count'] ?></td>
              <td><?= money($c['total_spent']) ?></td>
              <td><span class="badge sc-badge bg-<?= $c['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/customers/details.php?id=<?= $c['user_id'] ?>" class="text-gold small">View</a>
                <a href="?toggle=<?= $c['user_id'] ?>" class="text-secondary small js-confirm" data-confirm="<?= $c['status'] === 'active' ? 'Ban' : 'Reactivate' ?> this customer?"><?= $c['status'] === 'active' ? 'Ban' : 'Unban' ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($customers)): ?><tr><td colspan="7" class="text-center text-secondary py-4">No customers found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
