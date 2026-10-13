#!/bin/bash
# Test chatbot API với các câu hỏi mẫu (chạy bằng Git Bash)
API="http://127.0.0.1:8099/websitephoneai/api/ai/chat.php"
PHP=/c/xampp/php/php.exe
COOKIES=/tmp/chatbot_test_cookies.txt
BODY=/tmp/chatbot_body.json
rm -f "$COOKIES"

send() {
  "$PHP" -r '$b=["message"=>$argv[1],"history"=>[],"conversation_id"=>"test-suite"];file_put_contents($argv[2],json_encode($b,JSON_UNESCAPED_UNICODE));' "$1" "$BODY"
  curl -s -c "$COOKIES" -b "$COOKIES" -X POST "$API" -H "Content-Type: application/json; charset=utf-8" --data-binary @"$BODY"
}

show() {
  send "$1" | "$PHP" -r '
    $r = json_decode(stream_get_contents(STDIN), true);
    if (!is_array($r)) { echo "  INVALID JSON RESPONSE\n"; exit; }
    echo "  success=" . var_export($r["success"], true) . " | intent=" . ($r["data"]["intent"] ?? "-") . "\n";
    echo "  msg: " . preg_replace("/\s+/", " ", $r["data"]["message"] ?? "-") . "\n";
    foreach (($r["data"]["products"] ?? []) as $p) {
        echo "  card: " . $p["name"] . " | " . $p["price_display"] . " | stock=" . $p["total_stock"]
           . " | reason=" . ($p["reason"] ?: "-") . " | variant=" . ($p["variant_id"] ?? "-") . "\n";
    }
    echo "  actions=" . count($r["data"]["actions"] ?? []) . "\n";
  '
}

echo "=== 1. Ngan sach: Tìm điện thoại dưới 10 triệu ==="; show "Tìm điện thoại dưới 10 triệu"
echo "=== 2. Gaming: Điện thoại chơi game tốt ==="; show "Điện thoại chơi game tốt"
echo "=== 3. Pin: pin trâu ==="; show "pin trâu"
echo "=== 4. Camera: Điện thoại chụp ảnh đẹp ==="; show "Điện thoại chụp ảnh đẹp"
echo "=== 5. Gia: iPhone 15 giá bao nhiêu ==="; show "iPhone 15 giá bao nhiêu"
echo "=== 6. Stock: iPhone 15 còn hàng không ==="; show "iPhone 15 còn hàng không"
echo "=== 7. Compare: So sánh iPhone 15 và Samsung Galaxy S24 Ultra ==="; show "So sánh iPhone 15 và Samsung Galaxy S24 Ultra"
echo "=== 8. Detail: thông số Samsung Galaxy S24 Ultra ==="; show "thông số Samsung Galaxy S24 Ultra"
echo "=== 9. Search: tìm samsung ==="; show "tìm samsung"
echo "=== 10. Ram: điện thoại ram 12gb ==="; show "điện thoại ram 12gb"
echo "=== 11. Khoang gia: từ 10 đến 15 triệu ==="; show "điện thoại từ 10 đến 15 triệu"
echo "=== 12. General: chào shop ==="; show "chào shop"
echo "=== 13. Search không có kết quả ==="; show "tìm nokia"
echo "=== 14. Gia máy không tồn tại ==="; show "Nokia 3310 giá bao nhiêu"
