<?php
require_once __DIR__ . '/../../config/config.php';
require_customer_api();
if (!is_post()) json_response(false, 'Chỉ hỗ trợ POST.', [], 405);

$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$stmt = $conn->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
$stmt->bind_param('i', $variantId);
$stmt->execute();
$variant = $stmt->get_result()->fetch_assoc();
$current = (int) ($_SESSION['cart'][$variantId] ?? 0);

if (!$variant || $current + $quantity > (int) $variant['stock_quantity']) {
    json_response(false, 'Không đủ tồn kho.', [], 422);
}
$_SESSION['cart'][$variantId] = $current + $quantity;
json_response(true, 'Đã thêm vào giỏ.', ['cart' => $_SESSION['cart']]);
