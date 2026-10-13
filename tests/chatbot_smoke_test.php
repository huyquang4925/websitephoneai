<?php
// Smoke test cuối: gửi kèm history, kiểm tra cấu trúc JSON response
$body = json_encode([
    'message' => 'so sánh iPhone 15 và Samsung Galaxy S24 Ultra',
    'history' => [
        ['role' => 'user', 'content' => 'chào'],
        ['role' => 'assistant', 'content' => 'Xin chào!'],
    ],
    'conversation_id' => 'final-check',
], JSON_UNESCAPED_UNICODE);
file_put_contents('/tmp/final.json', $body);

$ch = curl_init('http://127.0.0.1:8099/websitephoneai/api/ai/chat.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
    CURLOPT_RETURNTRANSFER => true,
]);
$raw = curl_exec($ch);
curl_close($ch);
$r = json_decode((string) $raw, true);
if (!is_array($r)) {
    echo "INVALID JSON\n";
    exit(1);
}
echo 'success=' . var_export($r['success'], true)
    . ' intent=' . $r['data']['intent']
    . ' cards=' . count($r['data']['products'])
    . ' data_keys=' . implode(',', array_keys($r['data'])) . "\n";
echo 'reply_head=' . mb_substr(preg_replace('/\s+/', ' ', $r['data']['message']), 0, 90) . "\n";
foreach ($r['data']['products'] as $p) {
    echo 'card: ' . $p['name'] . ' | ' . $p['price_display'] . ' | url=' . $p['detail_url'] . "\n";
}
