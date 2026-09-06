<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) {
    redirect('/index.php');
}
$pageTitle = 'Đăng nhập';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap"><section class="auth-card">
<h1>Đăng nhập</h1><p class="auth-intro">Chào mừng bạn quay lại MCHAphone Store.</p>
<form action="<?= BASE_URL ?>/auth/login_process.php" method="post">
    <div class="form-group"><label>Tên đăng nhập</label><input type="text" name="username" placeholder="Nhập email hoặc tên người dùng" required autofocus></div>
    <div class="form-group"><label>Mật khẩu</label><input type="password" name="password" placeholder="Nhập mật khẩu" required></div>
    <button type="submit">Đăng nhập</button>
</form>
<p class="auth-switch">Chưa có tài khoản? <a href="<?= BASE_URL ?>/auth/register.php">Đăng ký ngay</a></p>
</section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
