<?php
require_once __DIR__ . '/../config/config.php';
require_customer();

$items = get_cart_items($conn);
if (!$items) {
    set_flash('error', 'Giỏ hàng đang trống.');
    redirect('/cart/index.php');
}

$error = '';
if (is_post()) {
    $receiverName = post_value('receiver_name');
    $receiverPhone = post_value('receiver_phone');
    $receiverAddress = post_value('receiver_address');
    $paymentMethod = post_value('payment_method', 'cod');

    if ($receiverName === '' || $receiverPhone === '' || $receiverAddress === '' || !in_array($paymentMethod, ['cod', 'banking'], true)) {
        $error = 'Vui lòng nhập đầy đủ thông tin nhận hàng.';
    } else {
        try {
            $conn->begin_transaction();
            $total = 0;
            $checkedItems = [];
            $lockStmt = $conn->prepare('SELECT id, price, sale_price, stock_quantity FROM product_variants WHERE id = ? FOR UPDATE');

            foreach ($_SESSION['cart'] as $variantId => $quantity) {
                $variantId = (int) $variantId;
                $quantity = (int) $quantity;
                $lockStmt->bind_param('i', $variantId);
                $lockStmt->execute();
                $variant = $lockStmt->get_result()->fetch_assoc();

                if (!$variant || $quantity < 1 || $quantity > (int) $variant['stock_quantity']) {
                    throw new Exception('Một sản phẩm không còn đủ số lượng trong kho.');
                }

                $unitPrice = sale_price($variant);
                $total += $unitPrice * $quantity;
                $checkedItems[] = ['id' => $variantId, 'quantity' => $quantity, 'unit_price' => $unitPrice];
            }

            $userId = (int) $_SESSION['user_id'];
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, receiver_name, receiver_phone, receiver_address, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $orderStmt->bind_param('isssds', $userId, $receiverName, $receiverPhone, $receiverAddress, $total, $paymentMethod);
            $orderStmt->execute();
            $orderId = $conn->insert_id;

            $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stockStmt = $conn->prepare('UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?');
            foreach ($checkedItems as $item) {
                $itemStmt->bind_param('iiid', $orderId, $item['id'], $item['quantity'], $item['unit_price']);
                $itemStmt->execute();
                $stockStmt->bind_param('ii', $item['quantity'], $item['id']);
                $stockStmt->execute();
            }

            $conn->commit();
            unset($_SESSION['cart']);
            set_flash('success', 'Đặt hàng thành công.');
            redirect('/orders/detail.php?id=' . $orderId);
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = $exception->getMessage();
        }
    }
}

$userStmt = $conn->prepare('SELECT full_name, phone, address FROM users WHERE id = ?');
$userStmt->bind_param('i', $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$total = array_sum(array_column($items, 'subtotal'));
$pageTitle = 'Thanh toán';
$pageCss = ['cart'];
require __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb"><a href="<?= BASE_URL ?>/cart/index.php">Giỏ hàng</a> / Thanh toán</div>
<div class="page-header"><div><h1>Thông tin thanh toán</h1><p>Kiểm tra thông tin nhận hàng trước khi xác nhận.</p></div></div>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<div class="checkout-layout">
<form class="checkout-form card" method="post">
<h2>Thông tin người nhận</h2>
<div class="form-group"><label>Người nhận</label><input name="receiver_name" value="<?= e($_POST['receiver_name'] ?? $user['full_name']) ?>" required></div>
<div class="form-group"><label>Số điện thoại</label><input name="receiver_phone" value="<?= e($_POST['receiver_phone'] ?? $user['phone']) ?>" required></div>
<div class="form-group"><label>Địa chỉ</label><textarea name="receiver_address" required><?= e($_POST['receiver_address'] ?? $user['address']) ?></textarea></div>
<div class="form-group"><label>Phương thức thanh toán</label><select name="payment_method"><option value="cod">Thanh toán khi nhận hàng</option><option value="banking">Chuyển khoản ngân hàng</option></select></div>
<div class="form-actions"><button type="submit">Xác nhận đặt hàng</button></div>
</form>
<aside class="cart-summary card"><h2>Đơn hàng của bạn</h2><?php foreach ($items as $item): ?><div class="summary-line"><span><?= e($item['product_name']) ?> × <?= $item['quantity'] ?></span><strong><?= format_price($item['subtotal']) ?></strong></div><?php endforeach; ?><div class="summary-line"><span>Tổng thanh toán</span><strong class="summary-total"><?= format_price($total) ?></strong></div></aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
