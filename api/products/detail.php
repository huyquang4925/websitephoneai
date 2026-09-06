<?php
require_once __DIR__ . '/../../config/config.php';
$id = get_id();

$stmt = $conn->prepare('SELECT p.*, b.name AS brand_name FROM products p JOIN brands b ON b.id = p.brand_id WHERE p.id = ? AND p.is_active = 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    json_response(false, 'Không tìm thấy sản phẩm.', [], 404);
}

$stmt = $conn->prepare('SELECT * FROM product_specs WHERE product_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$product['specs'] = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY price');
$stmt->bind_param('i', $id);
$stmt->execute();
$product['variants'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

json_response(true, 'Lấy sản phẩm thành công.', $product);
