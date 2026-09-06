<?php $cartCount = is_customer() ? array_sum($_SESSION['cart'] ?? []) : 0; ?>
<header class="site-header">
    <div class="container nav-wrap">
        <div class="nav-brand-group">
            <button type="button" class="nav-back-btn" id="navBackBtn" data-home="<?= BASE_URL ?>/index.php" aria-label="Quay lại" title="Quay lại trang trước">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </button>
            <a class="brand" href="<?= BASE_URL ?>/index.php"><span>MCHAphone</span> Store</a>
        </div>
        <button class="menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false">☰</button>
        <nav class="main-nav" aria-label="Điều hướng chính">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
            <a href="<?= BASE_URL ?>/search.php">Sản phẩm</a>
            <?php if (is_customer()): ?>
                <a href="<?= BASE_URL ?>/orders/history.php">Đơn hàng</a>
            <?php endif; ?>
            <?php if (in_array(current_role(), ['staff', 'admin'], true)): ?>
                <a href="<?= BASE_URL ?>/staff/index.php">Nhân viên</a>
            <?php endif; ?>
            <?php if (current_role() === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/index.php">Quản trị</a>
            <?php endif; ?>
        </nav>
        <form class="nav-search" action="<?= BASE_URL ?>/search.php" method="get">
            <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm điện thoại..." aria-label="Từ khóa tìm kiếm" required>
            <button type="submit" aria-label="Tìm kiếm">Tìm</button>
        </form>
        <div class="nav-actions">
            <?php if (is_customer()): ?>
                <a class="cart-link" href="<?= BASE_URL ?>/cart/index.php">Giỏ hàng <span><?= $cartCount ?></span></a>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <div class="account-menu">
                    <button class="account-menu-toggle" type="button" aria-expanded="false" aria-haspopup="true" aria-controls="accountDropdown">
                        <span class="account-avatar" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <span class="account-name"><?= e($_SESSION['full_name']) ?></span>
                        <svg class="account-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="account-dropdown" id="accountDropdown" hidden>
                        <a href="<?= BASE_URL ?>/auth/profile.php">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                            Thông tin cá nhân
                        </a>
                        <a href="<?= BASE_URL ?>/auth/change_password.php">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Đổi mật khẩu
                        </a>
                        <a class="account-logout" href="<?= BASE_URL ?>/auth/logout.php">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                            Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php">Đăng nhập</a>
                <a class="btn btn-small" href="<?= BASE_URL ?>/auth/register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>
