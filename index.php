<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Trang chủ';
$pageCss = ['product'];

$sql = "SELECT p.id, p.name, p.thumbnail, b.name AS brand_name,
        MIN(COALESCE(v.sale_price, v.price)) AS min_price
        FROM products p
        JOIN brands b ON b.id = p.brand_id
        LEFT JOIN product_variants v ON v.product_id = p.id
        WHERE p.is_active = 1
        GROUP BY p.id, p.name, p.thumbnail, b.name
        ORDER BY p.id DESC";
$products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>

<section class="feature-row" aria-label="Lợi ích mua hàng">
    <div class="feature-card">
        <div class="feature-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="feature-content">
            <strong>Hàng chính hãng</strong>
            <span>Nguồn gốc rõ ràng, bảo hành toàn quốc</span>
        </div>
    </div>
    <div class="feature-card">
        <div class="feature-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="feature-content">
            <strong>Giá tốt mỗi ngày</strong>
            <span>Giá bán và khuyến mãi minh bạch</span>
        </div>
    </div>
    <div class="feature-card">
        <div class="feature-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
        </div>
        <div class="feature-content">
            <strong>Hỗ trợ tận tâm</strong>
            <span>Tư vấn chọn máy 24/7 theo nhu cầu</span>
        </div>
    </div>
</section>

<section>
    <div class="section-heading"><div><h2>Sản phẩm nổi bật</h2><p><?= count($products) ?> sản phẩm đang được kinh doanh</p></div><a href="<?= BASE_URL ?>/search.php">Xem tất cả</a></div>
    <?php if ($products): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
        <article class="product-card">
            <?php if (image_exists($product['thumbnail'], 'products')): ?><img class="product-card-image" src="<?= BASE_URL ?>/<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><div class="product-icon" aria-hidden="true">PHONE</div><?php endif; ?>
            <span class="product-brand"><?= e($product['brand_name']) ?></span>
            <h3><a href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
            <p class="price"><?= $product['min_price'] !== null ? format_price($product['min_price']) : 'Liên hệ' ?></p>
            <a class="btn btn-outline" href="<?= BASE_URL ?>/product/detail.php?id=<?= $product['id'] ?>">Xem chi tiết</a>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty-state"><h2>Chưa có sản phẩm</h2><p>Vui lòng quay lại sau.</p></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
