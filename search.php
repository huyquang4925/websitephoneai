<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Sản phẩm';
$pageCss = ['product'];
$q = trim($_GET['q'] ?? '');
$products = [];

if ($q !== '') {
    $keyword = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT p.id, p.name, p.thumbnail, b.name AS brand_name, MIN(COALESCE(v.sale_price, v.price)) AS min_price
        FROM products p JOIN brands b ON b.id = p.brand_id
        LEFT JOIN product_variants v ON v.product_id = p.id
        WHERE p.is_active = 1 AND (p.name LIKE ? OR b.name LIKE ?)
        GROUP BY p.id, p.name, p.thumbnail, b.name ORDER BY p.name");
    $stmt->bind_param('ss', $keyword, $keyword);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT p.id, p.name, p.thumbnail, b.name AS brand_name, MIN(COALESCE(v.sale_price, v.price)) AS min_price
        FROM products p JOIN brands b ON b.id = p.brand_id
        LEFT JOIN product_variants v ON v.product_id = p.id
        WHERE p.is_active = 1
        GROUP BY p.id, p.name, p.thumbnail, b.name ORDER BY p.id DESC");
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><?= $q !== '' ? 'Kết quả tìm kiếm' : 'Danh sách sản phẩm' ?></h1>
        <p><?= $q !== '' ? 'Kết quả tìm kiếm cho từ khóa “' . e($q) . '”' : 'Khám phá tất cả các dòng điện thoại mới nhất' ?></p>
    </div>
</div>

<?php if (!$products): ?>
    <div class="empty-state">
        <h2>Không tìm thấy sản phẩm</h2>
        <p>Không có sản phẩm nào phù hợp với từ khóa “<?= e($q) ?>”. Vui lòng thử lại với từ khóa khác.</p>
    </div>
<?php else: ?>
    <div class="section-heading">
        <div>
            <h2><?= $q !== '' ? 'Sản phẩm phù hợp' : 'Tất cả sản phẩm' ?></h2>
            <p>Hiển thị <?= count($products) ?> sản phẩm</p>
        </div>
    </div>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
        <article class="product-card">
            <?php if (image_exists($product['thumbnail'] ?? '', 'products')): ?>
                <img class="product-card-image" src="<?= BASE_URL ?>/<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <div class="product-icon">PHONE</div>
            <?php endif; ?>
            <span class="product-brand"><?= e($product['brand_name']) ?></span>
            <h3><a href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
            <p class="price"><?= $product['min_price'] !== null ? format_price($product['min_price']) : 'Liên hệ' ?></p>
            <a class="btn btn-outline" href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>">Xem chi tiết</a>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

