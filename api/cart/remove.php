<?php
require_once __DIR__ . '/../../config/config.php';
require_customer_api();
if (!is_post()) json_response(false, 'Chỉ hỗ trợ POST.', [], 405);
$variantId = max(0, (int) ($_POST['variant_id'] ?? 0));
unset($_SESSION['cart'][$variantId]);
json_response(true, 'Đã xóa khỏi giỏ.', ['cart' => $_SESSION['cart'] ?? []]);
