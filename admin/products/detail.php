<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
$id = get_id();

$stmt = $conn->prepare('SELECT p.*, b.name AS brand_name, s.screen_size, s.screen_tech, s.refresh_rate, s.chipset, s.gpu, s.ram, s.camera_rear, s.camera_front, s.battery_capacity, s.charging_speed, s.os, s.sim FROM products p JOIN brands b ON b.id = p.brand_id LEFT JOIN product_specs s ON s.product_id = p.id WHERE p.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    http_response_code(404);
    exit('Không tìm thấy sản phẩm.');
}

$stmt = $conn->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY id DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Chi tiết: ' . $product['name'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <h1 style="margin: 0;"><?= e($product['name']) ?></h1>
            <span class="badge <?= $product['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                <?= $product['is_active'] ? 'Đang bán' : 'Đang ẩn' ?>
            </span>
        </div>
        <p>Thương hiệu: <strong><?= e($product['brand_name']) ?></strong> &bull; Slug: <code><?= e($product['slug']) ?></code></p>
    </div>
    <div class="detail-actions">
        <a class="btn" href="edit.php?id=<?= $id ?>">Sửa sản phẩm và thông số</a>
        <a class="btn btn-outline" href="../variants/create.php?product_id=<?= $id ?>">Thêm biến thể</a>
        <a class="btn btn-outline" href="index.php">Thoát</a>
    </div>
</div>

<div class="detail-card">
    <div class="detail-grid">
        <div class="detail-thumb">
            <?php if (image_exists($product['thumbnail'], 'products')): ?>
                <img class="admin-product-image" src="<?= BASE_URL ?>/<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <div class="product-thumb-placeholder">Không có ảnh</div>
            <?php endif; ?>
        </div>
        <div class="detail-info">
            <h3 style="margin-top: 0; margin-bottom: 10px;">Mô tả sản phẩm</h3>
            <div class="product-desc-text">
                <?= $product['description'] ? nl2br(e($product['description'])) : '<em style="color: var(--muted);">Chưa có mô tả chi tiết cho sản phẩm này.</em>' ?>
            </div>
        </div>
    </div>
</div>

<div class="detail-card">
    <h2 style="margin-top: 0; margin-bottom: 16px; font-size: 1.25rem;">Thông số kỹ thuật</h2>
    <div class="spec-grid">
        <div class="spec-item">
            <span class="spec-label">Màn hình</span>
            <span class="spec-value"><?= e($product['screen_size'] ?: '-') ?> &bull; <?= e($product['screen_tech'] ?: '-') ?> <?= $product['refresh_rate'] ? '(' . e($product['refresh_rate']) . ' Hz)' : '' ?></span>
        </div>
        <div class="spec-item">
            <span class="spec-label">Chipset & GPU</span>
            <span class="spec-value"><?= e($product['chipset'] ?: '-') ?> / <?= e($product['gpu'] ?: '-') ?></span>
        </div>
        <div class="spec-item">
            <span class="spec-label">RAM</span>
            <span class="spec-value"><?= $product['ram'] ? e($product['ram']) . ' GB' : '-' ?></span>
        </div>
        <div class="spec-item">
            <span class="spec-label">Camera sau / trước</span>
            <span class="spec-value"><?= e($product['camera_rear'] ?: '-') ?> / <?= e($product['camera_front'] ?: '-') ?></span>
        </div>
        <div class="spec-item">
            <span class="spec-label">Pin & Sạc</span>
            <span class="spec-value"><?= $product['battery_capacity'] ? e($product['battery_capacity']) . ' mAh' : '-' ?> <?= $product['charging_speed'] ? '(' . e($product['charging_speed']) . ' W)' : '' ?></span>
        </div>
        <div class="spec-item">
            <span class="spec-label">Hệ điều hành & SIM</span>
            <span class="spec-value"><?= e($product['os'] ?: '-') ?> / <?= e($product['sim'] ?: '-') ?></span>
        </div>
    </div>
</div>

<div class="detail-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0; font-size: 1.25rem;">Danh sách biến thể (<?= count($variants) ?>)</h2>
        <a class="btn btn-small" href="../variants/create.php?product_id=<?= $id ?>">+ Thêm biến thể</a>
    </div>
    <?php if (empty($variants)): ?>
        <p style="color: var(--muted); margin: 0;">Sản phẩm này chưa có biến thể nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Màu</th>
                        <th>Bộ nhớ</th>
                        <th>Giá gốc</th>
                        <th>Giá giảm</th>
                        <th>Tồn kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants as $variant): ?>
                    <tr>
                        <td>
                            <?php if (image_exists($variant['variant_image'], 'variants')): ?>
                                <img class="admin-variant-image" src="<?= BASE_URL ?>/<?= e($variant['variant_image']) ?>" alt="<?= e($variant['color']) ?>">
                            <?php else: ?>
                                <span style="color: var(--muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($variant['color']) ?></strong></td>
                        <td><?= e($variant['storage']) ?></td>
                        <td><?= format_price($variant['price']) ?></td>
                        <td><?= $variant['sale_price'] ? '<span style="color: #d92d20; font-weight: 600;">' . format_price($variant['sale_price']) . '</span>' : '<span style="color: var(--muted);">-</span>' ?></td>
                        <td><span class="badge"><?= $variant['stock_quantity'] ?></span></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-small" href="../variants/edit.php?id=<?= $variant['id'] ?>">Sửa</a>
                                <form class="inline-form" data-confirm="Bạn có chắc chắn muốn xóa biến thể &quot;<?= e($product['name']) ?> - <?= e($variant['color']) ?> (<?= e($variant['storage']) ?>)&quot; không?" action="../variants/delete.php" method="post">
                                    <input type="hidden" name="id" value="<?= $variant['id'] ?>">
                                    <button class="btn-danger btn-small" type="submit">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
