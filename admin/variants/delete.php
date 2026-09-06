<?php
require_once __DIR__ . '/../../config/config.php'; require_admin(); require_post(); $id = get_id();
$check = $conn->prepare('SELECT COUNT(*) AS total FROM order_items WHERE variant_id = ?'); $check->bind_param('i', $id); $check->execute();
if ($check->get_result()->fetch_assoc()['total'] > 0) set_flash('error', 'Không thể xóa biến thể đã có trong đơn hàng.');
else {
    $imageStmt = $conn->prepare('SELECT variant_image FROM product_variants WHERE id = ?'); $imageStmt->bind_param('i', $id); $imageStmt->execute(); $variant = $imageStmt->get_result()->fetch_assoc();
    $stmt = $conn->prepare('DELETE FROM product_variants WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    delete_uploaded_image($variant['variant_image'] ?? null, 'variants');
    set_flash('success', 'Đã xóa biến thể.');
}
redirect('/admin/variants/index.php');
