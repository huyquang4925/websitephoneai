<?php
require_once __DIR__ . '/../../config/config.php'; require_admin();
$brands = $conn->query('SELECT b.*, COUNT(p.id) AS product_count FROM brands b LEFT JOIN products p ON p.brand_id = b.id GROUP BY b.id ORDER BY b.name')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Thương hiệu'; require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><h1>Thương hiệu</h1><p>Quản lý hãng sản xuất điện thoại.</p></div><a class="btn" href="create.php">Thêm thương hiệu</a></div>
<div class="table-wrap"><table><tr><th>Logo</th><th>ID</th><th>Tên</th><th>Slug</th><th>Sản phẩm</th><th>Thao tác</th></tr><?php foreach ($brands as $brand): ?><tr><td><?php if (image_exists($brand['logo_url'], 'brands')): ?><img class="admin-variant-image" src="<?= BASE_URL ?>/<?= e($brand['logo_url']) ?>" alt="<?= e($brand['name']) ?>" style="height:36px;width:36px;object-fit:contain;"><?php else: ?><span style="color:var(--muted);">-</span><?php endif; ?></td><td><?= $brand['id'] ?></td><td><strong><?= e($brand['name']) ?></strong></td><td><?= e($brand['slug']) ?></td><td><?= $brand['product_count'] ?></td><td><div class="actions"><a class="btn btn-small" href="edit.php?id=<?= $brand['id'] ?>">Sửa</a><form class="inline-form" data-confirm="Bạn có chắc chắn muốn xóa thương hiệu &quot;<?= e($brand['name']) ?>&quot; không?" action="delete.php" method="post"><input type="hidden" name="id" value="<?= $brand['id'] ?>"><button class="btn-danger btn-small" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
