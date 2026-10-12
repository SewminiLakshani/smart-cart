<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
$pdo = db();

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') $pdo->prepare("UPDATE reviews SET status='approved' WHERE review_id=?")->execute([$id]);
    if ($_GET['action'] === 'hide') $pdo->prepare("UPDATE reviews SET status='hidden' WHERE review_id=?")->execute([$id]);
    if ($_GET['action'] === 'delete') {
        $r = $pdo->prepare("SELECT product_id FROM reviews WHERE review_id=?"); $r->execute([$id]); $pid = $r->fetchColumn();
        $pdo->prepare("DELETE FROM reviews WHERE review_id=?")->execute([$id]);
        if ($pid) {
            $agg = $pdo->prepare("SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE product_id=? AND status='approved'"); $agg->execute([$pid]); $a = $agg->fetch();
            $pdo->prepare("UPDATE products SET avg_rating=?, review_count=? WHERE product_id=?")->execute([round($a['a'] ?? 0, 2), $a['c'] ?? 0, $pid]);
        }
    }
    redirect(BASE_URL . 'admin/reviews/index.php');
}

$filter = $_GET['status'] ?? '';
$where = '1=1'; $params = [];
if ($filter) { $where = 'r.status = ?'; $params[] = $filter; }

$stmt = $pdo->prepare("SELECT r.*, u.full_name, p.product_name FROM reviews r
                        JOIN users u ON u.user_id = r.user_id JOIN products p ON p.product_id = r.product_id
                        WHERE $where ORDER BY r.created_at DESC");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$pageTitle = 'Reviews';
require __DIR__ . '/../../includes/header.php';
?>
<div class="sc-dash-wrap">
  <?php require __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="sc-content">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>
    <div class="d-flex gap-2 mb-3">
      <a href="?" class="btn btn-sc-dark btn-sm">All</a>
      <a href="?status=approved" class="btn btn-sc-dark btn-sm">Approved</a>
      <a href="?status=hidden" class="btn btn-sc-dark btn-sm">Hidden</a>
    </div>
    <?php foreach ($reviews as $r): ?>
      <div class="sc-card mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2">
          <div>
            <strong><?= e($r['full_name']) ?></strong> reviewed <strong class="text-gold"><?= e($r['product_name']) ?></strong>
            <div class="text-gold small"><?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $i<=$r['rating']?'-fill':'' ?>"></i><?php endfor; ?></div>
          </div>
          <div>
            <span class="badge sc-badge bg-<?= $r['status']==='approved'?'success':'secondary' ?>"><?= ucfirst($r['status']) ?></span>
          </div>
        </div>
        <p class="text-secondary mt-2 mb-2"><?= e($r['review_text']) ?></p>
        <div class="d-flex gap-3 small">
          <span class="text-secondary"><?= friendly_date($r['created_at']) ?></span>
          <?php if ($r['status'] !== 'approved'): ?><a href="?action=approve&id=<?= $r['review_id'] ?>" class="text-success">Approve</a><?php endif; ?>
          <?php if ($r['status'] !== 'hidden'): ?><a href="?action=hide&id=<?= $r['review_id'] ?>" class="text-warning">Hide</a><?php endif; ?>
          <a href="?action=delete&id=<?= $r['review_id'] ?>" class="text-danger js-confirm" data-confirm="Delete this review?">Delete</a>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?><div class="sc-card text-center py-5 text-secondary">No reviews found.</div><?php endif; ?>
  </main>
</div>
<?php require __DIR__ . '/../../includes/dash-footer.php'; ?>
