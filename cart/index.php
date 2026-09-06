<?php
require_once __DIR__ . '/../config/config.php';
require_customer();
$items = get_cart_items($conn);
$total = array_sum(array_column($items, 'subtotal'));
$pageTitle = 'Giỏ hàng';
$pageCss = ['cart'];
$pageJs = ['cart'];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Giỏ hàng</h1><p>Kiểm tra sản phẩm trước khi đặt hàng.</p></div><a class="btn btn-outline" href="<?= BASE_URL ?>/index.php">Tiếp tục mua sắm</a></div>
<?php if (!$items): ?>
<div class="empty-state"><h2>Giỏ hàng đang trống</h2><p>Hãy chọn điện thoại phù hợp với bạn.</p><a class="btn" href="<?= BASE_URL ?>/index.php">Xem sản phẩm</a></div>
<?php else: ?>
<div class="cart-layout">
<div class="table-wrap"><table>
<tr><th>Sản phẩm</th><th>Phiên bản</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr>
<?php foreach ($items as $item): ?>
<?php
$hasVariantImg = image_exists($item['variant_image'] ?? '', 'variants');
$hasProductThumb = image_exists($item['thumbnail'] ?? '', 'products');
$imgSrc = $hasVariantImg ? BASE_URL . '/' . e($item['variant_image']) : ($hasProductThumb ? BASE_URL . '/' . e($item['thumbnail']) : '');
?>
<tr>
<td><div class="cart-product"><?php if ($imgSrc): ?><img class="cart-thumb" src="<?= $imgSrc ?>" alt="<?= e($item['product_name']) ?>"><?php else: ?><span class="cart-icon">PHONE</span><?php endif; ?><strong><?= e($item['product_name']) ?></strong></div></td>
<td><?= e($item['color']) ?><br><small><?= e($item['storage']) ?></small></td>
<td><?= format_price($item['unit_price']) ?></td>
<td><form class="inline-form" action="<?= BASE_URL ?>/cart/update.php" method="post"><input type="hidden" name="variant_id" value="<?= $item['id'] ?>"><div class="quantity-control"><button type="button" data-step="-1">−</button><input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" max="<?= $item['stock_quantity'] ?>"><button type="button" data-step="1">+</button></div><button class="btn-small" type="submit">Lưu</button></form></td>
<td><strong><?= format_price($item['subtotal']) ?></strong></td>
<td><form class="inline-form" data-confirm="Bạn có chắc chắn muốn xóa sản phẩm &quot;<?= e($item['product_name']) ?>&quot; khỏi giỏ hàng không?" action="<?= BASE_URL ?>/cart/remove.php" method="post"><input type="hidden" name="variant_id" value="<?= $item['id'] ?>"><button class="btn-danger btn-small" type="submit">Xóa</button></form></td>
</tr>
<?php endforeach; ?>
</table></div>
<aside class="cart-summary card"><h2>Tóm tắt đơn hàng</h2><div class="summary-line"><span>Số sản phẩm</span><strong><?= array_sum(array_column($items, 'quantity')) ?></strong></div><div class="summary-line"><span>Tổng thanh toán</span><strong class="summary-total"><?= format_price($total) ?></strong></div><a class="btn" href="<?= BASE_URL ?>/cart/checkout.php">Tiến hành đặt hàng</a></aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
