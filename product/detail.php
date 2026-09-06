<?php
require_once __DIR__ . '/../config/config.php';
$id = get_id();

$stmt = $conn->prepare('SELECT p.*, b.name AS brand_name FROM products p JOIN brands b ON b.id = p.brand_id WHERE p.id = ? AND p.is_active = 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$stmt = $conn->prepare('SELECT * FROM product_specs WHERE product_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$spec = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY price');
$stmt->bind_param('i', $id);
$stmt->execute();
$variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = $product['name'];
$pageCss = ['product'];
$pageJs = ['product'];
require __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Trang chủ</a> / <a href="<?= BASE_URL ?>/product/category.php?brand=<?= e(strtolower($product['brand_name'])) ?>"><?= e($product['brand_name']) ?></a> / <?= e($product['name']) ?></div>
<section class="product-detail">
    <div class="product-visual"><?php if (image_exists($product['thumbnail'], 'products')): ?><img class="product-main-image" src="<?= BASE_URL ?>/<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>"><?php else: ?>PHONE<?php endif; ?></div>
    <div class="product-summary">
        <span class="badge"><?= e($product['brand_name']) ?></span>
        <h1><?= e($product['name']) ?></h1>
        <p class="product-description"><?= nl2br(e($product['description'])) ?></p>
        <h2>Chọn phiên bản</h2>
        <?php if ($variants): ?><div class="variant-list">
        <?php foreach ($variants as $variant): ?>
        <?php if (is_customer()): ?>
        <form class="variant-card" action="<?= BASE_URL ?>/cart/add.php" method="post">
            <input type="hidden" name="variant_id" value="<?= $variant['id'] ?>">
            <div><strong><?= e($variant['color']) ?> - <?= e($variant['storage']) ?></strong><br><small>Còn <?= $variant['stock_quantity'] ?> sản phẩm</small></div>
            <span class="variant-price"><?= format_price(sale_price($variant)) ?></span>
            <div class="form-actions"><input type="number" name="quantity" value="1" min="1" max="<?= $variant['stock_quantity'] ?>" aria-label="Số lượng"><button type="submit" <?= $variant['stock_quantity'] < 1 ? 'disabled' : '' ?>>Thêm vào giỏ</button></div>
        </form>
        <?php else: ?>
        <div class="variant-card"><div><strong><?= e($variant['color']) ?> - <?= e($variant['storage']) ?></strong><br><small>Còn <?= $variant['stock_quantity'] ?> sản phẩm</small></div><span class="variant-price"><?= format_price(sale_price($variant)) ?></span></div>
        <?php endif; ?>
        <?php endforeach; ?></div>
        <?php if (!is_logged_in()): ?><p class="alert"><a href="<?= BASE_URL ?>/auth/login.php">Đăng nhập bằng tài khoản khách hàng để mua sản phẩm.</a></p><?php elseif (!is_customer()): ?><p class="alert">Chức năng mua hàng chỉ dành cho khách hàng.</p><?php endif; ?>
        <?php else: ?><p>Chưa có phiên bản đang bán.</p><?php endif; ?>
    </div>
</section>
<?php if ($spec): ?>
<section class="card"><div class="section-heading"><div><h2>Thông số kỹ thuật</h2><p>Thông tin chi tiết của sản phẩm</p></div></div>
<ul class="spec-list">
<li><span>Màn hình</span><strong><?= e($spec['screen_size']) ?>, <?= e($spec['screen_tech']) ?></strong></li>
<li><span>Tần số quét</span><strong><?= e($spec['refresh_rate']) ?> Hz</strong></li>
<li><span>Chip</span><strong><?= e($spec['chipset']) ?></strong></li>
<li><span>GPU</span><strong><?= e($spec['gpu']) ?></strong></li>
<li><span>RAM</span><strong><?= e($spec['ram']) ?> GB</strong></li>
<li><span>Camera sau</span><strong><?= e($spec['camera_rear']) ?></strong></li>
<li><span>Camera trước</span><strong><?= e($spec['camera_front']) ?></strong></li>
<li><span>Pin / Sạc</span><strong><?= e($spec['battery_capacity']) ?> mAh / <?= e($spec['charging_speed']) ?> W</strong></li>
<li><span>Hệ điều hành</span><strong><?= e($spec['os']) ?></strong></li>
<li><span>SIM</span><strong><?= e($spec['sim']) ?></strong></li>
</ul></section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
