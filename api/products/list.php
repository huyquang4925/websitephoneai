<?php
require_once __DIR__ . '/../../config/config.php';

$result = $conn->query("SELECT p.id, p.name, p.slug, p.thumbnail, b.name AS brand_name,
    MIN(COALESCE(v.sale_price, v.price)) AS min_price
    FROM products p JOIN brands b ON b.id = p.brand_id
    LEFT JOIN product_variants v ON v.product_id = p.id
    WHERE p.is_active = 1 GROUP BY p.id, p.name, p.slug, p.thumbnail, b.name ORDER BY p.id DESC");

json_response(true, 'Lấy danh sách thành công.', $result->fetch_all(MYSQLI_ASSOC));
