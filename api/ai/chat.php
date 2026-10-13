<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/ai.php';

// ==================== Tiện ích xử lý văn bản tiếng Việt ====================

// Chuyển về chữ thường và bỏ dấu tiếng Việt, giữ nguyên ký tự còn lại.
function chat_fold_text(string $text): string
{
    static $map = [
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd',
    ];
    return strtr(mb_strtolower($text, 'UTF-8'), $map);
}

// Fold + chỉ giữ a-z, 0-9 và khoảng trắng (dùng để khớp từ khóa).
function chat_normalize_text(string $text): string
{
    $text = preg_replace('/[^a-z0-9]+/', ' ', chat_fold_text($text));
    return trim((string) preg_replace('/\s+/', ' ', $text));
}

// ==================== Trích xuất tiêu chí từ câu hỏi ====================

function chat_extract_budget(string $fold): array
{
    // Khoảng giá "từ X đến Y triệu"
    if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:den|toi)\s*(\d+(?:[.,]\d+)?)\s*(?:trieu|tr|cu|chai)(?![a-z0-9])/', $fold, $m)) {
        $a = (float) str_replace(',', '.', $m[1]) * 1000000;
        $b = (float) str_replace(',', '.', $m[2]) * 1000000;
        return ['min' => min($a, $b), 'max' => max($a, $b)];
    }

    $values = [];
    // 1. Số + đơn vị triệu/tr/củ/chai: "10 triệu", "10.5tr", "10 củ"
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:trieu|tr|cu|chai)(?![a-z0-9])/', $fold, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $values[] = ['value' => (float) str_replace(',', '.', $hit[1]) * 1000000, 'pos' => (int) strpos($fold, $hit[0])];
        }
    }
    // 2. Số + k/nghìn: "500k"
    if (!$values && preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:k|nghin|ngan)(?![a-z0-9])/', $fold, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $values[] = ['value' => (float) str_replace(',', '.', $hit[1]) * 1000, 'pos' => (int) strpos($fold, $hit[0])];
        }
    }
    // 3. Số liền: 10000000
    if (!$values && preg_match_all('/\b(\d{7,10})\b/', $fold, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $values[] = ['value' => (float) $hit[1], 'pos' => (int) strpos($fold, $hit[0])];
        }
    }
    // 4. Số có dấu ngăn cách: 10.000.000
    if (!$values && preg_match_all('/\b(\d{1,3}(?:[.,]\d{3})+)\b/', $fold, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $values[] = ['value' => (float) str_replace(['.', ','], '', $hit[1]), 'pos' => (int) strpos($fold, $hit[0])];
        }
    }
    if (!$values) {
        return ['min' => null, 'max' => null];
    }

    // Hai mức giá "từ X ... đến Y"
    if (count($values) >= 2) {
        $between = substr($fold, $values[0]['pos'], $values[1]['pos'] - $values[0]['pos']);
        if (preg_match('/\bden\b/', $between)) {
            return ['min' => min($values[0]['value'], $values[1]['value']), 'max' => max($values[0]['value'], $values[1]['value'])];
        }
    }

    $first = $values[0];
    $before = substr($fold, 0, $first['pos']);
    $lastWords = substr($before, -24);
    if (preg_match('/\b(tu|tren|toi thieu|tro len)\b/', $lastWords)) {
        return ['min' => $first['value'], 'max' => null];
    }
    // Mặc định coi là mức tối đa: "dưới 10 triệu", "10 triệu", "khoảng 10 triệu"
    return ['min' => null, 'max' => $first['value']];
}

function chat_extract_ram(string $norm): ?int
{
    if (preg_match('/\b(?:ram|bo nho trong)\s*(\d{1,3})/', $norm, $m) || preg_match('/\b(\d{1,3})\s*gb\b/', $norm, $m)) {
        $ram = (int) $m[1];
        if ($ram >= 2 && $ram <= 24) { // giá trị lớn hơn là dung lượng lưu trữ, không phải RAM
            return $ram;
        }
    }
    return null;
}

// Trích xuất tiêu chí kỹ thuật: chipset, tần số quét, bộ nhớ, OS, 2 SIM, màn hình nhỏ/lớn, 5G.
function chat_extract_tech(mysqli $conn, string $norm): array
{
    $tech = [
        'chipset' => null, 'refresh_min' => null, 'storage_min' => null, 'os' => null,
        'sim_dual' => false, 'screen_tech' => null, 'screen_max' => null, 'screen_min' => null, 'want_5g' => false,
    ];

    if (preg_match('/\b(\d{2,3})\s*hz\b/', $norm, $m)) {
        $hz = (int) $m[1];
        if ($hz >= 90 && $hz <= 240) {
            $tech['refresh_min'] = $hz;
        }
    }
    if (preg_match('/\b(\d{3,4})\s*gb\b/', $norm, $m)) {
        $gb = (int) $m[1];
        if ($gb >= 64 && $gb <= 1024) {
            $tech['storage_min'] = $gb;
        }
    }
    if (preg_match('/\b(1tb|1024\s*gb)\b/', $norm)) {
        $tech['storage_min'] = 1024;
    }
    if (preg_match('/\b(2 sim|hai sim|dual sim)\b/', $norm)) {
        $tech['sim_dual'] = true;
    }
    if (preg_match('/\bandroid\b/', $norm)) {
        $tech['os'] = 'android';
    } elseif (preg_match('/\bios\b/', $norm)) {
        $tech['os'] = 'ios';
    }
    if (preg_match('/\bamoled\b/', $norm)) {
        $tech['screen_tech'] = 'AMOLED';
    } elseif (preg_match('/\boled\b/', $norm)) {
        $tech['screen_tech'] = 'OLED';
    }
    if (preg_match('/\b5g\b/', $norm)) {
        $tech['want_5g'] = true;
    }
    // Ưu tiên nhận "màn nhỏ" trước vì "không thích màn to" cũng chứa "màn to"
    if (preg_match('/\b(man hinh nho|man nho|man hinh qua to|man hinh qua lon|khong thich man to|khong thich man lon|nho gon|gon gang|cam tay|de mang)\b/', $norm)) {
        $tech['screen_max'] = 6.5;
    } elseif (preg_match('/\b(man hinh lon|man hinh to|man lon|man rong|man to)\b/', $norm)) {
        $tech['screen_min'] = 6.7;
    }

    // Chipset: khớp tên đầy đủ có trong DB trước, rồi đến họ chip phổ biến
    $chipsets = $conn->query('SELECT DISTINCT chipset FROM product_specs')->fetch_all(MYSQLI_ASSOC);
    $best = '';
    foreach ($chipsets as $row) {
        $chipName = chat_normalize_text((string) $row['chipset']);
        if ($chipName !== '' && strpos($norm, $chipName) !== false && mb_strlen($chipName) > mb_strlen($best)) {
            $best = $chipName;
        }
    }
    if ($best !== '') {
        $tech['chipset'] = $best;
    } elseif (preg_match('/\b(snapdragon|snap dragon)\b/', $norm)) {
        $tech['chipset'] = 'Snapdragon';
    } elseif (preg_match('/\b(dimensity|mediatek|media tek)\b/', $norm)) {
        $tech['chipset'] = 'Dimensity';
    } elseif (preg_match('/\bexynos\b/', $norm)) {
        $tech['chipset'] = 'Exynos';
    } elseif (preg_match('/\bapple a\d+\b/', $norm)) {
        $tech['chipset'] = 'Apple';
    }
    return $tech;
}

function chat_detect_all_brands(mysqli $conn, string $norm): array
{
    $aliases = ['iphone' => 'apple', 'galaxy' => 'samsung', 'redmi' => 'xiaomi'];
    $rows = $conn->query('SELECT id, name, slug FROM brands')->fetch_all(MYSQLI_ASSOC);
    $found = [];
    foreach ($aliases as $needle => $slug) {
        if (strpos($norm, $needle) !== false) {
            foreach ($rows as $row) {
                if ($row['slug'] === $slug) {
                    $found[$row['id']] = $row;
                    break;
                }
            }
        }
    }
    foreach ($rows as $row) {
        if (strpos($norm, chat_normalize_text($row['name'])) !== false || strpos($norm, $row['slug']) !== false) {
            $found[$row['id']] = $row;
        }
    }
    return array_values($found);
}

function chat_detect_brand(mysqli $conn, string $norm): ?array
{
    $brands = chat_detect_all_brands($conn, $norm);
    return $brands[0] ?? null;
}

function chat_detect_need(string $norm): string
{
    $patterns = [
        'gaming' => '/\b(choi game|game|gaming|esports)\b/',
        'giai-tri' => '/\b(giai tri|xem phim|nghe nhac|xem video)\b/',
        'quay-video' => '/\b(quay video|quay phim|video|vlog)\b/',
        'camera' => '/\b(camera|chup anh|anh dep|selfie|may anh|chup dem)\b/',
        'pin' => '/\b(pin|trau|battery|sac nhanh|dung pin)\b/',
        'hoc-tap' => '/\b(hoc tap|hoc bai|sinh vien|du hoc|hoc online)\b/',
        'cong-viec' => '/\b(cong viec|lam viec|van phong|di lam|kinh doanh|ban hang)\b/',
        'man-hinh' => '/\b(man hinh|hien thi|amoled)\b|\d+\s*hz\b/',
        'hieu-nang' => '/\b(hieu nang|manh|cau hinh|chip|flagship|sieu pham)\b/',
    ];
    foreach ($patterns as $need => $pattern) {
        if (preg_match($pattern, $norm)) {
            return $need;
        }
    }
    return '';
}

function chat_detect_intent(string $norm, bool $hasCriteria): string
{
    if (preg_match('/\b(so sanh|so voi|khac nhau|khac gi|vs)\b/', $norm)) {
        return 'compare';
    }
    if (preg_match('/\b(con hang|het hang|ton kho|con ban|con khong|hang khong|van con)\b/', $norm)) {
        return 'stock';
    }
    if (preg_match('/\b(gia|bao nhieu|price|cost)\b/', $norm)) {
        return 'price';
    }
    if (preg_match('/\b(thong so|cau hinh|chi tiet|spec|danh gia|review)\b/', $norm)) {
        return 'detail';
    }
    if ($hasCriteria || preg_match('/\b(goi y|tu van|phu hop|nen mua|de xuat|can mua|muon mua|mua may|chon may|chon dien thoai)\b/', $norm)) {
        return 'recommend';
    }
    if (preg_match('/\b(tim|tim kiem|co nhung|danh sach|liet ke|re hon|gia thap hon)\b/', $norm)) {
        return 'search';
    }
    return 'general';
}

// Tìm sản phẩm được nhắc tới trong câu: tách câu thành các đoạn (ngăn cách bởi
// "và/với/vs..."), mỗi đoạn lấy sản phẩm khớp tên dài nhất (đã bỏ dấu tiếng Việt).
function chat_find_products_in_text(mysqli $conn, string $norm, int $limit = 2): array
{
    $products = $conn->query('SELECT p.id, p.name, b.name AS brand_name FROM products p JOIN brands b ON b.id = p.brand_id WHERE p.is_active = 1')->fetch_all(MYSQLI_ASSOC);
    $segments = array_filter(array_map('trim', preg_split('/\b(?:va|voi|vs|hay|and|&)\b/', $norm) ?: []), 'strlen');
    $seen = [];
    $result = [];
    foreach ($segments as $segment) {
        $best = null;
        $bestLength = 0;
        $bestScore = 0;
        foreach ($products as $product) {
            $productName = chat_normalize_text($product['name']);
            if ($productName === '') {
                continue;
            }
            $tokens = explode(' ', $productName);
            $matchedLength = 0;
            for ($length = count($tokens); $length >= 1; $length--) {
                $candidate = implode(' ', array_slice($tokens, 0, $length));
                if (mb_strlen($candidate) < 5 && $length < 2) {
                    continue; // bỏ qua từ đơn quá ngắn để tránh khớp nhiễu
                }
                if (strpos($segment, $candidate) !== false) {
                    $matchedLength = mb_strlen($candidate);
                    break;
                }
            }
            if ($matchedLength === 0) {
                continue;
            }
            $score = $matchedLength / mb_strlen($productName);
            if ($matchedLength > $bestLength || ($matchedLength === $bestLength && $score > $bestScore)) {
                $best = $product;
                $bestLength = $matchedLength;
                $bestScore = $score;
            }
        }
        if ($best !== null && $bestScore >= 0.35 && !isset($seen[$best['id']])) {
            $seen[$best['id']] = true;
            $result[] = $best;
            if (count($result) >= $limit) {
                break;
            }
        }
    }
    return $result;
}

// ==================== Trạng thái hội thoại (nhớ tiêu chí giữa các lượt) ====================

function chat_state_key(string $conversationId): string
{
    return 'chatbot_state_' . ($conversationId !== '' ? $conversationId : 'default');
}

function chat_load_state(string $conversationId): array
{
    $states = $_SESSION['chatbot_states'] ?? [];
    foreach ($states as $key => $item) {
        if (!is_array($item) || (time() - (int) ($item['ts'] ?? 0)) > 1800) {
            unset($states[$key]); // phiên quá 30 phút không hoạt động thì quên
        }
    }
    $_SESSION['chatbot_states'] = $states;
    $state = $states[chat_state_key($conversationId)] ?? null;
    if (!is_array($state)) {
        return ['filters' => [], 'last_products' => [], 'last_intent' => ''];
    }
    return [
        'filters' => is_array($state['filters'] ?? null) ? $state['filters'] : [],
        'last_products' => is_array($state['last_products'] ?? null) ? $state['last_products'] : [],
        'last_intent' => (string) ($state['last_intent'] ?? ''),
    ];
}

function chat_save_state(string $conversationId, array $state): void
{
    $state['ts'] = time();
    $states = $_SESSION['chatbot_states'] ?? [];
    $states[chat_state_key($conversationId)] = $state;
    while (count($states) > 6) { // chỉ giữ 6 phiên gần nhất để session không phình to
        uasort($states, function ($a, $b) {
            return ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0);
        });
        $oldest = array_key_first($states);
        unset($states[$oldest]);
    }
    $_SESSION['chatbot_states'] = $states;
}

// ==================== Nhận diện câu hỏi nối tiếp (follow-up) ====================

function chat_detect_followup(string $norm): ?array
{
    // "So sánh nó với iPhone 15"
    if (preg_match('/\bso sanh\b/', $norm) && preg_match('/\b(no|may nay|may do|may vua noi|may vua xem|may vua nhac|may thu nhat|may dau tien)\b/', $norm)) {
        return ['type' => 'compare_refer'];
    }
    // "Máy đầu tiên có bao nhiêu RAM?", "Máy này còn hàng không?"
    if (preg_match('/\b(may dau tien|may thu nhat|may 1|may thu hai|may thu 2|may 2|may nay|may do|may vua noi|may vua xem|no)\b/', $norm)
        && preg_match('/\b(pin|ram|gia|bao nhieu|con hang|con khong|het hang|camera|man hinh|chip|bo nho|thong so|cau hinh|ton kho|sac|he dieu hanh|tan so)\b/', $norm)) {
        $index = 0;
        if (preg_match('/\b(may thu hai|may thu 2|may 2)\b/', $norm)) {
            $index = 1;
        }
        return ['type' => 'refer', 'index' => $index];
    }
    // "Có máy nào rẻ hơn không?"
    if (preg_match('/\b(re hon|gia thap hon|may nao re hon)\b/', $norm)) {
        return ['type' => 'cheaper'];
    }
    // "Nếu tăng thêm 2 triệu thì sao?", "ngân sách lên 15 triệu"
    if (preg_match('/\b(tang them|cong them|tang ngan sach|ngan sach len|ngan sach them|tang len|them ngan sach)\b/', $norm)) {
        $amount = 1000000;
        $mode = 'add';
        if (preg_match('/ngan sach\s+(?:la\s+|len\s+)?(\d+(?:[.,]\d+)?)/', $norm, $m)) {
            $amount = (float) str_replace(',', '.', $m[1]) * 1000000;
            $mode = 'set';
        } elseif (preg_match('/(?:tang them|cong them|tang len|them)\s+(\d+(?:[.,]\d+)?)\s*(trieu|tr|cu|chai)?/', $norm, $m)) {
            $amount = (float) str_replace(',', '.', $m[1]) * 1000000;
        }
        return ['type' => 'increase', 'amount' => $amount, 'mode' => $mode];
    }
    // "Bỏ qua iPhone, tư vấn Android đi." (hãng/OS được caller trích xuất)
    if (preg_match('/\b(bo qua|khong chon|khong thich may|khong lay|khong dung|loai bo|khong can)\b/', $norm)) {
        return ['type' => 'exclude'];
    }
    // "Còn Samsung thì sao?"
    if (preg_match('/(^con |\bcon may\b|\bthi sao\b|\bthi lam sao\b)/', $norm)) {
        return ['type' => 'switch_brand'];
    }
    return null;
}

// ==================== Dựng thẻ sản phẩm trả về cho giao diện ====================

function chat_build_card(mysqli $conn, array $row, ?string $reason = null): array
{
    $image = '';
    if (!empty($row['thumbnail']) && image_exists($row['thumbnail'], 'products')) {
        $image = BASE_URL . '/' . $row['thumbnail'];
    }

    $summary = [];
    if (!empty($row['screen_size'])) {
        $summary[] = 'Màn ' . $row['screen_size'] . (!empty($row['refresh_rate']) ? ' ' . $row['refresh_rate'] . 'Hz' : '');
    }
    if (!empty($row['chipset'])) {
        $summary[] = $row['chipset'];
    }
    if (!empty($row['ram'])) {
        $summary[] = $row['ram'] . 'GB RAM';
    }
    if (!empty($row['battery_capacity'])) {
        $summary[] = 'Pin ' . $row['battery_capacity'] . 'mAh';
    }
    if (!$summary && !empty($row['camera_rear'])) {
        $summary[] = 'Camera ' . $row['camera_rear'];
    }

    $stmt = $conn->prepare('SELECT id, color, storage FROM product_variants WHERE product_id = ? AND stock_quantity > 0 ORDER BY COALESCE(sale_price, price) ASC LIMIT 1');
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $variant = $stmt->get_result()->fetch_assoc();

    $minPrice = $row['min_price'] !== null ? (float) $row['min_price'] : 0.0;

    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'brand_name' => $row['brand_name'],
        'image' => $image,
        'min_price' => $minPrice,
        'max_price' => $row['max_price'] !== null ? (float) $row['max_price'] : 0.0,
        'price_display' => $minPrice > 0 ? format_price($minPrice) : 'Liên hệ',
        'total_stock' => (int) $row['total_stock'],
        'in_stock' => (int) $row['total_stock'] > 0,
        'spec_summary' => implode(' • ', $summary),
        'reason' => $reason,
        'detail_url' => BASE_URL . '/product/detail.php?id=' . (int) $row['id'],
        'variant_id' => $variant !== null ? (int) $variant['id'] : null,
        'variant_label' => $variant !== null ? $variant['color'] . ' • ' . $variant['storage'] : null,
    ];
}

function chat_build_reason(array $row, string $need): string
{
    if ($need === 'pin') {
        $text = 'Pin ' . $row['battery_capacity'] . 'mAh' . (!empty($row['charging_speed']) ? ', sạc nhanh ' . $row['charging_speed'] . 'W' : '') . ' — dùng bền tơi cuối ngày.';
    } elseif ($need === 'gaming') {
        $text = 'Chip ' . $row['chipset'] . ', RAM ' . $row['ram'] . 'GB' . (!empty($row['refresh_rate']) ? ', màn ' . $row['refresh_rate'] . 'Hz' : '') . ' — chơi game mượt.';
    } elseif ($need === 'camera') {
        $text = 'Camera sau ' . $row['camera_rear'] . ', camera trước ' . $row['camera_front'] . ' — chụp ảnh đẹp.';
    } elseif ($need === 'quay-video') {
        $text = 'Camera sau ' . $row['camera_rear'] . ' với RAM ' . $row['ram'] . 'GB — quay video ổn định.';
    } elseif ($need === 'hoc-tap') {
        $text = 'Giá phải chăng, pin ' . $row['battery_capacity'] . 'mAh — đủ dùng cả ngày học.';
    } elseif ($need === 'cong-viec') {
        $text = 'RAM ' . $row['ram'] . 'GB cùng chip ' . $row['chipset'] . ' — chạy nhiều ứng dụng công việc cùng lúc.';
    } elseif ($need === 'giai-tri') {
        $text = 'Pin ' . $row['battery_capacity'] . 'mAh, màn ' . trim(($row['screen_size'] ?? '') . (!empty($row['refresh_rate']) ? ' ' . $row['refresh_rate'] . 'Hz' : '')) . ' — xem phim, nghe nhạc đã.';
    } elseif ($need === 'hieu-nang') {
        $text = 'RAM ' . $row['ram'] . 'GB cùng chip ' . $row['chipset'] . ' — hiệu năng mạnh.';
    } elseif ($need === 'man-hinh') {
        $text = 'Màn ' . $row['screen_size'] . ' ' . $row['screen_tech'] . (!empty($row['refresh_rate']) ? ' ' . $row['refresh_rate'] . 'Hz' : '') . ' — hiển thị đẹp.';
    } else {
        $text = 'Giá hợp lý, đang còn hàng tại cửa hàng.';
    }

    // Luôn kèm một điểm hạn chế nếu có thể xác định từ dữ liệu
    $limitation = '';
    if (!empty($row['refresh_rate']) && (int) $row['refresh_rate'] <= 60) {
        $limitation = 'màn 60Hz cuộn không mượt bằng máy 120Hz';
    } elseif (!empty($row['battery_capacity']) && (int) $row['battery_capacity'] < 4500) {
        $limitation = 'pin ' . $row['battery_capacity'] . 'mAh kém hơn mặt bằng ~5000mAh';
    } elseif (!empty($row['charging_speed']) && (int) $row['charging_speed'] <= 25) {
        $limitation = 'sạc ' . $row['charging_speed'] . 'W hơi chậm';
    } elseif (!empty($row['ram']) && (int) $row['ram'] <= 8) {
        $limitation = 'RAM ' . $row['ram'] . 'GB, mở nhiều ứng dụng sẽ hơi hạn chế';
    }
    return $limitation !== '' ? $text . ' Hạn chế: ' . $limitation . '.' : $text;
}

function chat_build_conclusion(array $cards): string
{
    if (count($cards) < 2) {
        return '';
    }
    $top = $cards[0];
    $cheapest = $cards[0];
    foreach ($cards as $card) {
        if ($card['min_price'] < $cheapest['min_price']) {
            $cheapest = $card;
        }
    }
    if ($top['id'] !== $cheapest['id']) {
        return 'Kết luận: ' . $top['name'] . ' là lựa chọn cân nhất với tiêu chí hiện tại; nếu muốn tiết kiệm hơn, ' . $cheapest['name'] . ' (' . $cheapest['price_display'] . ') vẫn đáp ứng tốt.';
    }
    return 'Kết luận: ' . $top['name'] . ' là lựa chọn cân nhất với tiêu chí hiện tại.';
}

function chat_describe_filters(array $filters): string
{
    $parts = [];
    if (!empty($filters['budget_max'])) {
        $parts[] = 'ngân sách đến ' . format_price($filters['budget_max']);
    }
    if (!empty($filters['budget_min'])) {
        $parts[] = 'giá từ ' . format_price($filters['budget_min']);
    }
    if (!empty($filters['ram'])) {
        $parts[] = 'RAM từ ' . $filters['ram'] . 'GB';
    }
    if (!empty($filters['brand_name'])) {
        $parts[] = 'hãng ' . $filters['brand_name'];
    }
    if (!empty($filters['os'])) {
        $parts[] = $filters['os'] === 'ios' ? 'hệ điều hành iOS' : 'hệ điều hành Android';
    }
    if (!empty($filters['chipset'])) {
        $parts[] = 'chip ' . $filters['chipset'];
    }
    if (!empty($filters['screen_tech'])) {
        $parts[] = 'màn ' . $filters['screen_tech'];
    }
    if (!empty($filters['screen_max'])) {
        $parts[] = 'màn hình ≤ ' . $filters['screen_max'] . ' inch';
    }
    if (!empty($filters['screen_min'])) {
        $parts[] = 'màn hình ≥ ' . $filters['screen_min'] . ' inch';
    }
    if (!empty($filters['refresh_min'])) {
        $parts[] = 'tần số quét từ ' . $filters['refresh_min'] . 'Hz';
    }
    if (!empty($filters['storage_min'])) {
        $parts[] = 'bộ nhớ từ ' . $filters['storage_min'] . 'GB';
    }
    if (!empty($filters['sim_dual'])) {
        $parts[] = 'hỗ trợ 2 SIM';
    }
    if (!empty($filters['want_5g'])) {
        $parts[] = '5G';
    }
    if (!empty($filters['need'])) {
        $labels = [
            'pin' => 'pin trâu', 'gaming' => 'chơi game', 'camera' => 'chụp ảnh', 'quay-video' => 'quay video',
            'hoc-tap' => 'học tập', 'cong-viec' => 'công việc', 'giai-tri' => 'giải trí',
            'hieu-nang' => 'hiệu năng mạnh', 'man-hinh' => 'màn hình tốt',
        ];
        $parts[] = 'nhu cầu ' . ($labels[$filters['need']] ?? $filters['need']);
    }
    if (!empty($filters['excluded_brand_names'])) {
        $parts[] = 'không dùng ' . implode(', ', $filters['excluded_brand_names']);
    }
    return implode(', ', $parts);
}

function chat_variant_lines(array $variants): string
{
    $lines = [];
    foreach (array_slice($variants, 0, 4) as $variant) {
        $lines[] = sprintf(
            '%s %s: %s (còn %d)',
            $variant['color'],
            $variant['storage'],
            format_price($variant['sale_price'] !== null ? $variant['sale_price'] : $variant['price']),
            (int) $variant['stock_quantity']
        );
    }
    return implode('; ', $lines);
}

function chat_suggestions(string $context): array
{
    $sets = [
        'compare' => [
            ['type' => 'suggestion', 'label' => 'So sánh iPhone 15 và Samsung Galaxy S24 Ultra'],
            ['type' => 'suggestion', 'label' => 'So sánh Xiaomi 14 và OPPO Reno11 5G'],
        ],
        'price' => [
            ['type' => 'suggestion', 'label' => 'iPhone 15 giá bao nhiêu'],
            ['type' => 'suggestion', 'label' => 'Samsung Galaxy A55 5G giá bao nhiêu'],
        ],
        'stock' => [
            ['type' => 'suggestion', 'label' => 'iPhone 15 còn hàng không'],
            ['type' => 'suggestion', 'label' => 'Xiaomi 14 còn hàng không'],
        ],
        'detail' => [
            ['type' => 'suggestion', 'label' => 'Thông số iPhone 15'],
            ['type' => 'suggestion', 'label' => 'Thông số Samsung Galaxy S24 Ultra'],
        ],
        'general' => [
            ['type' => 'suggestion', 'label' => 'Tìm điện thoại dưới 10 triệu'],
            ['type' => 'suggestion', 'label' => 'Điện thoại chơi game tốt'],
            ['type' => 'suggestion', 'label' => 'Điện thoại chụp ảnh đẹp'],
            ['type' => 'suggestion', 'label' => 'So sánh iPhone 15 và Samsung Galaxy S24 Ultra'],
        ],
    ];
    return $sets[$context] ?? $sets['general'];
}

// ==================== Ngữ cảnh gửi cho AI ====================

function chat_build_context(mysqli $conn, array $rows, bool $withVariants): array
{
    $context = [];
    foreach ($rows as $row) {
        $item = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'brand' => $row['brand_name'],
            'price_range' => format_price((float) $row['min_price'])
                . ($row['max_price'] !== null && (float) $row['max_price'] > (float) $row['min_price'] ? ' - ' . format_price((float) $row['max_price']) : ''),
            'total_stock' => (int) $row['total_stock'],
            'specs' => [
                'screen' => trim(($row['screen_size'] ?? '') . ' ' . ($row['screen_tech'] ?? '')),
                'refresh_rate' => !empty($row['refresh_rate']) ? $row['refresh_rate'] . 'Hz' : null,
                'chipset' => $row['chipset'] ?? null,
                'ram' => !empty($row['ram']) ? $row['ram'] . 'GB' : null,
                'camera_rear' => $row['camera_rear'] ?? null,
                'camera_front' => $row['camera_front'] ?? null,
                'battery' => !empty($row['battery_capacity']) ? $row['battery_capacity'] . 'mAh' : null,
                'charging' => !empty($row['charging_speed']) ? $row['charging_speed'] . 'W' : null,
                'os' => $row['os'] ?? null,
                'sim' => $row['sim'] ?? null,
            ],
        ];
        if ($withVariants) {
            $item['variants'] = array_map(function ($variant) {
                return [
                    'color' => $variant['color'],
                    'storage' => $variant['storage'],
                    'price' => format_price($variant['sale_price'] !== null ? $variant['sale_price'] : $variant['price']),
                    'stock' => (int) $variant['stock_quantity'],
                ];
            }, get_product_variants($conn, (int) $row['id']));
        }
        $context[] = $item;
    }
    return $context;
}

// ==================== Xử lý request ====================

if (!is_post()) {
    json_response(false, 'Chỉ hỗ trợ POST.', [], 405);
}

// Rate limit cơ bản theo session: tối đa 20 tin nhắn mỗi 60 giây.
$rateNow = time();
$rate = $_SESSION['ai_chat_rate'] ?? null;
if (!is_array($rate) || ($rateNow - (int) ($rate['window_start'] ?? 0)) >= 60) {
    $rate = ['window_start' => $rateNow, 'count' => 0];
}
$rate['count']++;
$_SESSION['ai_chat_rate'] = $rate;
if ($rate['count'] > 20) {
    json_response(false, 'Bạn đang gửi tin nhắn quá nhanh, vui lòng đợi một chút rồi thử lại.', [], 429);
}

$rawBody = file_get_contents('php://input');
$input = json_decode((string) $rawBody, true);
if (!is_array($input)) {
    $input = $_POST;
}

$message = trim((string) ($input['message'] ?? ''));
$message = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message);
if ($message === '') {
    json_response(false, 'Vui lòng nhập câu hỏi.', [], 422);
}
if (mb_strlen($message) > 1000) {
    $message = mb_substr($message, 0, 1000);
}

$history = $input['history'] ?? [];
if (!is_array($history)) {
    $history = [];
}
$cleanHistory = [];
foreach (array_slice($history, -10) as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $role = (string) ($entry['role'] ?? '');
    $content = trim((string) ($entry['content'] ?? ''));
    if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
        continue;
    }
    $cleanHistory[] = ['role' => $role, 'content' => mb_substr($content, 0, 500)];
}

$conversationId = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($input['conversation_id'] ?? '')), 0, 64);

$fold = chat_fold_text($message);
$norm = chat_normalize_text($message);

$budgetNew = chat_extract_budget($fold);
$ramNew = chat_extract_ram($norm);
$techNew = chat_extract_tech($conn, $norm);
$brandNew = chat_detect_brand($conn, $norm);
$needNew = chat_detect_need($norm);
$hasCriteria = ($budgetNew['min'] !== null || $budgetNew['max'] !== null || $ramNew !== null || $needNew !== ''
    || $techNew['os'] !== null || $techNew['chipset'] !== null || $techNew['refresh_min'] !== null
    || $techNew['storage_min'] !== null || $techNew['sim_dual'] || $techNew['screen_tech'] !== null
    || $techNew['screen_max'] !== null || $techNew['screen_min'] !== null || $techNew['want_5g']);

$state = chat_load_state($conversationId);
$f = $state['filters'];
$followup = chat_detect_followup($norm);

// Câu "tăng thêm 2 triệu"/"rẻ hơn" chứa số tiền là MỐC TĂNG/GIẢM,
// không phải ngân sách mới -> không cho nó ghi đè ngân sách đang nhớ
$skipBudgetMerge = ($followup !== null && in_array($followup['type'], ['increase', 'cheaper'], true));

// Giá trị mới trong câu ghi đè tiêu chí cũ; câu không nhắc thì giữ tiêu chí trước đó
if (!$skipBudgetMerge) {
    if ($budgetNew['min'] !== null) $f['budget_min'] = $budgetNew['min'];
    if ($budgetNew['max'] !== null) $f['budget_max'] = $budgetNew['max'];
}
if ($ramNew !== null) $f['ram'] = $ramNew;
// Nhắc "màn hình" kèm ràng buộc kích thước (màn to/nhỏ) chỉ là thêm điều kiện,
// không đổi nhu cầu đã nhớ (VD: vẫn đang tư vấn máy chụp ảnh)
if ($needNew !== '' && !($needNew === 'man-hinh' && $techNew['screen_max'] !== null)) $f['need'] = $needNew;
if ($brandNew !== null) {
    $f['brand_id'] = (int) $brandNew['id'];
    $f['brand_name'] = $brandNew['name'];
}
if ($techNew['os'] !== null) $f['os'] = $techNew['os'];
if ($techNew['chipset'] !== null) $f['chipset'] = $techNew['chipset'];
if ($techNew['refresh_min'] !== null) $f['refresh_min'] = $techNew['refresh_min'];
if ($techNew['storage_min'] !== null) $f['storage_min'] = $techNew['storage_min'];
if ($techNew['sim_dual']) $f['sim_dual'] = true;
if ($techNew['screen_tech'] !== null) $f['screen_tech'] = $techNew['screen_tech'];
if ($techNew['screen_max'] !== null) $f['screen_max'] = $techNew['screen_max'];
if ($techNew['screen_min'] !== null) $f['screen_min'] = $techNew['screen_min'];
if ($techNew['want_5g']) $f['want_5g'] = true;

$cards = [];
$reply = '';
$keyword = '';
$suggestionContext = 'general';
$note = '';
$intent = 'general';

// ===== Xử lý câu hỏi nối tiếp dựa trên trạng thái phiên =====
$followupActive = false;
$referProduct = null;
$mentioned = chat_find_products_in_text($conn, $norm, 2);

if ($followup !== null && (!empty($state['filters']) || !empty($state['last_products']))) {
    if ($followup['type'] === 'cheaper' && !empty($state['last_products'])) {
        $prices = array_filter(array_column($state['last_products'], 'min_price'));
        if ($prices) {
            $f['budget_max'] = max(0, min($prices) - 100000);
            $note = 'Ok, mình hạ khoảng giá xuống dưới ' . format_price($f['budget_max']) . " nhé.\n";
            $intent = 'recommend';
            $followupActive = true;
        }
    } elseif ($followup['type'] === 'increase') {
        $prices = array_filter(array_column($state['last_products'], 'min_price'));
        $refPrice = $prices ? min($prices) : 0;
        if ($followup['mode'] === 'set') {
            $f['budget_max'] = $followup['amount'];
            $note = 'Ok, mình đặt ngân sách ' . format_price($f['budget_max']) . " nhé.\n";
        } else {
            if (empty($f['budget_max'])) {
                $f['budget_max'] = ($refPrice > 0 ? $refPrice : 0) + $followup['amount'];
            } else {
                $f['budget_max'] += $followup['amount'];
            }
            $note = 'Ok, mình thêm ' . format_price($followup['amount']) . ', ngân sách lên ' . format_price($f['budget_max']) . " nhé.\n";
        }
        $intent = 'recommend';
        $followupActive = true;
    } elseif ($followup['type'] === 'exclude') {
        $excludeBrands = chat_detect_all_brands($conn, $norm);
        if ($excludeBrands || $techNew['os'] !== null) {
            $excludedNames = [];
            foreach ($excludeBrands as $excludeBrand) {
                $f['excluded_brand_ids'] = array_values(array_unique(array_merge($f['excluded_brand_ids'] ?? [], [(int) $excludeBrand['id']])));
                if (($f['brand_id'] ?? 0) === (int) $excludeBrand['id']) {
                    unset($f['brand_id'], $f['brand_name']);
                }
                $excludedNames[] = $excludeBrand['name'];
            }
            $f['excluded_brand_names'] = array_values(array_unique(array_merge($f['excluded_brand_names'] ?? [], $excludedNames)));
            $note = 'Ok, mình đã bỏ qua ' . implode(', ', $excludedNames);
            if ($techNew['os'] !== null) {
                $f['os'] = $techNew['os'];
                $note .= ' và chỉ tư vấn máy ' . ($techNew['os'] === 'ios' ? 'iOS' : 'Android');
            }
            $note .= " nhé.\n";
            $intent = 'recommend';
            $followupActive = true;
        }
    } elseif ($followup['type'] === 'switch_brand' && ($brandNew !== null || $techNew['os'] !== null)) {
        if ($brandNew !== null) {
            $note = 'Ok, mình xem qua các máy ' . $brandNew['name'] . " nhé.\n";
        } else {
            $f['os'] = $techNew['os'];
            $note = 'Ok, mình chỉ xem máy ' . ($techNew['os'] === 'ios' ? 'iOS' : 'Android') . " nhé.\n";
        }
        $intent = 'recommend';
        $followupActive = true;
    } elseif ($followup['type'] === 'refer' && !empty($state['last_products'])) {
        $referProduct = $state['last_products'][$followup['index']] ?? ($state['last_products'][0] ?? null);
        if ($referProduct !== null) {
            if (preg_match('/\bso sanh\b/', $norm)) {
                $intent = 'compare';
            } elseif (preg_match('/\b(con hang|con khong|het hang|ton kho|con ban)\b/', $norm)) {
                $intent = 'stock';
            } elseif (preg_match('/\b(ram|pin|man hinh|camera|chip|bo nho|thong so|cau hinh|sac|he dieu hanh|tan so)\b/', $norm)) {
                $intent = 'detail';
            } elseif (preg_match('/\b(gia|bao nhieu)\b/', $norm)) {
                $intent = 'price';
            } else {
                $intent = 'detail';
            }
            $note = 'Về ' . $referProduct['name'] . " nhé:\n";
            $followupActive = true;
        }
    } elseif ($followup['type'] === 'compare_refer' && !empty($state['last_products'])) {
        $referProduct = $state['last_products'][0] ?? null;
        if ($referProduct !== null) {
            $intent = 'compare';
            $followupActive = true;
        }
    }
}

// Gộp máy được nhắc qua "nó/máy này" với máy nhắc tên trong câu
if ($referProduct !== null) {
    $merged = [[
        'id' => (int) $referProduct['id'],
        'name' => $referProduct['name'],
        'brand_name' => (string) ($referProduct['brand_name'] ?? ''),
    ]];
    foreach ($mentioned as $mentionProduct) {
        if ((int) $mentionProduct['id'] !== (int) $referProduct['id']) {
            $merged[] = $mentionProduct;
            break;
        }
    }
    $mentioned = $merged;
}

if (!$followupActive) {
    $intent = chat_detect_intent($norm, $hasCriteria);
    // Nhắc tên thương hiệu mà không có ý định rõ ràng -> coi như tìm theo hãng
    if ($intent === 'general' && $brandNew !== null) {
        $intent = 'search';
    }
    // price/stock/detail: cần xác định được sản phẩm; nếu câu hiện tại có tiêu chí
    // (ngân sách/nhu cầu/hãng) thì chuyển sang tư vấn, nếu chỉ nhắc thương hiệu thì tìm theo hãng.
    // Tiêu chí còn nhớ từ các lượt trước KHÔNG dùng để chuyển hướng ở đây, vì người dùng
    // đang hỏi về một máy cụ thể (VD: máy không có trong cửa hàng).
    if (in_array($intent, ['price', 'stock', 'detail'], true) && !$mentioned) {
        if ($hasCriteria) {
            $intent = 'recommend';
        } elseif ($brandNew !== null) {
            $intent = 'search';
        }
    } elseif ($intent === 'search') {
        $searchTokens = ['tim', 'kiem', 'dien', 'thoai', 'co', 'nhung', 'danh', 'sach', 'liet', 'ke', 'may', 'nao'];
        $keywordTokens = array_values(array_filter(explode(' ', $norm), function ($token) use ($searchTokens) {
            return $token !== '' && !in_array($token, $searchTokens, true);
        }));
        $keyword = implode(' ', $keywordTokens);
        if ($brandNew !== null) {
            $keyword = ''; // khớp theo thương hiệu để lấy cả máy không có tên hãng trong tên (VD: Redmi)
        }
        if ($keyword === '' && $brandNew === null) {
            $intent = 'recommend';
        }
    }
}

if ($intent === 'compare') {
    if (count($mentioned) < 2) {
        $suggestionContext = 'compare';
        $reply = count($mentioned) === 1
            ? 'Mình thấy bạn nhắc đến ' . $mentioned[0]['name'] . '. Bạn muốn so sánh với máy nào nữa? VD: So sánh ' . $mentioned[0]['name'] . ' và Samsung Galaxy S24 Ultra.'
            : 'Bạn muốn so sánh hai điện thoại nào? Hãy nêu tên cả hai máy nhé. VD: So sánh iPhone 15 và Samsung Galaxy S24 Ultra.';
    } else {
        $rows = [];
        foreach ($mentioned as $product) {
            $row = get_product_row($conn, (int) $product['id']);
            if ($row) {
                $rows[] = $row;
            }
        }
        if (count($rows) === 2) {
            [$a, $b] = $rows;
            $screenLabel = function (array $row): string {
                $label = trim(($row['screen_size'] ?? '') . (!empty($row['screen_tech']) ? ' ' . $row['screen_tech'] : ''));
                if (!empty($row['refresh_rate'])) {
                    $label .= ' ' . $row['refresh_rate'] . 'Hz';
                }
                return $label !== '' ? $label : '-';
            };
            $batteryLabel = function (array $row): string {
                if (empty($row['battery_capacity'])) {
                    return '-';
                }
                return $row['battery_capacity'] . 'mAh' . (!empty($row['charging_speed']) ? ' (' . $row['charging_speed'] . 'W)' : '');
            };
            $reply = 'So sánh nhanh ' . $a['name'] . ' (' . format_price((float) $a['min_price']) . ') và ' . $b['name'] . ' (' . format_price((float) $b['min_price']) . "):\n"
                . '• Màn hình: ' . $screenLabel($a) . ' — ' . $screenLabel($b) . "\n"
                . '• Chip: ' . ($a['chipset'] ?: '-') . ' — ' . ($b['chipset'] ?: '-') . "\n"
                . '• RAM: ' . (!empty($a['ram']) ? $a['ram'] . 'GB' : '-') . ' — ' . (!empty($b['ram']) ? $b['ram'] . 'GB' : '-') . "\n"
                . '• Camera sau: ' . ($a['camera_rear'] ?: '-') . ' — ' . ($b['camera_rear'] ?: '-') . "\n"
                . '• Pin: ' . $batteryLabel($a) . ' — ' . $batteryLabel($b) . "\n"
                . '• Giá từ: ' . format_price((float) $a['min_price']) . ' — ' . format_price((float) $b['min_price']) . "\n"
                . '• Tồn kho: ' . (int) $a['total_stock'] . ' — ' . (int) $b['total_stock'];
            $cards = array_map(function ($row) use ($conn) {
                return chat_build_card($conn, $row);
            }, $rows);
        } else {
            $reply = 'Xin lỗi, mình không tải đủ thông tin hai máy này. Bạn thử lại với tên máy khác nhé.';
        }
    }
} else {
    if ($intent === 'recommend') {
        $filters = [
            'need' => $f['need'] ?? '',
            'ram' => $f['ram'] ?? null,
            'budget_min' => $f['budget_min'] ?? null,
            'budget_max' => $f['budget_max'] ?? null,
            'in_stock_only' => true,
        ];
        if (!empty($f['brand_id'])) {
            $filters['brand_id'] = (int) $f['brand_id'];
            $filters['brand_name'] = (string) $f['brand_name'];
        }
        if (!empty($f['excluded_brand_ids'])) {
            $filters['exclude_brand_ids'] = $f['excluded_brand_ids'];
        }
        if (!empty($f['os'])) {
            $filters['os'] = $f['os'];
        }
        if (!empty($f['chipset'])) {
            $filters['chipset'] = $f['chipset'];
        }
        if (!empty($f['screen_tech'])) {
            $filters['screen_tech'] = $f['screen_tech'];
        }
        if (!empty($f['refresh_min'])) {
            $filters['refresh_min'] = (int) $f['refresh_min'];
        }
        if (!empty($f['storage_min'])) {
            $filters['storage_min'] = (int) $f['storage_min'];
        }
        if (!empty($f['sim_dual'])) {
            $filters['sim_dual'] = true;
        }
        if (!empty($f['screen_max'])) {
            $filters['screen_max'] = (float) $f['screen_max'];
        }
        if (!empty($f['screen_min'])) {
            $filters['screen_min'] = (float) $f['screen_min'];
        }
        if (!empty($f['want_5g'])) {
            $filters['want_5g'] = true;
        }

        $rows = find_products($conn, $filters, 5);
        $prefix = '';
        if (!$rows && isset($filters['brand_id'])) {
            $triedBrand = (string) ($filters['brand_name'] ?? '');
            unset($filters['brand_id'], $filters['brand_name']);
            $rows = find_products($conn, $filters, 5);
            if ($rows) {
                $prefix = 'Không còn máy ' . $triedBrand . ' nào còn hàng đúng tiêu chí, mình mở rộng sang hãng khác nhé:' . "\n";
            }
        }
        if (!$rows && !empty($filters['budget_max'])) {
            $originalBudget = (float) $filters['budget_max'];
            $filters['budget_max'] = $originalBudget * 1.4;
            $rows = find_products($conn, $filters, 3);
            if ($rows) {
                $prefix = 'Hiện chưa có máy còn hàng đúng toàn bộ tiêu chí với ngân sách ' . format_price($originalBudget) . '. Gần nhất là các máy hơi vượt ngân sách:' . "\n";
            }
        }

        if ($rows) {
            $need = (string) ($filters['need'] ?? '');
            $cards = array_map(function ($row) use ($conn, $need) {
                return chat_build_card($conn, $row, chat_build_reason($row, $need));
            }, $rows);
            $criteriaText = chat_describe_filters($f);
            if ($prefix !== '') {
                $reply = $note . $prefix;
            } else {
                $reply = $note . ($criteriaText !== '' ? 'Với ' . $criteriaText . ', ' : 'Danh sách điện thoại đang có hàng, ')
                    . 'mình gợi ý ' . count($cards) . " máy (lý do và hạn chế trên từng thẻ):\n";
            }
            $conclusion = chat_build_conclusion($cards);
            if ($conclusion !== '') {
                $reply .= "\n" . $conclusion;
            }
        } else {
            $reply = $note . 'Hiện chưa có sản phẩm còn hàng khớp tiêu chí này. Bạn thử nới ngân sách hoặc nói thêm nhu cầu (chơi game, chụp ảnh, pin trâu...) để mình gợi ý lại nhé.';
        }
    } elseif ($intent === 'search') {
        $filters = [];
        if ($brandNew !== null) {
            $filters['brand_id'] = (int) $brandNew['id'];
        } elseif ($keyword !== '') {
            $filters['keyword'] = $keyword;
        }
        $rows = find_products($conn, $filters, 5);
        if ($rows) {
            $cards = array_map(function ($row) use ($conn) {
                return chat_build_card($conn, $row);
            }, $rows);
            $reply = $note . 'Mình tìm thấy ' . count($cards) . ' sản phẩm' . ($brandNew !== null ? ' của ' . $brandNew['name'] : '') . ' phù hợp "' . $message . '":';
        } else {
            $reply = $note . 'Không tìm thấy sản phẩm nào phù hợp "' . $message . '" trong cửa hàng. Bạn thử từ khóa khác hoặc chọn một gợi ý bên dưới nhé.';
        }
    } elseif ($intent === 'price') {
        foreach ($mentioned as $product) {
            $row = get_product_row($conn, (int) $product['id']);
            if (!$row) {
                continue;
            }
            $reply .= ($reply !== '' ? "\n\n" : '') . '• ' . $row['name'] . ' (' . $row['brand_name'] . '): giá từ ' . format_price((float) $row['min_price'])
                . ($row['max_price'] !== null && (float) $row['max_price'] > (float) $row['min_price'] ? ' đến ' . format_price((float) $row['max_price']) : '')
                . '. Các phiên bản: ' . chat_variant_lines(get_product_variants($conn, (int) $row['id']));
            $cards[] = chat_build_card($conn, $row);
        }
        if ($reply !== '') {
            $reply = $note . $reply . "\nGiá có thể thay đổi theo khuyến mãi, bạn bấm vào thẻ sản phẩm để xem chi tiết nhé.";
        } else {
            $suggestionContext = 'price';
            $reply = 'Bạn muốn hỏi giá máy nào vậy? Hãy nêu tên sản phẩm cụ thể nhé. VD: iPhone 15 giá bao nhiêu?';
        }
    } elseif ($intent === 'stock') {
        foreach ($mentioned as $product) {
            $row = get_product_row($conn, (int) $product['id']);
            if (!$row) {
                continue;
            }
            $total = (int) $row['total_stock'];
            $reply .= ($reply !== '' ? "\n\n" : '') . '• ' . $row['name'] . ': ' . ($total > 0 ? 'CÒN HÀNG (' . $total . ' máy). ' . chat_variant_lines(get_product_variants($conn, (int) $row['id'])) : 'Đã hết hàng.');
            $cards[] = chat_build_card($conn, $row);
        }
        if ($reply !== '') {
            $reply = $note . $reply;
            if (count($cards) === 1 && !$cards[0]['in_stock']) {
                $replacementFilters = ['in_stock_only' => true];
                if (!empty($f['brand_id'])) {
                    $replacementFilters['brand_id'] = (int) $f['brand_id'];
                }
                $alternatives = find_products($conn, $replacementFilters, 3);
                if (!$alternatives && !empty($replacementFilters['brand_id'])) {
                    unset($replacementFilters['brand_id']);
                    $alternatives = find_products($conn, $replacementFilters, 3);
                }
                if ($alternatives) {
                    $reply .= "\nMình gợi ý vài máy đang có hàng";
                    if (!empty($f['brand_name'])) {
                        $reply .= ' (ưu tiên cùng hãng ' . $f['brand_name'] . ')';
                    }
                    $reply .= ':';
                    foreach ($alternatives as $row) {
                        $cards[] = chat_build_card($conn, $row);
                    }
                }
            }
        } else {
            $suggestionContext = 'stock';
            $reply = 'Bạn muốn kiểm tra hàng của máy nào vậy? Hãy nêu tên sản phẩm nhé. VD: iPhone 15 còn hàng không?';
        }
    } elseif ($intent === 'detail') {
        foreach ($mentioned as $product) {
            $row = get_product_row($conn, (int) $product['id']);
            if (!$row) {
                continue;
            }
            $lines = [];
            if (!empty($row['screen_size'])) {
                $lines[] = 'Màn hình: ' . $row['screen_size'] . ' ' . $row['screen_tech'] . (!empty($row['refresh_rate']) ? ' ' . $row['refresh_rate'] . 'Hz' : '');
            }
            if (!empty($row['chipset'])) {
                $lines[] = 'Chipset: ' . $row['chipset'];
            }
            if (!empty($row['ram'])) {
                $lines[] = 'RAM: ' . $row['ram'] . 'GB';
            }
            if (!empty($row['camera_rear'])) {
                $lines[] = 'Camera sau: ' . $row['camera_rear'] . ' / trước: ' . $row['camera_front'];
            }
            if (!empty($row['battery_capacity'])) {
                $lines[] = 'Pin: ' . $row['battery_capacity'] . 'mAh, sạc ' . $row['charging_speed'] . 'W';
            }
            if (!empty($row['os'])) {
                $lines[] = 'Hệ điều hành: ' . $row['os'];
            }
            $reply .= ($reply !== '' ? "\n\n" : '') . '• ' . $row['name'] . ' — ' . format_price((float) $row['min_price']) . ":\n" . implode("\n", $lines);
            $cards[] = chat_build_card($conn, $row);
        }
        if ($reply !== '') {
            $reply = $note . $reply;
        } else {
            $suggestionContext = 'detail';
            $reply = 'Bạn muốn xem thông số của máy nào? Hãy nêu tên sản phẩm nhé. VD: Thông số iPhone 15.';
        }
    }
}

if ($reply === '') {
    $intent = 'general';
    $reply = "Xin chào! Mình là trợ lý AI của MCHAphone Store. Mình có thể giúp bạn:\n"
        . "• Tư vấn điện thoại theo nhu cầu, ngân sách\n"
        . "• Hỏi giá, thông số, tồn kho của máy\n"
        . "• So sánh hai điện thoại\n"
        . 'Bạn muốn tìm hiểu gì hôm nay?';
}

// ==================== Gọi AI provider (nếu cấu hình) ====================

// Chỉ gọi AI khi đã có dữ liệu thật từ database (hoặc hội thoại chung), để mô hình không bịa sản phẩm.
if (count($cards) > 0 || $intent === 'general') {
    $contextRows = [];
    foreach ($cards as $card) {
        $row = get_product_row($conn, (int) $card['id']);
        if ($row) {
            $contextRows[] = $row;
        }
    }
    $withVariants = in_array($intent, ['price', 'stock', 'detail', 'compare'], true);
    $systemPrompt = "Bạn là trợ lý tư vấn điện thoại của MCHAphone Store (hotline 1900 0000).\n"
        . "QUY TẮC BẮT BUỘC:\n"
        . "1. CHỈ dùng thông tin trong phần DỮ LIỆU SẢN PHẨM dưới đây (lấy trực tiếp từ database cửa hàng). Tuyệt đối không bịa tên sản phẩm, giá, thông số, tồn kho hay khuyến mãi không có trong dữ liệu. Thiếu dữ liệu thì nói rõ và hướng dẫn gọi hotline.\n"
        . "2. Trả lời tiếng Việt thuần (KHÔNG dùng markdown **, *, #, `), gạch đầu dòng bằng • hoặc -.\n"
        . "3. Cấu trúc lượt tư vấn: nhận định ngắn nhu cầu -> tối đa 5 sản phẩm (mỗi máy gồm: tên + giá, vì sao phù hợp, một điểm hạn chế) -> kết luận rõ nên chọn máy nào theo nhu cầu -> một câu hỏi dẫn hành động (so sánh, xem chi tiết hoặc thêm giỏ).\n"
        . "4. Không hỏi quá 2 câu làm rõ, không bắt người dùng lặp lại: ngân sách, nhu cầu, hãng đã chọn/loại trừ nằm trong lịch sử hội thoại và phần tiêu chí bên dưới.\n"
        . "5. Hiểu tiếng Việt tự nhiên: 10 củ/10tr = 10 triệu, pin trâu, máy chiến game, chụp đêm đẹp.\n"
        . "6. Không nhận đặt hàng hay thanh toán; hướng dẫn người dùng bấm nút trên thẻ sản phẩm.\n"
        . "7. Không khẳng định máy nào tốt nhất cho mọi người; khi người dùng hỏi nối tiếp (rẻ hơn, đổi hãng, máy đầu tiên...), dựa vào các máy đã tư vấn trước đó trong lịch sử.\n";
    $criteriaSummary = chat_describe_filters($f);
    if ($criteriaSummary !== '') {
        $systemPrompt .= "\nTIÊU CHÍ HIỆN TẠI CỦA NGƯỜI DÙNG (đã lọc trong dữ liệu): " . $criteriaSummary . "\n";
    }
    if ($note !== '') {
        $systemPrompt .= "NGƯỜI DÙNG VỪA THAY ĐỔI TIÊU CHÍ (đã áp vào dữ liệu): " . trim(str_replace("\n", ' ', $note)) . "\n";
    }
    $systemPrompt .= ($contextRows ? "\nDỮ LIỆU SẢN PHẨM TỪ DATABASE (JSON):\n" . json_encode(chat_build_context($conn, $contextRows, $withVariants), JSON_UNESCAPED_UNICODE) : '');
    $aiMessages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($cleanHistory as $entry) {
        $aiMessages[] = $entry;
    }
    $aiMessages[] = ['role' => 'user', 'content' => $message];

    $aiReply = ai_chat_completion(ai_provider_config(), $aiMessages);
    if (is_string($aiReply) && $aiReply !== '') {
        // AI đôi khi vẫn trả markdown - làm sạch để hiển thị văn bản thuần
        $aiReply = str_replace(['**', '__', '`'], '', $aiReply);
        $aiReply = (string) preg_replace('/^\s*#{1,6}\s*/m', '', $aiReply);
        $reply = trim($aiReply);
    }
}

// Lưu trạng thái phiên: tiêu chí lọc + các máy vừa tư vấn để xử lý follow-up
if ($intent !== 'general') {
    if (!empty($cards)) {
        $state['last_products'] = array_map(function ($card) {
            return [
                'id' => (int) $card['id'],
                'name' => (string) $card['name'],
                'brand_name' => (string) $card['brand_name'],
                'min_price' => (float) $card['min_price'],
            ];
        }, $cards);
    }
    if (in_array($intent, ['recommend', 'search'], true)) {
        $state['filters'] = $f;
    }
    $state['last_intent'] = $intent;
    chat_save_state($conversationId, $state);
}

// Gợi ý: khi có thẻ sản phẩm thì mời follow-up "rẻ hơn"; khi cần hỏi lại thì gợi ý theo ngữ cảnh
$actions = [];
if (count($cards) > 0) {
    if (in_array($intent, ['recommend', 'search'], true)) {
        $actions = [['type' => 'suggestion', 'label' => 'Có máy nào rẻ hơn không?']];
    }
} else {
    $actions = chat_suggestions($suggestionContext);
}

json_response(true, 'Chatbot phản hồi thành công.', [
    'message' => $reply,
    'intent' => $intent,
    'products' => $cards,
    'actions' => $actions,
]);
