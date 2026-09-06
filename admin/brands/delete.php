<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
require_post();
$id = get_id();

$check = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE brand_id = ?');
$check->bind_param('i', $id);
$check->execute();

if ($check->get_result()->fetch_assoc()['total'] > 0) {
    set_flash('error', 'Không thể xóa thương hiệu đang có sản phẩm.');
} else {
    $brandStmt = $conn->prepare('SELECT logo_url FROM brands WHERE id = ?');
    $brandStmt->bind_param('i', $id);
    $brandStmt->execute();
    $brand = $brandStmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare('DELETE FROM brands WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    delete_uploaded_image($brand['logo_url'] ?? null, 'brands');
    set_flash('success', 'Đã xóa thương hiệu.');
}
redirect('/admin/brands/index.php');
