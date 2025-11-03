<?php
/**
 * inc/admin-settings.php
 * Chứa tất cả các hàm để tạo trang cài đặt trong khu vực Admin WP.
 * PHIÊN BẢN NÂNG CẤP: Bổ sung trường Schema và sửa lỗi dứt điểm công cụ R2 Migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Hooks chính
add_action('admin_menu', 'tuancele_amp_create_settings_pages');
add_action('admin_init', 'tuancele_amp_register_all_settings');
add_action('admin_enqueue_scripts', 'tuancele_amp_settings_admin_scripts');

/**
 * 1. TẠO CÁC MENU TRONG ADMIN
 */
function tuancele_amp_create_settings_pages() {
    add_menu_page('Cài đặt Theme AMP', 'Cài đặt AMP', 'manage_options', 'tuancele-amp-settings', 'tuancele_amp_shortcode_guide_page', 'dashicons-superhero-alt', 60);
    add_submenu_page('tuancele-amp-settings', 'Hướng dẫn sử dụng Shortcode', 'Hướng dẫn Shortcode', 'manage_options', 'tuancele-amp-settings', 'tuancele_amp_shortcode_guide_page');
    add_submenu_page('tuancele-amp-settings', 'Cài đặt Tích hợp', 'Tích hợp Dịch vụ', 'manage_options', 'tuancele-amp-integrations', 'tuancele_amp_integrations_settings_page');
    add_submenu_page('tuancele-amp-settings', 'Cấu hình Schema Doanh nghiệp', 'Cấu hình Schema', 'manage_options', 'tuancele-amp-schema', 'tuancele_amp_schema_settings_page');
    add_submenu_page('tuancele-amp-settings', 'Cài đặt gửi mail (SMTP)', 'Cài đặt SMTP', 'manage_options', 'tuancele-amp-smtp', 'tuancele_amp_smtp_settings_page');
    add_submenu_page('tuancele-amp-settings', 'Cài đặt Cloudflare R2', 'Cài đặt R2', 'manage_options', 'tuancele-amp-r2', 'tuancele_amp_r2_settings_page');
    add_submenu_page('tuancele-amp-settings', 'Cloudflare Turnstile (Captcha)', 'Cấu hình Captcha', 'manage_options', 'tuancele-amp-turnstile', 'tuancele_amp_turnstile_settings_page');
    add_submenu_page('tuancele-amp-settings', 'Cài đặt các Nút Nổi', 'Các Nút Nổi', 'manage_options', 'tuancele-amp-floating-buttons', 'tuancele_amp_floating_buttons_page');
}

/**
 * 2. CÁC HÀM RENDER GIAO DIỆN HTML CHO TỪNG TRANG
 */

function tuancele_amp_shortcode_guide_page() {
    ?>
    <div class="wrap">
        <h1>Hướng dẫn sử dụng Shortcode của Theme</h1>
        <p>Sao chép và dán các shortcode dưới đây vào trình soạn thảo bài viết/trang để sử dụng. Click vào tiêu đề để xem chi tiết.</p>
        <style>
            .shortcode-guide-box { background: #fff; border: 1px solid #ccd0d4; padding: 15px 20px; margin-bottom: 20px; border-left-width: 4px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .shortcode-guide-box h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 1.3em; cursor: pointer; }
            .shortcode-guide-box h2::after { content: ' ▼'; font-size: 0.8em; }
            .shortcode-guide-box .details { display: none; margin-top: 15px; }
            .shortcode-guide-box code { background: #f0f0f1; padding: 10px; border-radius: 4px; font-size: 14px; display: block; margin: 10px 0; white-space: pre-wrap; word-break: break-all; overflow-x: auto; border: 1px solid #ddd; }
            .shortcode-guide-box .description { margin-top: 10px; color: #555; font-style: italic; }
            .shortcode-guide-box .note { color: #d63638; font-weight: bold; margin-top: 10px; }
            .shortcode-guide-box ul { margin-top: 10px; list-style: disc; padding-left: 20px;}
            .shortcode-guide-box table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .shortcode-guide-box th, .shortcode-guide-box td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .shortcode-guide-box th { background-color: #f9f9f9; }
        </style>
        <script>
            jQuery(document).ready(function($){
                $('.shortcode-guide-box h2').on('click', function(){
                    $(this).next('.details').slideToggle();
                });
            });
        </script>

        <?php /* === MỤC FORM === */ ?>
        <div class="shortcode-guide-box" style="border-left-color: #fd7e14;">
            <h2>📝 Form Đăng Ký (Đầy đủ)</h2>
            <div class="details">
                <p class="description">Hiển thị một form đăng ký đầy đủ với các trường: Họ tên, Số điện thoại, Email. Dữ liệu sẽ được gửi đến Zoho và Email admin.</p>
                <code>[form_dang_ky tieu_de="Đăng Ký Tư Vấn Miễn Phí" nut_gui="Gửi Thông Tin Ngay"]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th><th>Ví dụ</th></tr>
                    <tr><td><code>tieu_de</code></td><td>Tiêu đề của form. Mặc định: "Đăng Ký Tư Vấn Miễn Phí".</td><td><code>tieu_de="Nhận báo giá"</code></td></tr>
                    <tr><td><code>nut_gui</code></td><td>Nội dung của nút gửi. Mặc định: "Gửi Thông Tin Ngay".</td><td><code>nut_gui="Đăng ký ngay!"</code></td></tr>
                </table>
            </div>
        </div>

        <div class="shortcode-guide-box" style="border-left-color: #fd7e14;">
            <h2>📞 Form Đăng Ký (Chỉ SĐT)</h2>
            <div class="details">
                <p class="description">Hiển thị một form đăng ký tinh gọn chỉ yêu cầu nhập số điện thoại.</p>
                <code>[dang_ky_sdt tieu_de="Nhận báo giá nhanh" nut_gui="Yêu Cầu Gọi Lại"]</code>
                 <table>
                    <tr><th>Tham số</th><th>Mô tả</th><th>Ví dụ</th></tr>
                    <tr><td><code>tieu_de</code></td><td>Tiêu đề của form. Mặc định: "Để lại số điện thoại, chúng tôi sẽ gọi lại ngay!".</td><td><code>tieu_de="Tư vấn qua SĐT"</code></td></tr>
                    <tr><td><code>nut_gui</code></td><td>Nội dung của nút gửi. Mặc định: "Yêu Cầu Gọi Lại".</td><td><code>nut_gui="Gọi cho tôi"</code></td></tr>
                </table>
            </div>
        </div>

        <?php /* === MỤC SCHEMA & SEO === */ ?>
        <div class="shortcode-guide-box" style="border-left-color: #28a745;">
            <h2>❓ FAQ (Hỏi & Đáp) kèm Schema</h2>
            <div class="details">
                <p class="description">Tạo danh sách câu hỏi - trả lời dạng accordion và tự động thêm Schema `FAQPage` để hiển thị trên kết quả tìm kiếm Google.</p>
                <code>[schema_faq]
    [q]Câu hỏi 1 của bạn là gì?[/q]
    [a]Đây là câu trả lời cho câu hỏi 1.[/a]
    [q]Câu hỏi thứ 2?[/q]
    [a]Và đây là câu trả lời cho câu hỏi 2.[/a]
[/schema_faq]</code>
                <p class="note">Lưu ý: Luôn đặt các cặp thẻ `[q]` và `[a]` bên trong thẻ `[schema_faq]`.</p>
            </div>
        </div>
        
        <div class="shortcode-guide-box" style="border-left-color: #6f42c1;">
            <h2>📋 Hướng dẫn (How-To) kèm Schema</h2>
            <div class="details">
                <p class="description">Tạo danh sách các bước hướng dẫn và tự động thêm Schema `HowTo`.</p>
                <code>[schema_howto title="Cách làm bánh mì" total_time="PT1H30M"]
    [step title="Bước 1: Chuẩn bị bột"]Nội dung chi tiết cho bước 1.[/step]
    [step title="Bước 2: Nhào bột"]Nội dung chi tiết cho bước 2.[/step]
[/schema_howto]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th><th>Ví dụ</th></tr>
                    <tr><td><code>title</code></td><td><strong>(Bắt buộc)</strong> Tiêu đề của bản hướng dẫn.</td><td><code>title="Cách cài đặt VPN"</code></td></tr>
                    <tr><td><code>total_time</code></td><td>Thời gian hoàn thành theo chuẩn ISO 8601. Ví dụ: 1 giờ 30 phút là <code>PT1H30M</code>. 15 phút là <code>PT15M</code>.</td><td><code>total_time="PT45M"</code></td></tr>
                </table>
            </div>
        </div>

        <div class="shortcode-guide-box" style="border-left-color: #007bff;">
            <h2>⭐ Sản phẩm (Product) kèm Schema</h2>
            <div class="details">
                <p class="description">Hiển thị một khối thông tin sản phẩm chuyên nghiệp và tự động thêm Schema `Product` đầy đủ.</p>
                <p class="note">Hầu hết các thông tin sẽ được tự động lấy từ bài viết (Tiêu đề, ảnh đại diện, mô tả ngắn...). Bạn chỉ cần điền các tham số nếu muốn ghi đè.</p>
                <code>[amp_product price="150000" currency="VND" brand="Tên thương hiệu" sku="MA-SP-01" rating_value="4.8" rating_count="25"]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                    <tr><td><code>price</code></td><td>Giá sản phẩm (chỉ điền số).</td></tr>
                    <tr><td><code>currency</code></td><td>Đơn vị tiền tệ. Mặc định: "VND".</td></tr>
                    <tr><td><code>brand</code></td><td>Tên thương hiệu. Mặc định là tên website.</td></tr>
                    <tr><td><code>sku</code></td><td>Mã sản phẩm. Mặc định là ID bài viết.</td></tr>
                    <tr><td><code>rating_value</code></td><td>Điểm đánh giá. Mặc định là 5.0.</td></tr>
                    <tr><td><code>rating_count</code></td><td>Số lượng đánh giá. Mặc định là số lượt xem bài viết.</td></tr>
                    <tr><td><code>image_id</code></td><td>ID của ảnh để ghi đè ảnh đại diện mặc định.</td></tr>
                </table>
            </div>
        </div>

        <?php /* === MỤC NỘI DUNG & HIỂN THỊ === */ ?>
        <div class="shortcode-guide-box" style="border-left-color: #17a2b8;">
            <h2>🖼️ Slider Ảnh (Carousel)</h2>
            <div class="details">
                <p class="description">Tạo một slider ảnh tự động chạy, có nút điều hướng từ danh sách ID ảnh trong Media Library.</p>
                <code>[amp_slider ids="12,34,56" width="1600" height="900"]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                    <tr><td><code>ids</code></td><td><strong>(Bắt buộc)</strong> Danh sách ID của các ảnh, cách nhau bởi dấu phẩy.</td></tr>
                    <tr><td><code>width</code> / <code>height</code></td><td>Tỷ lệ khung hình của slider. Mặc định: 1600 / 900 (tỷ lệ 16:9).</td></tr>
                </table>
            </div>
        </div>
        
        <div class="shortcode-guide-box" style="border-left-color: #ffc107;">
            <h2>📣 Quảng cáo Nội bộ</h2>
            <div class="details">
                <p class="description">Hiển thị một khối quảng cáo/đề xuất đến một hoặc nhiều bài viết khác trên trang, giúp tăng internal link.</p>
                <p><strong>Hiển thị một bài viết:</strong></p>
                <code>[quang_cao_noi_bo id="123"]</code>
                <p><strong>Hiển thị nhiều bài viết:</strong></p>
                <code>[quang_cao_noi_bo id="123,456,789"]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                    <tr><td><code>id</code></td><td><strong>(Bắt buộc)</strong> Một hoặc nhiều ID của các bài viết cần hiển thị, cách nhau bởi dấu phẩy.</td></tr>
                </table>
            </div>
        </div>

        <div class="shortcode-guide-box" style="border-left-color: #dc3545;">
            <h2>🗺️ Image Map (Bản đồ ảnh tương tác)</h2>
            <div class="details">
                <p class="description">Tạo một bản đồ ảnh với các điểm nóng (hotspot) có thể nhấp vào để mở link hoặc popup.</p>
                <p class="note">Bạn phải tạo một "Image Map" trong menu <strong>Cài đặt AMP &gt; Image Maps</strong> trước, sau đó lấy ID của nó để sử dụng shortcode này.</p>
                <code>[amp_imagemap id="123"]</code>
                 <table>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                    <tr><td><code>id</code></td><td><strong>(Bắt buộc)</strong> ID của Image Map đã được tạo trong admin.</td></tr>
                </table>
            </div>
        </div>

        <div class="shortcode-guide-box" style="border-left-color: #343a40;">
            <h2>🌍 Hiển thị theo Địa lý (Geo Targeting)</h2>
            <div class="details">
                <p class="description">Hiển thị các nội dung khác nhau cho người dùng dựa trên vị trí địa lý của họ (Việt Nam, Hà Nội, HCM...).</p>
                <code>Chào mừng [geo_display]
    [geo_option code="hanoi" text="người dân thủ đô"]
    [geo_option code="hcm" text="các bạn ở Sài Gòn"]
    [geo_option code="vn" text="các bạn ở Việt Nam"]
    [geo_option code="default" text="quý khách"]
[/geo_display]!</code>
                <table>
                    <tr><th>Tham số (cho <code>geo_option</code>)</th><th>Mô tả</th></tr>
                    <tr><td><code>code</code></td><td>Mã vị trí. Các mã có sẵn: <code>vn</code>, <code>hanoi</code>, <code>hcm</code>, <code>danang</code>, <code>haiphong</code>... và <strong>(bắt buộc)</strong> <code>default</code> cho các trường hợp còn lại.</td></tr>
                    <tr><td><code>text</code></td><td>Nội dung văn bản tương ứng với mã đó.</td></tr>
                </table>
            </div>
        </div>

         <div class="shortcode-guide-box" style="border-left-color: #e83e8c;">
            <h2>🗓️ Thanh Thông báo Sự kiện</h2>
            <div class="details">
                <p class="description">Tự động hiển thị một thanh thông báo dạng carousel ở đầu trang, lấy dữ liệu từ các "Sự kiện" bạn đã tạo trong menu <strong>Sự kiện</strong>. Shortcode này không có tham số.</p>
                <code>[amp_event_bar]</code>
                <p class="note">Chỉ cần đặt shortcode này vào vị trí bạn muốn (thường là trong file `header.php`). Nếu có sự kiện được publish, thanh thông báo sẽ tự động hiện ra.</p>
            </div>
        </div>

        <?php /* === MỤC BẤT ĐỘNG SẢN === */ ?>
        <div class="shortcode-guide-box" style="border-left-color: #20c997;">
            <h2>🏠 Chi tiết Bất động sản</h2>
            <div class="details">
                <p class="description">Hiển thị một bảng thông số chi tiết cho một bất động sản, tự động thêm Schema `RealEstateListing` nếu có giá.</p>
                <code>[chi_tiet_bds gia="12 Tỷ" dientich="80" phongngu="2" phongtam="2" huong="Đông Nam" phaply="Sổ hồng" price="12" price_unit="Tỷ" street_address="123 Nguyễn Lương Bằng" address_locality="Quận 7" address_region="TP. Hồ Chí Minh"]</code>
                <p class="note">Các tham số `price`, `price_unit`, `street_address`... dùng để tạo Schema và không hiển thị trực tiếp. Các tham số còn lại (`gia`, `dientich`...) sẽ hiển thị trên bảng.</p>
            </div>
        </div>
        
        <div class="shortcode-guide-box" style="border-left-color: #20c997;">
            <h2>📈 Công cụ Tính Lãi suất</h2>
            <div class="details">
                <p class="description">Hiển thị một công cụ tương tác cho phép người dùng ước tính khoản vay mua nhà. Shortcode này không có tham số.</p>
                <code>[tinh_lai_suat]</code>
            </div>
        </div>
        
        <div class="shortcode-guide-box" style="border-left-color: #20c997;">
            <h2>✨ Bất động sản Nổi bật</h2>
            <div class="details">
                <p class="description">Hiển thị một lưới các bài viết bất động sản dựa trên danh sách ID bạn cung cấp. Tương tự `[quang_cao_noi_bo]` nhưng dành cho layout BĐS.</p>
                <code>[bds_noibat ids="123,456" title="Các dự án đáng chú ý"]</code>
                <table>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                    <tr><td><code>ids</code></td><td><strong>(Bắt buộc)</strong> Danh sách ID của các bài viết, cách nhau bởi dấu phẩy.</td></tr>
                    <tr><td><code>title</code></td><td>Tiêu đề cho cả khối.</td></tr>
                </table>
            </div>
        </div>

        <div class="shortcode-guide-box" style="border-left-color: #20c997;">
            <h2>🌳 Tiện ích Xung quanh</h2>
            <div class="details">
                <p class="description">Tạo một danh sách các nhóm tiện ích (trường học, bệnh viện...) dưới dạng accordion.</p>
                <code>[tien_ich_xung_quanh]
    [tien_ich title="Giáo dục" icon="school"]
        - Trường Mầm non ABC (500m)
        - Trường Tiểu học XYZ (1km)
    [/tien_ich]
    [tien_ich title="Y tế" icon="hospital"]
        - Bệnh viện Quận 7 (2km)
    [/tien_ich]
[/tien_ich_xung_quanh]</code>
                <table>
                    <tr><th>Tham số (cho <code>tien_ich</code>)</th><th>Mô tả</th></tr>
                    <tr><td><code>title</code></td><td>Tiêu đề của nhóm tiện ích.</td></tr>
                    <tr><td><code>icon</code></td><td>Icon hiển thị. Các giá trị có sẵn: <code>school</code>, <code>hospital</code>, <code>market</code>, <code>park</code>, <code>default</code>.</td></tr>
                </table>
            </div>
        </div>

    </div>
    <?php
}

function tuancele_amp_schema_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cấu hình Schema Doanh nghiệp & Local SEO</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_schema_group'); do_settings_sections('tuancele-amp-schema'); submit_button(); ?>
        </form>
    </div>
    <?php
}

function tuancele_amp_smtp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cài đặt gửi mail (SMTP)</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_smtp_group'); do_settings_sections('tuancele-amp-smtp'); submit_button(); ?>
        </form>
    </div>
    <?php
}

function tuancele_amp_r2_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cài đặt lưu trữ Cloudflare R2</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_r2_group'); do_settings_sections('tuancele-amp-r2'); submit_button(); ?>
        </form>
    </div>
    <?php
}

function tuancele_amp_turnstile_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cấu hình Cloudflare Turnstile (Captcha)</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_turnstile_group'); do_settings_sections('tuancele-amp-turnstile'); submit_button(); ?>
        </form>
    </div>
    <?php
}

function tuancele_amp_floating_buttons_page() {
    ?>
    <div class="wrap">
        <h1>Cài đặt các Nút Nổi (Floating Buttons)</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_floating_buttons_group'); do_settings_sections('tuancele-amp-floating-buttons'); submit_button(); ?>
        </form>
    </div>
    <?php
}

function tuancele_amp_integrations_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cài đặt Tích hợp Dịch vụ Bên ngoài</h1>
        <form method="post" action="options.php">
            <?php settings_fields('tuancele_amp_integrations_group'); do_settings_sections('tuancele-amp-integrations'); submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * 3. HÀM TỔNG HỢP ĐỂ ĐĂNG KÝ TẤT CẢ CÁC TRƯỜNG CÀI ĐẶT
 */
function tuancele_amp_register_all_settings() {
    register_setting('tuancele_amp_integrations_group', 'tuancele_integrations_settings');
    add_settings_section('tuancele_integrations_zoho_section', 'Tích hợp Zoho CRM', null, 'tuancele-amp-integrations');
    add_settings_field('zoho_xnqsjsdp', 'Zoho Key (xnQsjsdp)', 'tuancele_integrations_field_callback', 'tuancele-amp-integrations', 'tuancele_integrations_zoho_section', ['id' => 'zoho_xnqsjsdp']);
    add_settings_field('zoho_xmiwtld', 'Zoho Key (xmIwtLD)', 'tuancele_integrations_field_callback', 'tuancele-amp-integrations', 'tuancele_integrations_zoho_section', ['id' => 'zoho_xmiwtld']);

    // [NÂNG CẤP SCHEMA]
    register_setting('tuancele_amp_schema_group', 'tuancele_amp_schema_options', 'tuancele_amp_sanitize_callback');
    add_settings_section('tuancele_schema_main_section', 'Thông tin chung', null, 'tuancele-amp-schema');
    
    $schema_fields_main = [
        'name'              => 'Tên Doanh nghiệp',
        'logo'              => 'URL Logo',
        'organization_type' => ['label' => 'Loại hình Doanh nghiệp', 'type' => 'select', 'options' => [
            'Corporation'     => 'Doanh nghiệp (Mặc định)',
            'RealEstateAgent' => 'Đại lý Bất động sản',
            'LocalBusiness'   => 'Doanh nghiệp Địa phương',
        ]],
        'price_range'       => ['label' => 'Khoảng giá (Price Range)', 'desc' => 'Ví dụ: $100000-$500000. Dùng cho Đại lý BĐS.'],
        'telephone'         => 'Số điện thoại',
        'hotline_number'    => 'Hotline Hỗ trợ',
        'email'             => 'Email liên hệ',
        'description'       => 'Mô tả ngắn'
    ];
    
    foreach ($schema_fields_main as $id => $field_data) {
        $args = is_array($field_data) ? array_merge($field_data, ['id' => $id]) : ['id' => $id];
        $label = is_array($field_data) ? $field_data['label'] : $field_data;
        add_settings_field($id, $label, 'tuancele_amp_schema_field_callback', 'tuancele-amp-schema', 'tuancele_schema_main_section', $args);
    }

    add_settings_section('tuancele_schema_local_seo_section', 'Địa chỉ & Local SEO', null, 'tuancele-amp-schema');
    $schema_fields_local = ['streetAddress' => 'Địa chỉ', 'addressLocality' => 'Quận / Huyện', 'addressRegion' => 'Tỉnh / Thành phố', 'postalCode' => 'Mã bưu chính', 'latitude' => 'Vĩ độ', 'longitude' => 'Kinh độ', 'openingHours' => 'Giờ mở cửa'];
    foreach ($schema_fields_local as $id => $title) add_settings_field($id, $title, 'tuancele_amp_schema_field_callback', 'tuancele-amp-schema', 'tuancele_schema_local_seo_section', ['id' => $id]);
    
    add_settings_section('tuancele_schema_social_section', 'Mạng xã hội', null, 'tuancele-amp-schema');
    add_settings_field('sameAs', 'Các trang MXH', 'tuancele_amp_schema_field_callback', 'tuancele-amp-schema', 'tuancele_schema_social_section', ['id' => 'sameAs']);

    register_setting('tuancele_amp_smtp_group', 'tuancele_smtp_settings');
    add_settings_section('tuancele_smtp_settings_section', 'Cấu hình gửi Mail (SMTP)', 'tuancele_smtp_section_callback', 'tuancele-amp-smtp');
    $smtp_fields = ['notification_email' => ['label' => 'Email nhận thông báo', 'type' => 'email'], 'enable_smtp' => ['label' => 'Kích hoạt SMTP', 'type' => 'checkbox'], 'smtp_user' => ['label' => 'Tài khoản SMTP'], 'smtp_pass' => ['label' => 'Mật khẩu SMTP', 'type' => 'password'], 'smtp_host' => ['label' => 'Máy chủ SMTP', 'default' => 'smtp.gmail.com'], 'smtp_port' => ['label' => 'Cổng SMTP', 'type' => 'number', 'default' => '587'], 'smtp_secure' => ['label' => 'Mã hóa', 'type' => 'select', 'options' => ['' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL']]];
    foreach ($smtp_fields as $id => $field) add_settings_field('tuancele_' . $id, $field['label'], 'tuancele_smtp_field_callback', 'tuancele-amp-smtp', 'tuancele_smtp_settings_section', array_merge($field, ['id' => $id]));

    register_setting('tuancele_amp_r2_group', 'tuancele_r2_settings');
    add_settings_section('tuancele_r2_settings_section', 'Thông tin kết nối Cloudflare R2', 'tuancele_r2_section_callback', 'tuancele-amp-r2');
    $r2_fields = ['enable_r2' => ['label' => 'Kích hoạt R2', 'type' => 'checkbox'], 'access_key_id' => ['label' => 'Access Key ID'], 'secret_access_key' => ['label' => 'Secret Access Key', 'type' => 'password'], 'bucket' => ['label' => 'Tên Bucket'], 'endpoint' => ['label' => 'Endpoint'], 'public_url' => ['label' => 'Public URL'], 'delete_local_file' => ['label' => 'Xóa file gốc', 'type' => 'checkbox'], 'enable_webp_conversion' => ['label' => 'Chuyển sang WebP', 'type' => 'checkbox']];
    foreach ($r2_fields as $id => $field) add_settings_field('tuancele_r2_' . $id, $field['label'], 'tuancele_r2_field_callback', 'tuancele-amp-r2', 'tuancele_r2_settings_section', array_merge($field, ['id' => $id]));
    
    add_settings_section('tuancele_r2_migration_section', 'Công cụ Di chuyển Dữ liệu cũ', 'tuancele_r2_migration_section_callback', 'tuancele-amp-r2');
    add_settings_field('tuancele_r2_migration_tool', 'Trạng thái & Hành động', 'tuancele_r2_migration_tool_callback', 'tuancele-amp-r2', 'tuancele_r2_migration_section');

    register_setting('tuancele_amp_turnstile_group', 'tuancele_turnstile_settings');
    add_settings_section('tuancele_turnstile_main_section', 'Khóa API Cloudflare', null, 'tuancele-amp-turnstile');
    $turnstile_fields = ['site_key' => ['label' => 'Site Key'], 'secret_key' => ['label' => 'Secret Key', 'type' => 'password']];
    foreach ($turnstile_fields as $id => $field) add_settings_field('tuancele_turnstile_' . $id, $field['label'], 'tuancele_turnstile_field_callback', 'tuancele-amp-turnstile', 'tuancele_turnstile_main_section', array_merge($field, ['id' => $id]));

    register_setting('tuancele_amp_floating_buttons_group', 'tuancele_floating_buttons_options');
    add_settings_section('tuancele_floating_buttons_main_section', 'Thiết lập hiển thị', null, 'tuancele-amp-floating-buttons');
    add_settings_field('enable_call_button', 'Kích hoạt Nút Gọi', 'tuancele_floating_buttons_field_callback', 'tuancele-amp-floating-buttons', 'tuancele_floating_buttons_main_section', ['id' => 'enable_call_button']);
    add_settings_field('enable_form_button', 'Kích hoạt Nút Form', 'tuancele_floating_buttons_field_callback', 'tuancele-amp-floating-buttons', 'tuancele_floating_buttons_main_section', ['id' => 'enable_form_button']);
}

/**
 * 4. CÁC HÀM CALLBACK VÀ SANITIZE CHO TỪNG LOẠI CÀI ĐẶT
 */

function tuancele_integrations_field_callback($args) {
    $options = get_option('tuancele_integrations_settings', []);
    $id = $args['id']; $value = $options[$id] ?? '';
    echo '<input type="text" id="'.esc_attr($id).'" name="tuancele_integrations_settings['.esc_attr($id).']" value="'.esc_attr($value).'" class="regular-text" />';
}

// --- [NÂNG CẤP] Callbacks cho Schema ---
function tuancele_amp_schema_field_callback($args) {
    $options = get_option('tuancele_amp_schema_options', []);
    $id = $args['id'];
    $value = $options[$id] ?? '';
    $type = $args['type'] ?? 'text';
    $placeholder = '';
    
    if ($type === 'select') {
        echo '<select id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']">';
        if (!empty($args['options']) && is_array($args['options'])) {
            foreach ($args['options'] as $option_value => $label) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($option_value),
                    selected($value, $option_value, false),
                    esc_html($label)
                );
            }
        }
        echo '</select>';
    } elseif (in_array($id, ['description', 'sameAs', 'openingHours'])) {
        echo '<textarea id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']" rows="5" class="large-text code">' . esc_textarea($value) . '</textarea>';
        if ($id === 'sameAs') { echo '<p class="description">Nhập mỗi URL mạng xã hội trên một dòng.</p>'; }
        if ($id === 'openingHours') { echo '<p class="description">Định dạng chuẩn: <code>Mo-Fr 08:00-17:00</code>. Mỗi khoảng thời gian trên một dòng.</p>'; }
    } else {
        if (in_array($id, ['latitude', 'longitude'])) { $placeholder = 'Ví dụ: 21.028511'; }
        echo '<input type="text" id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';
    }
    
    if (!empty($args['desc'])) {
        echo '<p class="description">' . esc_html($args['desc']) . '</p>';
    }
}

function tuancele_amp_sanitize_callback($input) {
    $new_input = [];
    if ( !is_array($input) ) return $new_input;

    foreach ($input as $key => $value) {
        switch ($key) {
            case 'email': $new_input[$key] = sanitize_email($value); break;
            case 'logo': case 'url': $new_input[$key] = esc_url_raw(trim($value)); break;
            case 'sameAs':
                 $urls = preg_split('/[\r\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
                 $sanitized_urls = [];
                 foreach ($urls as $url) { $sanitized_urls[] = esc_url_raw(trim($url)); }
                 $new_input[$key] = implode("\r\n", array_filter($sanitized_urls));
                 break;
            case 'description': case 'openingHours': $new_input[$key] = sanitize_textarea_field($value); break;
            case 'latitude': case 'longitude': $new_input[$key] = preg_replace('/[^0-9.-]/', '', $value); break;
            default: $new_input[$key] = sanitize_text_field(trim($value)); break;
        }
    }
    return $new_input;
}

// --- Callbacks cho SMTP ---
function tuancele_smtp_section_callback() {
    echo '<p>Sử dụng SMTP để tăng độ tin cậy khi gửi mail, tránh bị rơi vào hòm thư Spam.</p>';
    $status = get_option('tuancele_smtp_connection_status');
    if ($status && isset($status['message'])) {
        $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
        echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
    } else {
         echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra hoặc chưa lưu cài đặt.</span></strong>';
    }
}
function tuancele_smtp_field_callback($args) {
    $options = get_option('tuancele_smtp_settings', []);
    $id = $args['id'];
    $value = isset($options[$id]) ? $options[$id] : ($args['default'] ?? '');
    $type = $args['type'] ?? 'text';

    switch ($type) {
        case 'checkbox':
             echo '<label><input type="checkbox" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
             break;
        case 'password':
            echo '<input type="password" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
            break;
        case 'select':
            echo '<select id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']">';
            if (isset($args['options']) && is_array($args['options'])) {
                foreach ($args['options'] as $val => $label) {
                    echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>' . esc_html($label) . '</option>';
                }
            }
            echo '</select>';
            break;
        default:
            echo '<input type="' . esc_attr($type) . '" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
             break;
    }
}

// --- Callbacks cho R2 ---
function tuancele_r2_section_callback() {
    echo '<p>Điền các thông tin dưới đây để kết nối website của bạn với dịch vụ lưu trữ Cloudflare R2.</p>';
    $status = get_option('tuancele_r2_connection_status');
     if ($status && isset($status['message'])) {
        $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
        echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
    } else {
         echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra.</span></strong>';
    }
}
function tuancele_r2_field_callback($args) {
    $options = get_option('tuancele_r2_settings', []);
    $id = $args['id'];
    $value = $options[$id] ?? '';
    $type = $args['type'] ?? 'text';

    switch ($type) {
        case 'checkbox':
            echo '<label><input type="checkbox" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
            break;
        case 'password':
            echo '<input type="password" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
            break;
        default:
            echo '<input type="text" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
            break;
    }
}

function tuancele_r2_migration_section_callback() {
    echo '<p>Sử dụng công cụ này để tải lên Cloudflare R2 toàn bộ các tệp media đã được tải lên từ trước.</p>';
}

function tuancele_r2_migration_tool_callback() {
    $status = get_option('tuancele_r2_migration_status', ['running' => false, 'total' => 0, 'processed' => 0]);
    $is_running = $status['running'];
    
    $local_query = new WP_Query([
        'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids',
        'meta_query' => [['key' => '_tuancele_r2_offloaded', 'compare' => 'NOT EXISTS']]
    ]);
    $local_count = $local_query->post_count;
    ?>
    <style>#r2-migration-tool{border:1px solid #ccd0d4;padding:20px;background:#fff;border-radius:4px}#r2-migration-status{font-weight:700;margin-bottom:15px}#r2-progress-bar-container{width:100%;background-color:#e0e0e0;border-radius:4px;overflow:hidden;height:25px;margin-top:15px}#r2-progress-bar{width:0;height:100%;background-color:#4caf50;text-align:center;line-height:25px;color:#fff;transition:width .3s ease}#r2-migration-tool button{margin-right:10px}</style>
    <div id="r2-migration-tool">
        <div id="r2-migration-status"></div>
        <div id="r2-progress-bar-container"><div id="r2-progress-bar">0%</div></div>
        <p style="margin-top:15px">
            <button type="button" class="button button-primary" id="start-r2-migration" <?php if ($is_running || $local_count === 0) echo 'disabled'; ?>>Bắt đầu Di chuyển <?php echo $local_count; ?> tệp</button>
            <button type="button" class="button" id="cancel-r2-migration" <?php if (!$is_running) echo 'disabled'; ?>>Hủy bỏ</button>
        </p>
    </div>
    <?php
}

// --- Callbacks cho Turnstile ---
function tuancele_turnstile_field_callback($args) {
    $options = get_option('tuancele_turnstile_settings', []);
    $id = $args['id'];
    $value = $options[$id] ?? '';
    $type = $args['type'] ?? 'text';
    echo '<input type="' . esc_attr($type) . '" id="tuancele_turnstile_' . esc_attr($id) . '" name="tuancele_turnstile_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
}

// --- Callbacks cho Floating Buttons ---
function tuancele_floating_buttons_field_callback($args) {
    $options = get_option('tuancele_floating_buttons_options', []);
    $id = $args['id'];
    $checked = isset($options[$id]) && $options[$id] === 'on';
    echo '<label><input type="checkbox" name="tuancele_floating_buttons_options[' . esc_attr($id) . ']" value="on" ' . checked($checked, true, false) . '></label>';
}

/**
 * 5. HÀM TỔNG HỢP ĐỂ TẢI SCRIPT CHO CÁC TRANG CÀI ĐẶT
 */
function tuancele_amp_settings_admin_scripts($hook) {
    $pages_with_toggle = ['cai-dat-amp_page_tuancele-amp-smtp', 'cai-dat-amp_page_tuancele-amp-r2'];

    if ( in_array($hook, $pages_with_toggle) ) {
        $script_toggle = "
        jQuery(document).ready(function($) {
            const mainCheckbox = $('input[type=\"checkbox\"][name*=\"[enable_\"]');
            if (mainCheckbox.length > 0) {
                const dependentFields = mainCheckbox.closest('tr').nextAll();
                function toggleFields() {
                    if (mainCheckbox.is(':checked')) { dependentFields.show(); } else { dependentFields.hide(); }
                }
                toggleFields(); 
                mainCheckbox.on('change', toggleFields);
            }
        });";
        wp_add_inline_script('jquery-core', $script_toggle);
    }

    if ($hook === 'cai-dat-amp_page_tuancele-amp-r2') {
        wp_enqueue_script(
            'tuancele-r2-migration',
            get_template_directory_uri() . '/assets/js/admin-r2-migration.js',
            ['jquery'], '1.1', true
        );

        $nonce_data_script = sprintf(
            'const tuanceleR2Data = { ajax_url: "%s", nonce: "%s" };',
            admin_url('admin-ajax.php'),
            wp_create_nonce('r2_migration_nonce')
        );

        wp_add_inline_script('tuancele-r2-migration', $nonce_data_script, 'before');
    }
}