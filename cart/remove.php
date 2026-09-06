<?php
require_once __DIR__ . '/../config/config.php';
require_customer();
require_post();
$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
unset($_SESSION['cart'][$variantId]);
set_flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
redirect('/cart/index.php');
