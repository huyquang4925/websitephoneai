<?php
require_once __DIR__ . '/../../config/config.php';
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    json_response(false, 'Vui lòng nhập từ khóa.', [], 422);
}

$keyword = '%' . $q . '%';
$stmt = $conn->prepare("SELECT p.id, p.name, p.slug, b.name AS brand_name, MIN(COALESCE(v.sale_price, v.price)) AS min_price
    FROM products p JOIN brands b ON b.id = p.brand_id LEFT JOIN product_variants v ON v.product_id = p.id
    WHERE p.is_active = 1 AND (p.name LIKE ? OR b.name LIKE ?)
    GROUP BY p.id, p.name, p.slug, b.name ORDER BY p.name");
$stmt->bind_param('ss', $keyword, $keyword);
$stmt->execute();
json_response(true, 'Tìm kiếm thành công.', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
