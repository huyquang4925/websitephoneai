<?php
require_once __DIR__ . '/../../config/config.php'; require_admin();
$products = $conn->query('SELECT p.*, b.name AS brand_name, COUNT(v.id) AS variant_count FROM products p JOIN brands b ON b.id = p.brand_id LEFT JOIN product_variants v ON v.product_id = p.id GROUP BY p.id ORDER BY p.id DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Quản lý sản phẩm'; require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1>Quản lý sản phẩm</h1><p>Thêm, sửa và quản lý thông số điện thoại.</p></div><a class="btn" href="create.php">Thêm sản phẩm</a></div>
<div class="table-wrap"><table><tr><th>ID</th><th>Tên</th><th>Thương hiệu</th><th>Trạng thái</th><th>Biến thể</th><th>Thao tác</th></tr>
<?php foreach ($products as $product): ?><tr><td><?= $product['id'] ?></td><td><strong><?= e($product['name']) ?></strong></td><td><?= e($product['brand_name']) ?></td><td><span class="badge <?= $product['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>"><?= $product['is_active'] ? 'Đang bán' : 'Đang ẩn' ?></span></td><td><?= $product['variant_count'] ?></td><td><div class="actions"><a class="btn btn-outline btn-small" href="detail.php?id=<?= $product['id'] ?>">Chi tiết</a><a class="btn btn-small" href="edit.php?id=<?= $product['id'] ?>">Sửa</a><form class="inline-form" data-confirm="Bạn có chắc chắn muốn xóa sản phẩm &quot;<?= e($product['name']) ?>&quot; không?" action="delete.php" method="post"><input type="hidden" name="id" value="<?= $product['id'] ?>"><button class="btn-danger btn-small" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
