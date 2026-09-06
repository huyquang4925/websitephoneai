<?php
require_once __DIR__ . '/../../config/config.php';
require_customer_api();
if (!is_post()) json_response(false, 'Chỉ hỗ trợ POST.', [], 405);

$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
$quantity = max(0, (int) ($_POST['quantity'] ?? 0));
$stmt = $conn->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
$stmt->bind_param('i', $variantId);
$stmt->execute();
$variant = $stmt->get_result()->fetch_assoc();
if (!$variant || $quantity > (int) $variant['stock_quantity']) {
    json_response(false, 'Số lượng không hợp lệ.', [], 422);
}
if ($quantity === 0) unset($_SESSION['cart'][$variantId]);
else $_SESSION['cart'][$variantId] = $quantity;
json_response(true, 'Đã cập nhật giỏ.', ['cart' => $_SESSION['cart'] ?? []]);
