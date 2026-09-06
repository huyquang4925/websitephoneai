<?php
require_once __DIR__ . '/../../config/config.php';
require_staff();
$id = get_id();
$stmt = $conn->prepare('SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?');
$stmt->bind_param('i', $id); $stmt->execute(); $order = $stmt->get_result()->fetch_assoc();
if (!$order) { http_response_code(404); exit('Không tìm thấy đơn hàng.'); }
$items = get_order_items($conn, $id);
$pageTitle = 'Chi tiết đơn hàng'; require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1>Đơn hàng #<?= $id ?></h1><p><?= e($order['receiver_name']) ?> - <?= e($order['receiver_phone']) ?></p></div><span class="badge badge-<?= e($order['status']) ?>"><?= order_status_label($order['status']) ?></span></div>
<div class="card"><p><strong>Địa chỉ giao hàng:</strong> <?= e($order['receiver_address']) ?></p></div>
<form class="status-form card" action="update_status.php" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-group">
        <label>Trạng thái đơn hàng</label>
        <select name="status">
            <?php foreach (['pending','confirmed','shipping','completed','cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= order_status_label($status) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Cập nhật</button>
    <a class="btn btn-outline" href="index.php">Thoát</a>
</form>
<div class="table-wrap"><table><tr><th>Sản phẩm</th><th>Phiên bản</th><th>SL</th><th>Đơn giá</th></tr><?php foreach ($items as $item): ?><tr><td><strong><?= e($item['product_name']) ?></strong></td><td><?= e($item['color']) ?> - <?= e($item['storage']) ?></td><td><?= $item['quantity'] ?></td><td><?= format_price($item['unit_price']) ?></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
