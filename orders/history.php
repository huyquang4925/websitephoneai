<?php
require_once __DIR__ . '/../config/config.php';
require_customer();

$stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Lịch sử đơn hàng';
$pageCss = ['cart'];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Lịch sử đơn hàng</h1><p>Theo dõi các đơn hàng đã đặt.</p></div><a class="btn btn-outline" href="<?= BASE_URL ?>/index.php">Tiếp tục mua sắm</a></div>
<?php if ($orders): ?><div class="table-wrap"><table>
<tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr>
<?php foreach ($orders as $order): ?>
<tr><td><strong>#<?= $order['id'] ?></strong></td><td><?= e($order['created_at']) ?></td><td><strong><?= format_price($order['total_amount']) ?></strong></td><td><span class="badge badge-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td><td><a class="btn btn-outline btn-small" href="detail.php?id=<?= $order['id'] ?>">Chi tiết</a></td></tr>
<?php endforeach; ?>
</table></div><?php else: ?><div class="empty-state"><h2>Chưa có đơn hàng</h2><p>Đơn hàng đã đặt sẽ xuất hiện tại đây.</p><a class="btn" href="<?= BASE_URL ?>/index.php">Mua sắm ngay</a></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
