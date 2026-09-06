<?php
require_once __DIR__ . '/../../config/config.php'; require_admin(); $id = get_id(); $error = '';
$stmt = $conn->prepare('SELECT * FROM product_variants WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $variant = $stmt->get_result()->fetch_assoc(); if (!$variant) { http_response_code(404); exit('Không tìm thấy biến thể.'); }
$products = $conn->query('SELECT id, name FROM products ORDER BY name')->fetch_all(MYSQLI_ASSOC); $newImage = null;
if (is_post()) {
    $productId = (int) post_value('product_id'); $color = post_value('color'); $storage = post_value('storage'); $price = (float) post_value('price'); $saleText = post_value('sale_price'); $salePrice = $saleText === '' ? null : (float) $saleText; $stock = (int) post_value('stock_quantity'); $image = $variant['variant_image'];
    if (!$productId || $color === '' || $storage === '' || $price < 0 || $stock < 0 || ($salePrice !== null && $salePrice < 0)) $error = 'Dữ liệu biến thể không hợp lệ.';
    else {
        try {
            $newImage = upload_image('variant_image_file', 'variants');
            if ($newImage) $image = $newImage;
            $update = $conn->prepare('UPDATE product_variants SET product_id=?, color=?, storage=?, price=?, sale_price=?, stock_quantity=?, variant_image=? WHERE id=?');
            $update->bind_param('issddisi', $productId, $color, $storage, $price, $salePrice, $stock, $image, $id); $update->execute();
            if ($newImage) delete_uploaded_image($variant['variant_image'], 'variants');
            set_flash('success', 'Đã sửa biến thể.'); redirect('/admin/variants/index.php?product_id=' . $productId);
        } catch (Throwable $exception) { delete_uploaded_image($newImage, 'variants'); $error = 'Không thể sửa biến thể: ' . $exception->getMessage(); }
    }
}
function variant_value(array $variant, string $name): string { return e($_POST[$name] ?? $variant[$name] ?? ''); }
$pageTitle = 'Sửa biến thể'; require __DIR__ . '/../../includes/header.php';
?>
<h1>Sửa biến thể</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= $id ?>"><label>Sản phẩm</label><br><select name="product_id"><?php foreach ($products as $product): ?><option value="<?= $product['id'] ?>" <?= (int)($_POST['product_id'] ?? $variant['product_id']) === (int)$product['id'] ? 'selected' : '' ?>><?= e($product['name']) ?></option><?php endforeach; ?></select><br><label>Màu</label><br><input name="color" value="<?= variant_value($variant,'color') ?>"><br><label>Bộ nhớ</label><br><input name="storage" value="<?= variant_value($variant,'storage') ?>"><br><label>Giá</label><br><input type="number" name="price" min="0" value="<?= variant_value($variant,'price') ?>"><br><label>Giá giảm</label><br><input type="number" name="sale_price" min="0" value="<?= variant_value($variant,'sale_price') ?>"><br><label>Tồn kho</label><br><input type="number" name="stock_quantity" min="0" value="<?= variant_value($variant,'stock_quantity') ?>"><br><label>Ảnh biến thể</label><div class="image-picker"><input class="image-input" id="variant_image_file" type="file" name="variant_image_file" accept="image/jpeg,image/png,image/webp"><label class="image-button" for="variant_image_file"><span class="image-icon">▧</span> Chọn ảnh mới</label><span class="image-name">Giữ ảnh hiện tại nếu không chọn</span><img class="image-preview" src="<?= image_exists($variant['variant_image'], 'variants') ? BASE_URL . '/' . e($variant['variant_image']) : '' ?>" alt="Ảnh hiện tại" <?= image_exists($variant['variant_image'], 'variants') ? '' : 'hidden' ?>></div><br><div class="form-actions"><button type="submit">Lưu</button><a class="btn btn-outline btn-cancel-edit" href="index.php?product_id=<?= $variant['product_id'] ?>">Hủy</a></div></form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
