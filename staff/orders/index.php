<?php
require_once __DIR__ . '/../../config/config.php';
require_staff();
$orders = $conn->query('SELECT * FROM orders ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Quản lý đơn hàng';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1>Quản lý đơn hàng</h1><p><?= count($orders) ?> đơn hàng trong hệ thống.</p></div></div>
<div class="table-wrap"><table><tr><th>Mã</th><th>Khách hàng</th><th>Số điện thoại</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr>
<?php foreach ($orders as $order): ?><tr><td><strong>#<?= $order['id'] ?></strong></td><td><?= e($order['receiver_name']) ?></td><td><?= e($order['receiver_phone']) ?></td><td><strong><?= format_price($order['total_amount']) ?></strong></td><td><span class="badge badge-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td><td><a class="btn btn-outline btn-small" href="detail.php?id=<?= $order['id'] ?>">Chi tiết</a></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
