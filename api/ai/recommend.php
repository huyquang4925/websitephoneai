<?php
require_once __DIR__ . '/../../config/config.php';

$budget = max(0, (float) ($_GET['budget'] ?? 0));
$brand = trim($_GET['brand'] ?? '');
$need = trim($_GET['need'] ?? '');

$sql = "SELECT p.id, p.name, b.name AS brand_name, s.ram, s.battery_capacity, s.camera_rear,
    MIN(COALESCE(v.sale_price, v.price)) AS min_price
    FROM products p JOIN brands b ON b.id = p.brand_id
    JOIN product_specs s ON s.product_id = p.id
    JOIN product_variants v ON v.product_id = p.id
    WHERE p.is_active = 1";
$params = [];
$types = '';

if ($brand !== '') {
    $sql .= ' AND b.slug = ?';
    $params[] = $brand;
    $types .= 's';
}

$sql .= ' GROUP BY p.id, p.name, b.name, s.ram, s.battery_capacity, s.camera_rear';
if ($budget > 0) {
    $sql .= ' HAVING min_price <= ?';
    $params[] = $budget;
    $types .= 'd';
}

if ($need === 'pin') {
    $sql .= ' ORDER BY s.battery_capacity DESC';
} elseif ($need === 'hieu-nang') {
    $sql .= ' ORDER BY s.ram DESC';
} else {
    $sql .= ' ORDER BY min_price ASC';
}
$sql .= ' LIMIT 5';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
json_response(true, 'Gợi ý theo tiêu chí, không sử dụng AI.', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
