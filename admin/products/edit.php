<?php
require_once __DIR__ . '/../../config/config.php'; require_admin(); $id = get_id(); $error = '';
$stmt = $conn->prepare('SELECT p.*, s.screen_size, s.screen_tech, s.refresh_rate, s.chipset, s.gpu, s.ram, s.camera_rear, s.camera_front, s.battery_capacity, s.charging_speed, s.os, s.sim FROM products p LEFT JOIN product_specs s ON s.product_id = p.id WHERE p.id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $product = $stmt->get_result()->fetch_assoc();
if (!$product) { http_response_code(404); exit('Không tìm thấy sản phẩm.'); }
$brands = $conn->query('SELECT id, name FROM brands ORDER BY name')->fetch_all(MYSQLI_ASSOC); $newImage = null;
if (is_post()) {
    $brandId = (int) post_value('brand_id'); $name = post_value('name'); $slug = post_value('slug') ?: make_slug($name); $description = post_value('description'); $thumbnail = $product['thumbnail']; $isActive = isset($_POST['is_active']) ? 1 : 0;
    $screenSize = post_value('screen_size'); $screenTech = post_value('screen_tech'); $refreshRate = max(0, (int) post_value('refresh_rate')); $chipset = post_value('chipset'); $gpu = post_value('gpu'); $ram = max(0, (int) post_value('ram')); $cameraRear = post_value('camera_rear'); $cameraFront = post_value('camera_front'); $battery = max(0, (int) post_value('battery_capacity')); $charging = max(0, (int) post_value('charging_speed')); $os = post_value('os'); $sim = post_value('sim');
    if (!$brandId || $name === '' || $slug === '') $error = 'Thương hiệu, tên và slug là bắt buộc.';
    else {
        $check = $conn->prepare('SELECT id FROM products WHERE slug = ? AND id != ?'); $check->bind_param('si', $slug, $id); $check->execute();
        if ($check->get_result()->num_rows) $error = 'Slug đã tồn tại.';
        else {
            try {
                $newImage = upload_image('thumbnail_file', 'products');
                if ($newImage) $thumbnail = $newImage;
                $conn->begin_transaction();
                $update = $conn->prepare('UPDATE products SET brand_id = ?, name = ?, slug = ?, description = ?, thumbnail = ?, is_active = ? WHERE id = ?'); $update->bind_param('issssii', $brandId, $name, $slug, $description, $thumbnail, $isActive, $id); $update->execute();
                $spec = $conn->prepare('INSERT INTO product_specs (product_id, screen_size, screen_tech, refresh_rate, chipset, gpu, ram, camera_rear, camera_front, battery_capacity, charging_speed, os, sim) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE screen_size=VALUES(screen_size), screen_tech=VALUES(screen_tech), refresh_rate=VALUES(refresh_rate), chipset=VALUES(chipset), gpu=VALUES(gpu), ram=VALUES(ram), camera_rear=VALUES(camera_rear), camera_front=VALUES(camera_front), battery_capacity=VALUES(battery_capacity), charging_speed=VALUES(charging_speed), os=VALUES(os), sim=VALUES(sim)');
                $spec->bind_param('issississiiss', $id, $screenSize, $screenTech, $refreshRate, $chipset, $gpu, $ram, $cameraRear, $cameraFront, $battery, $charging, $os, $sim); $spec->execute();
                $conn->commit();
                if ($newImage) delete_uploaded_image($product['thumbnail'], 'products');
                set_flash('success', 'Đã sửa sản phẩm.'); redirect('/admin/products/detail.php?id=' . $id);
            } catch (Throwable $exception) { $conn->rollback(); delete_uploaded_image($newImage, 'products'); $error = 'Không thể sửa sản phẩm: ' . $exception->getMessage(); }
        }
    }
}
function field_value(array $product, string $name): string { return e($_POST[$name] ?? $product[$name] ?? ''); }
$pageTitle = 'Sửa sản phẩm'; require __DIR__ . '/../../includes/header.php';
?>
<h1>Sửa sản phẩm</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= $id ?>">
<label>Thương hiệu</label><br><select name="brand_id"><?php foreach ($brands as $brand): ?><option value="<?= $brand['id'] ?>" <?= (int)($_POST['brand_id'] ?? $product['brand_id']) === (int)$brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option><?php endforeach; ?></select><br>
<label>Tên</label><br><input name="name" value="<?= field_value($product, 'name') ?>" required><br><label>Slug</label><br><input name="slug" value="<?= field_value($product, 'slug') ?>"><br><label>Mô tả</label><br><textarea name="description"><?= field_value($product, 'description') ?></textarea><br><label>Ảnh đại diện</label><div class="image-picker"><input class="image-input" id="thumbnail_file" type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp"><label class="image-button" for="thumbnail_file"><span class="image-icon">▧</span> Chọn ảnh mới</label><span class="image-name">Giữ ảnh hiện tại nếu không chọn</span><img class="image-preview" src="<?= image_exists($product['thumbnail'], 'products') ? BASE_URL . '/' . e($product['thumbnail']) : '' ?>" alt="Ảnh hiện tại" <?= image_exists($product['thumbnail'], 'products') ? '' : 'hidden' ?>></div><label><input type="checkbox" name="is_active" <?= (int)($_POST['is_active'] ?? $product['is_active']) ? 'checked' : '' ?>> Đang bán</label>
<h2>Thông số</h2><?php foreach (['screen_size'=>'Kích thước màn hình','screen_tech'=>'Công nghệ màn hình','refresh_rate'=>'Tần số quét','chipset'=>'Chipset','gpu'=>'GPU','ram'=>'RAM (GB)','camera_rear'=>'Camera sau','camera_front'=>'Camera trước','battery_capacity'=>'Pin (mAh)','charging_speed'=>'Sạc (W)','os'=>'Hệ điều hành','sim'=>'SIM'] as $field => $label): ?><label><?= $label ?></label><br><input name="<?= $field ?>" value="<?= field_value($product, $field) ?>"><br><?php endforeach; ?><br><div class="form-actions"><button type="submit">Lưu thay đổi</button><a class="btn btn-outline btn-cancel-edit" href="index.php">Hủy</a></div></form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
