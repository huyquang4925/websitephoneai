<?php
require_once __DIR__ . '/../../config/config.php';
require_staff(); require_post();
$id = get_id(); $status = post_value('status');
if (!$id || !valid_order_status($status)) { set_flash('error', 'Dữ liệu không hợp lệ.'); redirect('/staff/orders/index.php'); }
$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?'); $stmt->bind_param('si', $status, $id); $stmt->execute();
set_flash('success', 'Đã cập nhật trạng thái đơn hàng.'); redirect('/staff/orders/detail.php?id=' . $id);
