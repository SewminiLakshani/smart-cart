<?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<ul class="nav nav-pills mb-4 gap-2">
  <li><a class="btn btn-sm <?= $cur==='sales.php'?'btn-sc-gold':'btn-sc-dark' ?>" href="<?= BASE_URL ?>admin/reports/sales.php">Sales</a></li>
  <li><a class="btn btn-sm <?= $cur==='products.php'?'btn-sc-gold':'btn-sc-dark' ?>" href="<?= BASE_URL ?>admin/reports/products.php">Products</a></li>
  <li><a class="btn btn-sm <?= $cur==='customers.php'?'btn-sc-gold':'btn-sc-dark' ?>" href="<?= BASE_URL ?>admin/reports/customers.php">Customers</a></li>
  <li><a class="btn btn-sm <?= $cur==='orders.php'?'btn-sc-gold':'btn-sc-dark' ?>" href="<?= BASE_URL ?>admin/reports/orders.php">Orders</a></li>
</ul>
