<?php
require_once __DIR__ . '/../../config/config.php';
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')))));
$ids = array_slice($ids, 0, 4);
if (count($ids) < 2) {
    json_response(false, 'Cần ít nhất 2 ID sản phẩm.', [], 422);
}

$products = [];
$stmt = $conn->prepare('SELECT p.id, p.name, b.name AS brand_name, s.* FROM products p JOIN brands b ON b.id = p.brand_id LEFT JOIN product_specs s ON s.product_id = p.id WHERE p.id = ? AND p.is_active = 1');
foreach ($ids as $id) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product) {
        $products[] = $product;
    }
}
json_response(true, 'Lấy dữ liệu so sánh thành công.', $products);
