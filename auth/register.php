<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) {
    redirect('/index.php');
}

$error = '';
if (is_post()) {
    $fullName = post_value('full_name');
    $email = post_value('email');
    $password = post_value('password');
    $phone = post_value('phone');

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Vui lòng nhập đủ thông tin; mật khẩu có ít nhất 6 ký tự.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email đã được sử dụng.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->bind_param('ssss', $fullName, $email, $passwordHash, $phone);
            $stmt->execute();
            set_flash('success', 'Đăng ký thành công. Vui lòng đăng nhập.');
            redirect('/auth/login.php');
        }
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap"><section class="auth-card">
<h1>Tạo tài khoản</h1><p class="auth-intro">Đăng ký để đặt hàng và theo dõi đơn dễ dàng.</p>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<form method="post">
    <div class="form-group"><label>Họ tên</label><input name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
    <div class="form-group"><label>Mật khẩu</label><input type="password" name="password" minlength="6" required><span class="password-note">Ít nhất 6 ký tự.</span></div>
    <div class="form-group"><label>Số điện thoại</label><input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
    <button type="submit">Đăng ký</button>
</form>
<p class="auth-switch">Đã có tài khoản? <a href="<?= BASE_URL ?>/auth/login.php">Đăng nhập</a></p>
</section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
