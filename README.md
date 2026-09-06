Hướng dẫn cho anh em cài dự án về máy:

**Bước 1: Clone kho lưu trữ về máy**
* Mở terminal trong thư mục chạy web của bạn (ví dụ với XAMPP: `C:/xampp/htdocs/`):
```bash
git clone [https://github.com/huyquang4925/websitephoneai.git](https://github.com/huyquang4925/websitephoneai.git)
cd websitephoneai 
```
**Bước 2: Cấu hình cơ sở dữ liệu**
Mở phpMyAdmin (thường tại http://localhost/phpmyadmin).
Tạo một cơ sở dữ liệu mới.
Import phone_store.sql của dự án.
Kiểm tra và điều chỉnh thông tin kết nối DB (host, user, password, db_name) trong file cấu hình tương ứng của dự án.

**Bước 3: Chạy thử trên trình duyệt**
Truy cập theo đường dẫn: http://localhost/websitephoneai/

**Lưu ý nếu có code mới nên dùng lệnh git pull để kéo code về không bị xung đột với code cần sửa nhá**
