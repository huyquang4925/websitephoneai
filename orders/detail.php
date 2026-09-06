<?php
require_once __DIR__ . '/../config/config.php';
require_customer();
$id = get_id();

$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}
$items = get_order_items($conn, $id);
$pageTitle = 'Chi tiết đơn hàng';
$pageCss = ['cart'];
require __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb"><a href="history.php">Đơn hàng</a> / #<?= $order['id'] ?></div>
<div class="page-header"><div><h1>Đơn hàng #<?= $order['id'] ?></h1><p>Đặt lúc <?= e($order['created_at']) ?></p></div><span class="badge badge-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span></div>
<section class="order-info"><div class="card"><small>Người nhận</small><strong><?= e($order['receiver_name']) ?></strong></div><div class="card"><small>Số điện thoại</small><strong><?= e($order['receiver_phone']) ?></strong></div><div class="card"><small>Địa chỉ</small><strong><?= e($order['receiver_address']) ?></strong></div></section>
<div class="table-wrap"><table><tr><th>Sản phẩm</th><th>Phiên bản</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr>
<?php foreach ($items as $item): ?><tr><td><strong><?= e($item['product_name']) ?></strong></td><td><?= e($item['color']) ?> - <?= e($item['storage']) ?></td><td><?= $item['quantity'] ?></td><td><?= format_price($item['unit_price']) ?></td><td><strong><?= format_price($item['quantity'] * $item['unit_price']) ?></strong></td></tr><?php endforeach; ?></table></div>
<aside class="card cart-summary"><div class="summary-line"><span>Tổng thanh toán</span><strong class="summary-total"><?= format_price($order['total_amount']) ?></strong></div></aside>
<?php require __DIR__ . '/../includes/footer.php'; ?>
