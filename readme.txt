=== Theme AMP v1.0.0 ===
Tác giả: Tuancele
Website: https://vpnmisa.com/

Cảm ơn bạn đã sử dụng theme này. Để theme hoạt động chính xác ngay sau khi kích hoạt, vui lòng thực hiện các bước cấu hình bắt buộc dưới đây.


amp/
├── css/                      # Thư mục chứa các tệp định kiểu (CSS)
│   ├── amp-custom.css        # Tệp CSS tùy chỉnh cho giao diện AMP (bản đầy đủ)
│   └── amp-custom.min.css    # Tệp CSS tùy chỉnh đã được nén (minified)
│
├── inc/                      # Thư mục "Includes" (chứa các tệp chức năng cốt lõi)
│   ├── admin-settings.php    # Các hàm và logic cho trang cài đặt quản trị viên
│   ├── comments-handler.php  # Xử lý các thao tác liên quan đến bình luận
│   ├── core-setup.php        # Các thiết lập và khởi tạo cốt lõi của theme/plugin
│   ├── image-map-data.php    # (Chưa rõ mục đích, có thể liên quan đến dữ liệu bản đồ ảnh)
│   ├── integrations.php      # Logic tích hợp với các hệ thống hoặc plugin khác
│   ├── meta-boxes.php        # (Chưa rõ mục đích, có thể định nghĩa các meta box tùy chỉnh)
│   ├── seo-helpers.php       # Các hàm hỗ trợ tối ưu hóa công cụ tìm kiếm (SEO)
│   ├── shortcodes.php        # Định nghĩa các shortcode tùy chỉnh
│   └── template-helpers.php  # Các hàm hỗ trợ việc hiển thị và xử lý template
│
├── assets/
│   ├── icons/
│   └── js/
│
├── template-parts/           # Thư mục chứa các phần tử giao diện có thể tái sử dụng
│   └── content-card.php      # Mẫu hiển thị nội dung dạng thẻ (card)
│
├── vendor/                   # Thư mục chứa các thư viện hoặc mã bên thứ ba
│
├── 404.php                   # Template hiển thị khi không tìm thấy trang (Lỗi 404)
├── archive.php               # Template hiển thị các trang lưu trữ (danh mục, tag, v.v.)
├── comments.php              # Template hiển thị khu vực bình luận
├── footer.php                # Template hiển thị phần chân trang (footer)
├── functions.php             # Tệp chức năng cốt lõi của Theme
├── header.php                # Template hiển thị phần đầu trang (header)
├── index.php                 # Template trang chủ mặc định
├── ip.php                    # (Chưa rõ mục đích, có thể liên quan đến địa chỉ IP)
├── my-ip.php                 # (Chưa rõ mục đích, có thể hiển thị IP người dùng)
├── page-camon.php            # Template tùy chỉnh cho trang "Cảm ơn"
├── page-live-status.php      # Template tùy chỉnh cho trang "Trạng thái trực tiếp"
├── page.php                  # Template mặc định cho các trang tĩnh
├── search.php                # Template hiển thị kết quả tìm kiếm
├── single.php                # Template hiển thị một bài viết đơn lẻ
├── style.css                 # Tệp định kiểu CSS chính (và thông tin tiêu đề Theme)
├── support.php               # Template hoặc file chức năng liên quan đến hỗ trợ
├── template-homepage-bds.php # Template tùy chỉnh cho trang chủ bất động sản
└── lay-toa-do.html           # (File HTML tĩnh, có thể là công cụ nội bộ)
--- CẤU HÌNH BẮT BUỘC SAU KHI KÍCH HOẠT ---

1. CÀI ĐẶT MENU:
   - Vào "Giao diện" -> "Menu".
   - Tạo 2 menu mới:
     - Một menu tên "Main Menu" và gán vào vị trí "Primary Menu".
     - Một menu tên "Footer Links" và gán vào vị trí "Footer Links 1".

2. TẠO TRANG "CẢM ƠN":
   - Vào "Trang" -> "Thêm trang mới".
   - Tạo một trang có tiêu đề là "Cảm Ơn".
   - Quan trọng: Đảm bảo đường dẫn tĩnh (slug) của trang này là "cam-on".
   - Trang này dùng để chuyển hướng người dùng sau khi họ điền form.

3. CẤU HÌNH GỬI MAIL (SMTP):
   - Mở file "wp-config.php" ở thư mục gốc của WordPress.
   - Thêm 2 dòng sau vào cuối file, ngay trước dòng /* That's all, stop editing! */:

     define( 'SMTP_USER', 'your-email@gmail.com' );
     define( 'SMTP_PASS', 'your-google-app-password' );

   - Thay thế bằng thông tin Gmail và Mật khẩu ứng dụng của bạn.

--- HƯỚNG DẪN SỬ DỤNG SHORTCODE ---

- Form đăng ký: [form_dang_ky tieu_de="Tiêu đề tùy chỉnh" nut_gui="Tên nút tùy chỉnh"]
- FAQ Schema: [schema_faq][q]Câu hỏi[/q][a]Trả lời[/a][/schema_faq]
- How-to Schema: [schema_howto title="Tiêu đề"][step title="Bước 1"]Nội dung[/step][/schema_howto]

Cảm ơn và chúc bạn thành công!