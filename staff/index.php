<?php
require_once __DIR__ . '/../config/config.php';
require_staff();
$pageTitle = 'Khu vực nhân viên';
$pending = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'")->fetch_assoc()['total'];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Tổng quan nhân viên</h1><p>Tiếp nhận và cập nhật tình trạng đơn hàng.</p></div><a class="btn" href="<?= BASE_URL ?>/staff/orders/index.php">Xem tất cả đơn</a></div>
<section class="card staff-stat"><strong><?= $pending ?></strong><div><h2>Đơn đang chờ xử lý</h2><p>Cần xác nhận trước khi chuyển sang giao hàng.</p></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
