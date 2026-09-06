<?php
require_once __DIR__ . '/../config/config.php';
$slug = trim($_GET['brand'] ?? '');

$stmt = $conn->prepare('SELECT * FROM brands WHERE slug = ?');
$stmt->bind_param('s', $slug);
$stmt->execute();
$brand = $stmt->get_result()->fetch_assoc();

if (!$brand) {
    http_response_code(404);
    exit('Không tìm thấy thương hiệu.');
}

$stmt = $conn->prepare("SELECT p.id, p.name, p.thumbnail, MIN(COALESCE(v.sale_price, v.price)) AS min_price
    FROM products p LEFT JOIN product_variants v ON v.product_id = p.id
    WHERE p.brand_id = ? AND p.is_active = 1 GROUP BY p.id, p.name, p.thumbnail ORDER BY p.name");
$stmt->bind_param('i', $brand['id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = $brand['name'];
$pageCss = ['product'];
require __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Trang chủ</a> / <?= e($brand['name']) ?></div>
<div class="page-header"><div><h1>Điện thoại <?= e($brand['name']) ?></h1><p><?= count($products) ?> sản phẩm đang bán</p></div><a class="btn btn-outline" href="<?= BASE_URL ?>/search.php">Tìm sản phẩm</a></div>
<?php if ($products): ?><div class="product-grid">
<?php foreach ($products as $product): ?>
<article class="product-card"><?php if (image_exists($product['thumbnail'] ?? '', 'products')): ?><img class="product-card-image" src="<?= BASE_URL ?>/<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><div class="product-icon">PHONE</div><?php endif; ?><span class="product-brand"><?= e($brand['name']) ?></span><h3><a href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3><p class="price"><?= $product['min_price'] !== null ? format_price($product['min_price']) : 'Liên hệ' ?></p><a class="btn btn-outline" href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>">Xem chi tiết</a></article>
<?php endforeach; ?></div><?php else: ?><div class="empty-state"><h2>Chưa có sản phẩm</h2><p>Thương hiệu này chưa có sản phẩm đang bán.</p></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
