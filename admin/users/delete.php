<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();
require_post();

$id = get_id();
$pin = post_value('pin');

if (!hash_equals(ADMIN_DELETE_PIN, (string) $pin)) {
    set_flash('error', 'Mã PIN không đúng. Không thể xóa tài khoản.');
    redirect('/admin/users/index.php');
}

$stmtUser = $conn->prepare('SELECT id, role, full_name FROM users WHERE id = ?');
$stmtUser->bind_param('i', $id);
$stmtUser->execute();
$targetUser = $stmtUser->get_result()->fetch_assoc();

if (!$targetUser) {
    set_flash('error', 'Người dùng không tồn tại.');
    redirect('/admin/users/index.php');
}

if ($targetUser['role'] === 'admin') {
    set_flash('error', 'Không được phép xóa tài khoản Admin.');
    redirect('/admin/users/index.php');
}

if ($id === (int) $_SESSION['user_id']) {
    set_flash('error', 'Không thể tự xóa tài khoản đang đăng nhập.');
    redirect('/admin/users/index.php');
}

$check = $conn->prepare('SELECT COUNT(*) AS total FROM orders WHERE user_id = ?');
$check->bind_param('i', $id);
$check->execute();
if ($check->get_result()->fetch_assoc()['total'] > 0) {
    set_flash('error', 'Không thể xóa người dùng đã có đơn hàng.');
} else {
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    set_flash('success', 'Đã xóa người dùng ' . $targetUser['full_name'] . ' thành công.');
}

redirect('/admin/users/index.php');
