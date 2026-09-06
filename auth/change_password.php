<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$error = '';

if (is_post()) {
    $currentPassword = post_value('current_password');
    $newPassword = post_value('new_password');
    $confirmPassword = post_value('confirm_password');

    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $currentPasswordCorrect = $user && (
        password_verify($currentPassword, $user['password']) ||
        hash_equals($user['password'], $currentPassword)
    );

    if (!$currentPasswordCorrect) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu mới không khớp.';
    } else {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->bind_param('si', $passwordHash, $userId);
        $update->execute();
        set_flash('success', 'Đã đổi mật khẩu thành công.');
        redirect('/auth/change_password.php');
    }
}

$pageTitle = 'Đổi mật khẩu';
$pageCss = ['auth'];
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <section class="auth-card">
        <h1>Đổi mật khẩu</h1>
        <p class="auth-intro">Mật khẩu mới phải có ít nhất 6 ký tự.</p>

        <?php if ($error): ?>
            <p class="alert alert-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <input id="new_password" type="password" name="new_password" minlength="6" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input id="confirm_password" type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit">Đổi mật khẩu</button>
                <a class="btn btn-outline btn-cancel-edit" href="<?= BASE_URL ?>/index.php">Hủy</a>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
