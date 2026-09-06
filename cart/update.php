<?php
require_once __DIR__ . '/../config/config.php';
require_customer();
require_post();

$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
$quantity = max(0, (int) ($_POST['quantity'] ?? 0));
$stmt = $conn->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
$stmt->bind_param('i', $variantId);
$stmt->execute();
$variant = $stmt->get_result()->fetch_assoc();

if (!$variant || $quantity > (int) $variant['stock_quantity']) {
    set_flash('error', 'Số lượng không hợp lệ hoặc vượt tồn kho.');
} elseif ($quantity === 0) {
    unset($_SESSION['cart'][$variantId]);
    set_flash('success', 'Đã xóa sản phẩm khỏi giỏ.');
} else {
    $_SESSION['cart'][$variantId] = $quantity;
    set_flash('success', 'Đã cập nhật giỏ hàng.');
}
redirect('/cart/index.php');
