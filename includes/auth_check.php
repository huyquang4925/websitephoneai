<?php
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Vui lòng đăng nhập.');
        redirect('/auth/login.php');
    }
}

function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

function is_customer(): bool
{
    return current_role() === 'customer';
}

function deny_access(string $message = 'Bạn không có quyền truy cập trang này.'): void
{
    http_response_code(403);
    exit($message);
}

function require_customer(): void
{
    require_login();
    if (!is_customer()) {
        deny_access('Chức năng này chỉ dành cho khách hàng.');
    }
}

function require_customer_api(): void
{
    if (!is_logged_in()) {
        json_response(false, 'Vui lòng đăng nhập.', [], 401);
    }
    if (!is_customer()) {
        json_response(false, 'Chức năng này chỉ dành cho khách hàng.', [], 403);
    }
}

function require_admin(): void
{
    require_login();
    if (current_role() !== 'admin') {
        deny_access();
    }
}

function require_staff(): void
{
    require_login();
    if (!in_array(current_role(), ['staff', 'admin'], true)) {
        deny_access();
    }
}
