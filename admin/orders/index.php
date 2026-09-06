<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
$orders = $conn->query('SELECT * FROM orders ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Đơn hàng';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Đơn hàng</h1>
        <p>Kiểm tra và cập nhật đơn từ khách hàng.</p>
    </div>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã</th>
                <th>Khách hàng</th>
                <th>Số điện thoại</th>
                <th>Tổng</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?= $order['id'] ?></strong></td>
                <td><?= e($order['receiver_name']) ?></td>
                <td><?= e($order['receiver_phone']) ?></td>
                <td><strong><?= format_price($order['total_amount']) ?></strong></td>
                <td><span class="badge badge-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td>
                <td><a class="btn btn-outline btn-small" href="detail.php?id=<?= $order['id'] ?>">Chi tiết</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

