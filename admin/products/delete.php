<?php
require_once __DIR__ . '/../../config/config.php'; require_admin(); require_post(); $id = get_id();
$check = $conn->prepare('SELECT COUNT(*) AS total FROM order_items oi JOIN product_variants v ON v.id = oi.variant_id WHERE v.product_id = ?'); $check->bind_param('i', $id); $check->execute();
if ($check->get_result()->fetch_assoc()['total'] > 0) { set_flash('error', 'Không thể xóa sản phẩm đã xuất hiện trong đơn hàng.'); redirect('/admin/products/index.php'); }
$productStmt = $conn->prepare('SELECT thumbnail FROM products WHERE id = ?'); $productStmt->bind_param('i', $id); $productStmt->execute(); $product = $productStmt->get_result()->fetch_assoc();
$variantStmt = $conn->prepare('SELECT variant_image FROM product_variants WHERE product_id = ?'); $variantStmt->bind_param('i', $id); $variantStmt->execute(); $variantImages = $variantStmt->get_result()->fetch_all(MYSQLI_ASSOC);
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('DELETE FROM product_variants WHERE product_id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM product_specs WHERE product_id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    $conn->commit();
    delete_uploaded_image($product['thumbnail'] ?? null, 'products');
    foreach ($variantImages as $variant) delete_uploaded_image($variant['variant_image'], 'variants');
    set_flash('success', 'Đã xóa sản phẩm.');
} catch (Throwable $exception) { $conn->rollback(); set_flash('error', 'Không thể xóa sản phẩm.'); }
redirect('/admin/products/index.php');
