<?php
require_once __DIR__ . '/../../config/config.php'; require_admin(); $error = ''; $newImage = null; $products = $conn->query('SELECT id, name FROM products ORDER BY name')->fetch_all(MYSQLI_ASSOC); $selectedProduct = max(0, (int) ($_GET['product_id'] ?? 0));
if (is_post()) {
    $productId = (int) post_value('product_id'); $color = post_value('color'); $storage = post_value('storage'); $price = (float) post_value('price'); $saleText = post_value('sale_price'); $salePrice = $saleText === '' ? null : (float) $saleText; $stock = (int) post_value('stock_quantity'); $image = '';
    if (!$productId || $color === '' || $storage === '' || $price < 0 || $stock < 0 || ($salePrice !== null && $salePrice < 0)) $error = 'Dữ liệu biến thể không hợp lệ.';
    else {
        try {
            $newImage = upload_image('variant_image_file', 'variants');
            $image = $newImage ?? '';
            $stmt = $conn->prepare('INSERT INTO product_variants (product_id, color, storage, price, sale_price, stock_quantity, variant_image) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issddis', $productId, $color, $storage, $price, $salePrice, $stock, $image); $stmt->execute();
            set_flash('success', 'Đã thêm biến thể.'); redirect('/admin/variants/index.php?product_id=' . $productId);
        } catch (Throwable $exception) { delete_uploaded_image($newImage, 'variants'); $error = 'Không thể thêm biến thể: ' . $exception->getMessage(); }
    }
}
$pageTitle = 'Thêm biến thể'; require __DIR__ . '/../../includes/header.php';
?>
<h1>Thêm biến thể</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?><form method="post" enctype="multipart/form-data"><label>Sản phẩm</label><br><select name="product_id"><?php foreach ($products as $product): ?><option value="<?= $product['id'] ?>" <?= $selectedProduct === (int)$product['id'] ? 'selected' : '' ?>><?= e($product['name']) ?></option><?php endforeach; ?></select><br><label>Màu</label><br><input name="color" required><br><label>Bộ nhớ</label><br><input name="storage" required><br><label>Giá</label><br><input type="number" name="price" min="0" required><br><label>Giá giảm</label><br><input type="number" name="sale_price" min="0"><br><label>Tồn kho</label><br><input type="number" name="stock_quantity" min="0" value="0"><br><label>Ảnh biến thể</label><div class="image-picker"><input class="image-input" id="variant_image_file" type="file" name="variant_image_file" accept="image/jpeg,image/png,image/webp"><label class="image-button" for="variant_image_file"><span class="image-icon">▧</span> Chọn ảnh từ máy</label><span class="image-name">Chưa chọn ảnh</span><img class="image-preview" alt="Xem trước ảnh" hidden></div><br><div class="form-actions"><button type="submit">Thêm</button><a class="btn btn-outline btn-cancel-edit" href="index.php<?= $selectedProduct ? '?product_id=' . $selectedProduct : '' ?>">Hủy</a></div></form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
