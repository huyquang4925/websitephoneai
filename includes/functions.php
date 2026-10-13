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

function get_product_min_price(mysqli $conn, int $productId): ?float
{
    $stmt = $conn->prepare('SELECT MIN(COALESCE(sale_price, price)) AS min_price FROM product_variants WHERE product_id = ?');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ($row && $row['min_price'] !== null) ? (float) $row['min_price'] : null;
}

function get_product_total_stock(mysqli $conn, int $productId): int
{
    $stmt = $conn->prepare('SELECT COALESCE(SUM(stock_quantity), 0) AS total_stock FROM product_variants WHERE product_id = ?');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total_stock'] ?? 0);
}

function find_products(mysqli $conn, array $filters = [], int $limit = 5): array
{
    $sql = 'SELECT p.id, p.name, p.slug, p.thumbnail, b.id AS brand_id, b.name AS brand_name,
        s.screen_size, s.screen_tech, s.refresh_rate, s.chipset, s.gpu, s.ram,
        s.camera_rear, s.camera_front, s.battery_capacity, s.charging_speed, s.os,
        MIN(COALESCE(v.sale_price, v.price)) AS min_price,
        MAX(COALESCE(v.sale_price, v.price)) AS max_price,
        SUM(v.stock_quantity) AS total_stock
        FROM products p
        JOIN brands b ON b.id = p.brand_id
        LEFT JOIN product_specs s ON s.product_id = p.id
        LEFT JOIN product_variants v ON v.product_id = p.id
        WHERE p.is_active = 1';
    $params = [];
    $types = '';

    if (!empty($filters['product_id'])) {
        $sql .= ' AND p.id = ?';
        $params[] = (int) $filters['product_id'];
        $types .= 'i';
    }
    if (!empty($filters['brand_id'])) {
        $sql .= ' AND b.id = ?';
        $params[] = (int) $filters['brand_id'];
        $types .= 'i';
    }
    if (!empty($filters['exclude_brand_ids'])) {
        foreach (array_unique(array_map('intval', (array) $filters['exclude_brand_ids'])) as $excludeId) {
            if ($excludeId <= 0) {
                continue;
            }
            $sql .= ' AND b.id != ?';
            $params[] = $excludeId;
            $types .= 'i';
        }
    }
    if (!empty($filters['keyword'])) {
        $keyword = '%' . $filters['keyword'] . '%';
        $sql .= ' AND (p.name LIKE ? OR b.name LIKE ?)';
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= 'ss';
    }
    if (!empty($filters['ram'])) {
        $sql .= ' AND s.ram >= ?';
        $params[] = (int) $filters['ram'];
        $types .= 'i';
    }
    if (!empty($filters['os'])) {
        $osMap = ['ios' => '%iOS%', 'android' => '%Android%'];
        $os = strtolower((string) $filters['os']);
        if (isset($osMap[$os])) {
            $sql .= ' AND s.os LIKE ?';
            $params[] = $osMap[$os];
            $types .= 's';
        }
    }
    if (!empty($filters['chipset'])) {
        $sql .= ' AND s.chipset LIKE ?';
        $params[] = '%' . $filters['chipset'] . '%';
        $types .= 's';
    }
    if (!empty($filters['screen_tech'])) {
        $sql .= ' AND s.screen_tech LIKE ?';
        $params[] = '%' . $filters['screen_tech'] . '%';
        $types .= 's';
    }
    if (!empty($filters['refresh_min'])) {
        $sql .= ' AND s.refresh_rate >= ?';
        $params[] = (int) $filters['refresh_min'];
        $types .= 'i';
    }
    if (!empty($filters['storage_min'])) {
        $sql .= " AND EXISTS (SELECT 1 FROM product_variants vv WHERE vv.product_id = p.id AND CAST(REPLACE(vv.storage, 'GB', '') AS UNSIGNED) >= ?)";
        $params[] = (int) $filters['storage_min'];
        $types .= 'i';
    }
    if (!empty($filters['sim_dual'])) {
        $sql .= " AND s.sim LIKE '2 %'";
    }
    if (!empty($filters['screen_max'])) {
        $sql .= " AND CAST(REPLACE(s.screen_size, ' inch', '') AS DECIMAL(4,2)) <= ?";
        $params[] = (float) $filters['screen_max'];
        $types .= 'd';
    }
    if (!empty($filters['screen_min'])) {
        $sql .= " AND CAST(REPLACE(s.screen_size, ' inch', '') AS DECIMAL(4,2)) >= ?";
        $params[] = (float) $filters['screen_min'];
        $types .= 'd';
    }
    if (!empty($filters['want_5g'])) {
        $sql .= " AND p.name LIKE '%5G%'";
    }

    $sql .= ' GROUP BY p.id, p.name, p.slug, p.thumbnail, b.id, b.name, s.screen_size, s.screen_tech,
        s.refresh_rate, s.chipset, s.gpu, s.ram, s.camera_rear, s.camera_front, s.battery_capacity,
        s.charging_speed, s.os';

    $having = [];
    if (isset($filters['budget_min']) && (float) $filters['budget_min'] > 0) {
        $having[] = 'min_price >= ?';
        $params[] = (float) $filters['budget_min'];
        $types .= 'd';
    }
    if (isset($filters['budget_max']) && (float) $filters['budget_max'] > 0) {
        $having[] = 'min_price <= ?';
        $params[] = (float) $filters['budget_max'];
        $types .= 'd';
    }
    if (!empty($filters['in_stock_only'])) {
        $having[] = 'total_stock > 0';
    }
    if ($having) {
        $sql .= ' HAVING ' . implode(' AND ', $having);
    }

    $needOrders = [
        'pin' => 's.battery_capacity DESC, s.charging_speed DESC',
        'gaming' => 's.refresh_rate DESC, s.ram DESC',
        'camera' => 'CHAR_LENGTH(s.camera_rear) DESC, min_price ASC',
        'quay-video' => 'CHAR_LENGTH(s.camera_rear) DESC, s.ram DESC',
        'hoc-tap' => 'min_price ASC, s.battery_capacity DESC',
        'cong-viec' => 's.ram DESC, min_price ASC',
        'giai-tri' => 's.battery_capacity DESC, s.refresh_rate DESC',
        'hieu-nang' => 's.ram DESC, s.refresh_rate DESC',
        'man-hinh' => 's.refresh_rate DESC, s.screen_size DESC',
    ];
    $order = $needOrders[$filters['need'] ?? ''] ?? 'min_price ASC';
    // Khi lọc total_stock > 0 thì mọi dòng đều còn hàng nên không cần sort theo tồn kho
    $sql .= ' ORDER BY ' . $order . ', min_price ASC';
    $sql .= ' LIMIT ' . max(1, min(10, $limit));

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_product_row(mysqli $conn, int $productId): ?array
{
    $rows = find_products($conn, ['product_id' => $productId], 1);
    return $rows[0] ?? null;
}

function get_product_variants(mysqli $conn, int $productId): array
{
    $stmt = $conn->prepare('SELECT id, color, storage, price, sale_price, stock_quantity FROM product_variants WHERE product_id = ? ORDER BY COALESCE(sale_price, price) ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_order_items(mysqli $conn, int $orderId): array
{
    $stmt = $conn->prepare('SELECT oi.*, p.name AS product_name, p.thumbnail, v.color, v.storage, v.variant_image FROM order_items oi JOIN product_variants v ON v.id = oi.variant_id JOIN products p ON p.id = v.product_id WHERE oi.order_id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
