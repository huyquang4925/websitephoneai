<?php
// Cấu hình AI provider (Deepseek) cho chatbot api/ai/chat.php.
// API key CHỈ được đọc phía server, không bao giờ xuất ra HTML/JavaScript/log.
// Thứ tự ưu tiên cung cấp key:
//   1. Biến môi trường DEEPSEEK_API_KEY (SetEnv trong Apache hoặc biến hệ thống).
//   2. File config/ai_secret.php định nghĩa DEEPSEEK_API_KEY (đã được .gitignore).

function ai_provider_config(): array
{
    $apiKey = (string) (getenv('DEEPSEEK_API_KEY') ?: '');
    if ($apiKey === '' && !defined('DEEPSEEK_API_KEY') && is_file(__DIR__ . '/ai_secret.php')) {
        require_once __DIR__ . '/ai_secret.php';
    }
    if ($apiKey === '' && defined('DEEPSEEK_API_KEY')) {
        $apiKey = (string) DEEPSEEK_API_KEY;
    }

    $model = (string) (getenv('DEEPSEEK_MODEL') ?: '');
    if ($model === '' && defined('DEEPSEEK_MODEL')) {
        $model = (string) DEEPSEEK_MODEL;
    }

    return [
        'api_key' => trim($apiKey),
        'api_url' => rtrim((string) (getenv('DEEPSEEK_API_URL') ?: 'https://api.deepseek.com/chat/completions'), '/'),
        'model' => $model !== '' ? $model : 'deepseek-v4-flash',
        'timeout' => 30,
        'max_tokens' => 1000,
    ];
}

// Gọi chat completion. Trả về nội dung trả lời, hoặc null khi lỗi/chưa cấu hình
// để caller chuyển sang fallback truy vấn SQL trực tiếp.
function ai_chat_completion(array $config, array $messages): ?string
{
    if ($config['api_key'] === '') {
        return null;
    }

    $payload = json_encode([
        'model' => $config['model'],
        'messages' => array_values($messages),
        'temperature' => 0.4,
        'max_tokens' => $config['max_tokens'],
        'stream' => false,
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return null;
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_key'],
    ];

    $body = false;
    if (function_exists('curl_init')) {
        $curl = curl_init($config['api_url']);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($body === false) {
            error_log('AI chat: khong goi duoc provider (cURL).');
            curl_close($curl);
            return null;
        }
        curl_close($curl);
        if ($status < 200 || $status >= 300) {
            error_log('AI chat: provider tra ve HTTP ' . $status . '.');
            return null;
        }
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => $config['timeout'],
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($config['api_url'], false, $context);
        if ($body === false) {
            error_log('AI chat: khong goi duoc provider (stream).');
            return null;
        }
    }

    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded)) {
        error_log('AI chat: phan hoi provider khong hop le.');
        return null;
    }
    $content = $decoded['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        error_log('AI chat: provider khong tra ve noi dung.');
        return null;
    }
    return trim($content);
}
