<?php
require_once __DIR__ . '/../config/config.php';
require_post();

$username = post_value('username', post_value('email'));
$password = post_value('password');

if ($username === '' || $password === '') {
    set_flash('error', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
    redirect('/auth/login.php');
}

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR full_name = ?');
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Hỗ trợ mật khẩu thường của dữ liệu mẫu; tài khoản mới luôn dùng password_hash.
$passwordCorrect = $user && (password_verify($password, $user['password']) || hash_equals($user['password'], $password));

if (!$passwordCorrect) {
    set_flash('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
    redirect('/auth/login.php');
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
set_flash('success', 'Đăng nhập thành công.');

if ($user['role'] === 'admin') {
    redirect('/admin/index.php');
}
if ($user['role'] === 'staff') {
    redirect('/staff/index.php');
}
redirect('/index.php');
