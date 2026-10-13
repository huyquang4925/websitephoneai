<?php
if (!defined('BASE_URL')) { exit; }

$currentPath = $_SERVER['PHP_SELF'] ?? '';
if (str_contains($currentPath, '/admin/')) {
    $bodyClass = trim(($bodyClass ?? '') . ' admin-page');
    $pageCss = array_unique(array_merge($pageCss ?? [], ['admin']));
} elseif (str_contains($currentPath, '/staff/')) {
    $bodyClass = trim(($bodyClass ?? '') . ' staff-page');
    $pageCss = array_unique(array_merge($pageCss ?? [], ['staff']));
} elseif (str_contains($currentPath, '/auth/')) {
    $bodyClass = trim(($bodyClass ?? '') . ' auth-page');
    $pageCss = array_unique(array_merge($pageCss ?? [], ['auth']));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'MCHAphone Store') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : '1' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css?v=<?= file_exists(__DIR__ . '/../assets/css/chatbot.css') ? filemtime(__DIR__ . '/../assets/css/chatbot.css') : '1' ?>">
    <?php foreach (($pageCss ?? []) as $css): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= e($css) ?>.css?v=<?= file_exists(__DIR__ . '/../assets/css/' . $css . '.css') ? filemtime(__DIR__ . '/../assets/css/' . $css . '.css') : '1' ?>">
    <?php endforeach; ?>
    <script>window.CHATBOT_CONFIG = { baseUrl: '<?= BASE_URL ?>', isCustomer: <?= is_customer() ? 'true' : 'false' ?> };</script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js?v=<?= file_exists(__DIR__ . '/../assets/js/chatbot.js') ? filemtime(__DIR__ . '/../assets/js/chatbot.js') : '1' ?>" defer></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php require __DIR__ . '/navbar.php'; ?>
<main class="site-main container">
<?php if (str_contains($currentPath, '/admin/')): ?>
<nav class="admin-nav" aria-label="Điều hướng quản trị">
    <a href="<?= BASE_URL ?>/admin/index.php">Tổng quan</a>
    <a href="<?= BASE_URL ?>/admin/brands/index.php">Thương hiệu</a>
    <a href="<?= BASE_URL ?>/admin/products/index.php">Sản phẩm</a>
    <a href="<?= BASE_URL ?>/admin/variants/index.php">Biến thể</a>
    <a href="<?= BASE_URL ?>/admin/users/index.php">Người dùng</a>
    <a href="<?= BASE_URL ?>/admin/orders/index.php">Đơn hàng</a>
</nav>
<?php elseif (str_contains($currentPath, '/staff/')): ?>
<nav class="staff-bar" aria-label="Điều hướng nhân viên"><strong>Khu vực nhân viên</strong><div class="actions"><a href="<?= BASE_URL ?>/staff/index.php">Tổng quan</a><a href="<?= BASE_URL ?>/staff/orders/index.php">Đơn hàng</a></div></nav>
<?php endif; ?>
<?php show_flash(); ?>
