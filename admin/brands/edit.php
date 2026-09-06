<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
$id = get_id();
$error = '';

$stmt = $conn->prepare('SELECT * FROM brands WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$brand = $stmt->get_result()->fetch_assoc();
if (!$brand) {
    http_response_code(404);
    exit('Không tìm thấy thương hiệu.');
}

$newImage = null;
if (is_post()) {
    $name = post_value('name');
    $slug = post_value('slug') ?: make_slug($name);
    $logoUrl = $brand['logo_url'];

    if ($name === '' || $slug === '') {
        $error = 'Tên và slug không được trống.';
    } else {
        $check = $conn->prepare('SELECT id FROM brands WHERE slug = ? AND id != ?');
        $check->bind_param('si', $slug, $id);
        $check->execute();
        if ($check->get_result()->num_rows) {
            $error = 'Slug đã tồn tại.';
        } else {
            try {
                $newImage = upload_image('logo_file', 'brands');
                if ($newImage) {
                    $logoUrl = $newImage;
                }
                $update = $conn->prepare('UPDATE brands SET name = ?, slug = ?, logo_url = ? WHERE id = ?');
                $update->bind_param('sssi', $name, $slug, $logoUrl, $id);
                $update->execute();
                if ($newImage) {
                    delete_uploaded_image($brand['logo_url'], 'brands');
                }
                set_flash('success', 'Đã sửa thương hiệu.');
                redirect('/admin/brands/index.php');
            } catch (Throwable $exception) {
                delete_uploaded_image($newImage, 'brands');
                $error = 'Không thể sửa thương hiệu: ' . $exception->getMessage();
            }
        }
    }
}
$pageTitle = 'Sửa thương hiệu';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Sửa thương hiệu</h1>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $id ?>">
    <label>Tên</label><br>
    <input name="name" value="<?= e($_POST['name'] ?? $brand['name']) ?>" required><br>
    <label>Slug</label><br>
    <input name="slug" value="<?= e($_POST['slug'] ?? $brand['slug']) ?>"><br>
    <label>Logo thương hiệu</label>
    <div class="image-picker">
        <input class="image-input" id="logo_file" type="file" name="logo_file" accept="image/jpeg,image/png,image/webp">
        <label class="image-button" for="logo_file"><span class="image-icon">▧</span> Chọn ảnh mới</label>
        <span class="image-name">Giữ ảnh hiện tại nếu không chọn</span>
        <img class="image-preview" src="<?= image_exists($brand['logo_url'], 'brands') ? BASE_URL . '/' . e($brand['logo_url']) : '' ?>" alt="Logo hiện tại" <?= image_exists($brand['logo_url'], 'brands') ? '' : 'hidden' ?>>
    </div>
    <br>
    <div class="form-actions">
        <button type="submit">Lưu</button>
        <a class="btn btn-outline btn-cancel-edit" href="index.php">Hủy</a>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
