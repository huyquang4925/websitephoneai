<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$error = '';

$stmt = $conn->prepare('SELECT id, full_name, email, phone, role FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['role']);
    set_flash('error', 'Tài khoản không còn tồn tại.');
    redirect('/auth/login.php');
}

if (is_post()) {
    $fullName = post_value('full_name');
    $email = post_value('email');
    $phone = post_value('phone');

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vui lòng nhập họ tên và email hợp lệ.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->bind_param('si', $email, $userId);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email đã được sử dụng bởi tài khoản khác.';
        } else {
            $update = $conn->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
            $update->bind_param('sssi', $fullName, $email, $phone, $userId);
            $update->execute();
            $_SESSION['full_name'] = $fullName;
            set_flash('success', 'Đã cập nhật thông tin cá nhân.');
            redirect('/auth/profile.php');
        }
    }

    $user['full_name'] = $fullName;
    $user['email'] = $email;
    $user['phone'] = $phone;
}

$pageTitle = 'Thông tin cá nhân';
$pageCss = ['auth'];
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <section class="auth-card">
        <h1>Thông tin cá nhân</h1>
        <p class="auth-intro">Cập nhật thông tin tài khoản của bạn.</p>

        <?php if ($error): ?>
            <p class="alert alert-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="full_name">Họ tên</label>
                <input id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input id="phone" name="phone" value="<?= e($user['phone']) ?>">
            </div>
            <div class="form-group">
                <label>Vai trò</label>
                <input value="<?= e(role_label($user['role'])) ?>" disabled>
            </div>
            <div class="form-actions">
                <button type="submit">Lưu thay đổi</button>
                <a class="btn btn-outline btn-cancel-edit" href="<?= BASE_URL ?>/index.php">Hủy</a>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
