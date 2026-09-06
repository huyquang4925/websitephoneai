<?php
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<p class="message ' . e($flash['type']) . '">' . e($flash['message']) . '</p>';
}

function post_value(string $name, string $default = ''): string
{
    return trim($_POST[$name] ?? $default);
}

function get_id(string $name = 'id'): int
{
    return max(0, (int) ($_GET[$name] ?? $_POST[$name] ?? 0));
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function require_post(): void
{
    if (!is_post()) {
        http_response_code(405);
        exit('Phương thức không được hỗ trợ.');
    }
}

function make_slug(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text ?: '');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function format_price($price): string
{
    return number_format((float) $price, 0, ',', '.') . ' đ';
}

function sale_price(array $variant): float
{
    if (!empty($variant['sale_price'])) {
        return (float) $variant['sale_price'];
    }
    return (float) $variant['price'];
}

function json_response(bool $success, string $message, array $data = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function valid_order_status(string $status): bool
{
    return in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true);
}

function role_label(string $role): string
{
    $roles = [
        'admin' => 'Admin',
        'staff' => 'Nhân viên',
        'customer' => 'Khách hàng'
    ];
    return $roles[$role] ?? $role;
}

function order_status_label(string $status): string
{
    $statuses = [
        'pending' => 'Đang xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    return $statuses[$status] ?? $status;
}

function upload_image(string $fieldName, string $folder): ?string
{
    $allowedFolders = ['products', 'variants', 'brands'];
    if (!in_array($folder, $allowedFolders, true)) {
        throw new Exception('Thư mục lưu ảnh không hợp lệ.');
    }

    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Không thể tải ảnh lên.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Ảnh không được lớn hơn 2 MB.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($mimeTypes[$mime])) {
        throw new Exception('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    $uploadDirectory = dirname(__DIR__) . '/assets/images/' . $folder;
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }
    $fileName = time() . '-' . bin2hex(random_bytes(4)) . '.' . $mimeTypes[$mime];
    $destination = $uploadDirectory . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Không thể lưu ảnh vào thư mục uploads.');
    }

    return 'assets/images/' . $folder . '/' . $fileName;
}

function delete_uploaded_image(?string $path, string $folder): void
{
    if (!$path || !in_array($folder, ['products', 'variants', 'brands'], true)) {
        return;
    }

    $prefix = 'assets/images/' . $folder . '/';
    if (!str_starts_with($path, $prefix) || basename($path) !== substr($path, strlen($prefix))) {
        return;
    }

    $file = dirname(__DIR__) . '/' . $path;
    if (is_file($file)) {
        unlink($file);
    }
}

function image_exists(?string $path, string $folder): bool
{
    if (!$path || !str_starts_with($path, 'assets/images/' . $folder . '/')) {
        return false;
    }
    return is_file(dirname(__DIR__) . '/' . $path);
}

function get_cart_items(mysqli $conn): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $items = [];
    $stmt = $conn->prepare('SELECT v.*, p.name AS product_name, p.thumbnail FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?');

    foreach ($cart as $variantId => $quantity) {
        $variantId = (int) $variantId;
        $stmt->bind_param('i', $variantId);
        $stmt->execute();
        $variant = $stmt->get_result()->fetch_assoc();

        if ($variant) {
            $variant['quantity'] = (int) $quantity;
            $variant['unit_price'] = sale_price($variant);
            $variant['subtotal'] = $variant['unit_price'] * $variant['quantity'];
            $items[] = $variant;
        }
    }

    return $items;
}

function get_order_items(mysqli $conn, int $orderId): array
{
    $stmt = $conn->prepare('SELECT oi.*, p.name AS product_name, p.thumbnail, v.color, v.storage, v.variant_image FROM order_items oi JOIN product_variants v ON v.id = oi.variant_id JOIN products p ON p.id = v.product_id WHERE oi.order_id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
