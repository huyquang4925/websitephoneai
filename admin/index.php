<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
$counts = [];
foreach (['brands','products','product_variants','users','orders'] as $table) {
    $counts[$table] = $conn->query("SELECT COUNT(*) AS total FROM $table")->fetch_assoc()['total'];
}
$labels = ['brands'=>'Thương hiệu','products'=>'Sản phẩm','product_variants'=>'Biến thể','users'=>'Người dùng','orders'=>'Đơn hàng'];
$pageTitle = 'Trang quản trị'; require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Tổng quan quản trị</h1><p>Theo dõi dữ liệu chính của cửa hàng.</p></div></div>
<section class="stat-grid"><?php foreach ($counts as $name => $total): ?><a class="stat-card" href="<?= $name === 'product_variants' ? 'variants' : $name ?>/index.php"><span><?= e($labels[$name]) ?></span><strong><?= $total ?></strong></a><?php endforeach; ?></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
