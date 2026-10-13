# Website Phone AI

## Cài đặt nhanh trên XAMPP

### 1. Clone dự án

Mở Git Bash hoặc Terminal tại `C:\xampp\htdocs`, sau đó chạy:

```bash
git clone https://github.com/huyquang4925/websitephoneai.git
cd websitephoneai
```

### 2. Tạo và nạp cơ sở dữ liệu

1. Mở XAMPP và bật **Apache** cùng **MySQL**.
2. Truy cập <http://localhost/phpmyadmin>.
3. Tạo database có tên `phone_store`.
4. Chọn database `phone_store`, bấm **Import**, rồi chọn file `database/phone_store.sql`.

Dự án dùng cấu hình XAMPP mặc định trong [config/database.php](config/database.php):

```text
Host: localhost
User: root
Password: (để trống)
Database: phone_store
```

Nếu máy bạn dùng thông tin khác, sửa file trên trước khi chạy.

### 3. Cấu hình chatbot AI (bắt buộc để dùng chatbot có Al)

Website vẫn chạy chatbot cơ bản nếu không có DeepSeek API key. Muốn dùng câu trả lời AI đầy đủ:

1. Copy file `config/ai_secret.example.php` thành `config/ai_secret.php`.
2. Mở `config/ai_secret.php`.
3. Thay `your_deepseek_api_key_here` bằng API key DeepSeek của tôi gửi.

Ví dụ trên Windows PowerShell:

```powershell
Copy-Item config\ai_secret.example.php config\ai_secret.php
```

**Không commit hoặc gửi file `config/ai_secret.php` lên GitHub.** File này đã được thêm vào `.gitignore`. Mỗi người nên dùng API key riêng.

### 4. Mở website

Truy cập:

<http://localhost/websitephoneai/>

## Chạy test chatbot (không bắt buộc)

Thư mục `tests/` chỉ chứa các file kiểm tra chatbot, không cần chạy để sử dụng website. Các file `.sh` cần chạy bằng Git Bash và yêu cầu Apache, MySQL, PHP đã được cấu hình đúng.

## Cập nhật code mới

Nếu dự án đã được clone trước đó, chạy:

```bash
git pull origin main
```