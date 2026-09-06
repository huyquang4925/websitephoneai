<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
$error = '';
$newImage = null;

if (is_post()) {
    $name = post_value('name');
    $slug = post_value('slug') ?: make_slug($name);
    $logoUrl = '';

    if ($name === '' || $slug === '') {
        $error = 'Tên và slug không được trống.';
    } else {
        $check = $conn->prepare('SELECT id FROM brands WHERE slug = ?');
        $check->bind_param('s', $slug);
        $check->execute();
        if ($check->get_result()->num_rows) {
            $error = 'Slug đã tồn tại.';
        } else {
            try {
                $newImage = upload_image('logo_file', 'brands');
                $logoUrl = $newImage ?? '';
                $stmt = $conn->prepare('INSERT INTO brands (name, slug, logo_url) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $name, $slug, $logoUrl);
                $stmt->execute();
                set_flash('success', 'Đã thêm thương hiệu.');
                redirect('/admin/brands/index.php');
            } catch (Throwable $exception) {
                delete_uploaded_image($newImage, 'brands');
                $error = 'Không thể thêm thương hiệu: ' . $exception->getMessage();
            }
        }
    }
}
$pageTitle = 'Thêm thương hiệu';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Thêm thương hiệu</h1>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label>Tên</label><br>
    <input name="name" value="<?= e($_POST['name'] ?? '') ?>" required><br>
    <label>Slug</label><br>
    <input name="slug" value="<?= e($_POST['slug'] ?? '') ?>"><br>
    <label>Logo thương hiệu</label>
    <div class="image-picker">
        <input class="image-input" id="logo_file" type="file" name="logo_file" accept="image/jpeg,image/png,image/webp">
        <label class="image-button" for="logo_file"><span class="image-icon">▧</span> Chọn ảnh từ máy</label>
        <span class="image-name">Chưa chọn ảnh</span>
        <img class="image-preview" alt="Xem trước ảnh" hidden>
    </div>
    <br>
    <div class="form-actions">
        <button type="submit">Thêm</button>
        <a class="btn btn-outline btn-cancel-edit" href="index.php">Hủy</a>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
