<?php
require_once __DIR__ . '/../../config/config.php'; require_admin();
$brands = $conn->query('SELECT id, name FROM brands ORDER BY name')->fetch_all(MYSQLI_ASSOC); $error = ''; $newImage = null;
if (is_post()) {
    $brandId = (int) post_value('brand_id'); $name = post_value('name'); $slug = post_value('slug') ?: make_slug($name); $description = post_value('description'); $thumbnail = ''; $isActive = isset($_POST['is_active']) ? 1 : 0;
    $screenSize = post_value('screen_size'); $screenTech = post_value('screen_tech'); $refreshRate = max(0, (int) post_value('refresh_rate')); $chipset = post_value('chipset'); $gpu = post_value('gpu'); $ram = max(0, (int) post_value('ram')); $cameraRear = post_value('camera_rear'); $cameraFront = post_value('camera_front'); $battery = max(0, (int) post_value('battery_capacity')); $charging = max(0, (int) post_value('charging_speed')); $os = post_value('os'); $sim = post_value('sim');
    if (!$brandId || $name === '' || $slug === '') $error = 'Thương hiệu, tên và slug là bắt buộc.';
    else {
        $check = $conn->prepare('SELECT id FROM products WHERE slug = ?'); $check->bind_param('s', $slug); $check->execute();
        if ($check->get_result()->num_rows) $error = 'Slug đã tồn tại.';
        else {
            try {
                $newImage = upload_image('thumbnail_file', 'products');
                $thumbnail = $newImage ?? '';
                $conn->begin_transaction();
                $stmt = $conn->prepare('INSERT INTO products (brand_id, name, slug, description, thumbnail, is_active) VALUES (?, ?, ?, ?, ?, ?)'); $stmt->bind_param('issssi', $brandId, $name, $slug, $description, $thumbnail, $isActive); $stmt->execute(); $productId = $conn->insert_id;
                $specStmt = $conn->prepare('INSERT INTO product_specs (product_id, screen_size, screen_tech, refresh_rate, chipset, gpu, ram, camera_rear, camera_front, battery_capacity, charging_speed, os, sim) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $specStmt->bind_param('issississiiss', $productId, $screenSize, $screenTech, $refreshRate, $chipset, $gpu, $ram, $cameraRear, $cameraFront, $battery, $charging, $os, $sim); $specStmt->execute();
                $conn->commit(); set_flash('success', 'Đã thêm sản phẩm.'); redirect('/admin/products/detail.php?id=' . $productId);
            } catch (Throwable $exception) { $conn->rollback(); delete_uploaded_image($newImage, 'products'); $error = 'Không thể thêm sản phẩm: ' . $exception->getMessage(); }
        }
    }
}
$pageTitle = 'Thêm sản phẩm'; require __DIR__ . '/../../includes/header.php';
?>
<h1>Thêm sản phẩm</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Thương hiệu</label><br><select name="brand_id" required><option value="">-- Chọn --</option><?php foreach ($brands as $brand): ?><option value="<?= $brand['id'] ?>"><?= e($brand['name']) ?></option><?php endforeach; ?></select><br>
<label>Tên</label><br><input name="name" required><br><label>Slug</label><br><input name="slug"><br><label>Mô tả</label><br><textarea name="description"></textarea><br><label>Ảnh đại diện</label><div class="image-picker"><input class="image-input" id="thumbnail_file" type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp"><label class="image-button" for="thumbnail_file"><span class="image-icon">▧</span> Chọn ảnh từ máy</label><span class="image-name">Chưa chọn ảnh</span><img class="image-preview" alt="Xem trước ảnh" hidden></div><label><input type="checkbox" name="is_active" checked> Đang bán</label>
<h2>Thông số</h2><label>Kích thước màn hình</label><br><input name="screen_size"><br><label>Công nghệ màn hình</label><br><input name="screen_tech"><br><label>Tần số quét</label><br><input type="number" name="refresh_rate"><br><label>Chipset</label><br><input name="chipset"><br><label>GPU</label><br><input name="gpu"><br><label>RAM (GB)</label><br><input type="number" name="ram"><br><label>Camera sau</label><br><input name="camera_rear"><br><label>Camera trước</label><br><input name="camera_front"><br><label>Pin (mAh)</label><br><input type="number" name="battery_capacity"><br><label>Sạc (W)</label><br><input type="number" name="charging_speed"><br><label>Hệ điều hành</label><br><input name="os"><br><label>SIM</label><br><input name="sim"><br><br><div class="form-actions"><button type="submit">Thêm sản phẩm</button><a class="btn btn-outline btn-cancel-edit" href="index.php">Hủy</a></div>
</form><?php require __DIR__ . '/../../includes/footer.php'; ?>
