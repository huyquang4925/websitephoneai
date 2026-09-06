<?php
require_once __DIR__ . '/../config/config.php';
require_customer();
require_post();

$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$stmt = $conn->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
$stmt->bind_param('i', $variantId);
$stmt->execute();
$variant = $stmt->get_result()->fetch_assoc();

$current = (int) ($_SESSION['cart'][$variantId] ?? 0);
if (!$variant || $current + $quantity > (int) $variant['stock_quantity']) {
    set_flash('error', 'Sản phẩm không tồn tại hoặc không đủ tồn kho.');
    redirect('/cart/index.php');
}

$_SESSION['cart'][$variantId] = $current + $quantity;
set_flash('success', 'Đã thêm sản phẩm vào giỏ hàng.');
redirect('/cart/index.php');
