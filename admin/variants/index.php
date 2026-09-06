<?php
require_once __DIR__ . '/../../config/config.php'; require_admin();
$productId = max(0, (int) ($_GET['product_id'] ?? 0));
$productName = '';
if ($productId) {
    $pStmt = $conn->prepare('SELECT name FROM products WHERE id = ?');
    $pStmt->bind_param('i', $productId);
    $pStmt->execute();
    $pRes = $pStmt->get_result()->fetch_assoc();
    if ($pRes) { $productName = $pRes['name']; }
}
$sql = 'SELECT v.*, p.name AS product_name FROM product_variants v JOIN products p ON p.id = v.product_id';
if ($productId) { $stmt = $conn->prepare($sql . ' WHERE v.product_id = ? ORDER BY v.id DESC'); $stmt->bind_param('i', $productId); $stmt->execute(); $variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); }
else $variants = $conn->query($sql . ' ORDER BY v.id DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Biến thể sản phẩm' . ($productName ? ' - ' . $productName : ''); require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Biến thể sản phẩm</h1>
        <p><?= $productName ? 'Đang lọc theo sản phẩm: <strong>' . e($productName) . '</strong>' : 'Quản lý màu sắc, bộ nhớ, giá và tồn kho.' ?></p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a class="btn" href="create.php<?= $productId ? '?product_id='.$productId : '' ?>">Thêm biến thể</a>
        <?php if ($productId): ?>
            <a class="btn btn-outline" href="index.php">Thoát</a>
        <?php endif; ?>
    </div>
</div>
<div class="table-wrap"><table><tr><th>Sản phẩm</th><th>Màu</th><th>Bộ nhớ</th><th>Giá</th><th>Giá giảm</th><th>Kho</th><th>Thao tác</th></tr><?php foreach ($variants as $variant): ?><tr><td><strong><?= e($variant['product_name']) ?></strong></td><td><?= e($variant['color']) ?></td><td><?= e($variant['storage']) ?></td><td><?= format_price($variant['price']) ?></td><td><?= $variant['sale_price'] ? format_price($variant['sale_price']) : '-' ?></td><td><span class="badge"><?= $variant['stock_quantity'] ?></span></td><td><div class="actions"><a class="btn btn-small" href="edit.php?id=<?= $variant['id'] ?>">Sửa</a><form class="inline-form" data-confirm="Bạn có chắc chắn muốn xóa biến thể &quot;<?= e($variant['product_name']) ?> - <?= e($variant['color']) ?> (<?= e($variant['storage']) ?>)&quot; không?" action="delete.php" method="post"><input type="hidden" name="id" value="<?= $variant['id'] ?>"><button class="btn-danger btn-small" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
