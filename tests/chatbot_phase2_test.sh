#!/bin/bash
# Test Giai đoạn 2: hội thoại nối tiếp, state theo conversation_id, tiêu chí kỹ thuật, slang
API="http://127.0.0.1:8099/websitephoneai/api/ai/chat.php"
PHP=/c/xampp/php/php.exe
COOKIES=/tmp/chatbot_p2_cookies.txt
BODY=/tmp/chatbot_p2_body.json
rm -f "$COOKIES"

send() { # $1 = conversation_id, $2 = message
  "$PHP" -r '$b=["message"=>$argv[2],"history"=>[],"conversation_id"=>$argv[1]];file_put_contents($argv[3],json_encode($b,JSON_UNESCAPED_UNICODE));' "$1" "$2" "$BODY"
  curl -s -c "$COOKIES" -b "$COOKIES" -X POST "$API" -H "Content-Type: application/json; charset=utf-8" --data-binary @"$BODY" \
  | "$PHP" -r '
    $r = json_decode(stream_get_contents(STDIN), true);
    if (!is_array($r)) { echo "  INVALID JSON RESPONSE\n"; return; }
    echo "  success=" . var_export($r["success"], true) . " | intent=" . ($r["data"]["intent"] ?? "-") . "\n";
    echo "  msg: " . preg_replace("/\s+/", " ", $r["data"]["message"] ?? "-") . "\n";
    foreach (($r["data"]["products"] ?? []) as $p) {
        echo "  card: " . $p["name"] . " | " . $p["price_display"] . " | stock=" . $p["total_stock"]
           . " | reason=" . ($p["reason"] ?: "-") . "\n";
    }
    echo "  actions=" . count($r["data"]["actions"] ?? []) . "\n";
  '
}

echo "### CHUỖI A: state + đổi hãng + rẻ hơn"
echo "A1. Tôi cần điện thoại chơi game dưới 10 triệu"
send conv-a "Tôi cần điện thoại chơi game dưới 10 triệu"
echo "A2. Còn Samsung thì sao?"
send conv-a "Còn Samsung thì sao?"
echo "A3. Có máy nào rẻ hơn không?"
send conv-a "Có máy nào rẻ hơn không?"

echo; echo "### CHUỖI B: so sánh + hỏi tiếp máy đầu tiên/máy này"
echo "B1. So sánh iPhone 15 và Samsung Galaxy S24 Ultra"
send conv-b "So sánh iPhone 15 và Samsung Galaxy S24 Ultra"
echo "B2. Máy đầu tiên có bao nhiêu RAM?"
send conv-b "Máy đầu tiên có bao nhiêu RAM?"
echo "B3. Máy này còn hàng không?"
send conv-b "Máy này còn hàng không?"
echo "B4. So sánh nó với Xiaomi 14"
send conv-b "So sánh nó với Xiaomi 14"

echo; echo "### CHUỖI C: loại trừ hãng + hệ điều hành + cap 5 máy"
echo "C1. iPhone 15 giá bao nhiêu?"
send conv-c "iPhone 15 giá bao nhiêu?"
echo "C2. Tôi muốn Android, không chọn iPhone."
send conv-c "Tôi muốn Android, không chọn iPhone."

echo; echo "### CHUỖI D: slang + tiêu chí kỹ thuật"
echo "D1. máy 10 củ pin trâu"
send conv-d "máy 10 củ pin trâu"
echo "D2. cần máy 2 sim dưới 15 triệu"
send conv-d "cần máy 2 sim dưới 15 triệu"
echo "D3. máy snapdragon 8 gen 3 ram 12gb"
send conv-d "máy snapdragon 8 gen 3 ram 12gb"

echo; echo "### CHUỖI E: không có máy khớp -> đề xuất gần nhất"
echo "E1. máy dưới 8 triệu camera đẹp"
send conv-e "máy dưới 8 triệu camera đẹp"

echo; echo "### CHUỖI F: ràng buộc màn hình giữa chừng"
echo "F1. tư vấn máy chụp ảnh"
send conv-f "tư vấn máy chụp ảnh"
echo "F2. tôi không thích máy màn hình quá to"
send conv-f "tôi không thích máy màn hình quá to"

echo; echo "### Đơn lẻ: tăng ngân sách"
echo "G1. tư vấn máy chơi game dưới 12 triệu"
send conv-g "tư vấn máy chơi game dưới 12 triệu"
echo "G2. nếu tăng thêm 2 triệu thì sao?"
send conv-g "nếu tăng thêm 2 triệu thì sao?"
