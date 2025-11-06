<?php
/**
 * inc/admin-shortcode-guide.php
 * Module Class chuyên dụng để hiển thị trang "Hướng dẫn Shortcode".
 * Tệp này được tách ra từ admin-settings-module.php.
 * [FIX]: Sử dụng esc_html() để hiển thị code HTML mẫu chính xác.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class AMP_Shortcode_Guide_Module {

    /**
     * Khởi tạo module, đăng ký hook menu.
     */
    public function __construct() {
        // Chạy sau menu chính (priority 10) để đảm bảo menu cha tồn tại
        add_action('admin_menu', [ $this, 'add_guide_page_to_menu' ], 11);
    }

    /**
     * Đăng ký trang Hướng dẫn Shortcode vào menu "Cài đặt AMP".
     */
    public function add_guide_page_to_menu() {
        add_submenu_page(
            'tuancele-amp-settings',                 // $parent_slug (Menu cha đã được tạo bởi AMP_Admin_Settings_Module)
            'Hướng dẫn sử dụng Shortcode',          // $page_title
            'Hướng dẫn Shortcode',                  // $menu_title
            'manage_options',                        // $capability
            'tuancele-amp-shortcode-guide',          // $menu_slug (Slug mới cho trang này)
            [ $this, 'render_guide_page' ]           // $function (Hàm render)
        );
    }

    /**
     * Render nội dung HTML cho trang Hướng dẫn Shortcode.
     * (Đây là hàm shortcode_guide_page() đã được chuyển từ file cũ).
     */
    public function render_guide_page() {
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

            <?php /* === MỤC A/B TESTING === */ ?>
            <div class="shortcode-guide-box" style="border-left-color: #663399;">
                <h2>📊 A/B Testing (Thử nghiệm Biến thể)</h2>
                <div class="details">
                    <p class="description">Hiển thị các nội dung khác nhau cho người dùng để thử nghiệm. Tính năng này yêu cầu bạn phải thiết lập thử nghiệm trong trang <strong>Cài đặt AMP &gt; A/B Testing</strong> trước.</p>
                    <p class="note">Bạn phải bọc <strong>TẤT CẢ</strong> các biến thể (A và B) trong shortcode. AMP sẽ tự động chọn một phiên bản để hiển thị cho người dùng và theo dõi qua Google Analytics.</p>
                    <p><strong>Ví dụ: Thử nghiệm tiêu đề của Form:</strong></p>
                    <code>[ab_test_variant experiment="form_title_test" variant="tieu_de_goc"]
    [form_dang_ky tieu_de="Đăng Ký Tư Vấn Miễn Phí"]
[/ab_test_variant]

[ab_test_variant experiment="form_title_test" variant="tieu_de_moi"]
    [form_dang_ky tieu_de="Nhận Báo Giá Ngay!"]
[/ab_test_variant]</code>
<p><strong>Ví dụ: Thử nghiệm A/B cho Nút bấm (HTML đầy đủ):</strong></p>
                    <code><?php echo esc_html('[ab_test_variant experiment="cta_button_test" variant="button_xanh"]
    <a href="/lien-he/" class="bds-details-button" style="background-color: #007bff;">Liên Hệ Tư Vấn</a>
[/ab_test_variant]

[ab_test_variant experiment="cta_button_test" variant="button_do"]
    <a href="/lien-he/" class="bds-details-button" style="background-color: #dc3545;">Nhận Báo Giá Gấp</a>
[/ab_test_variant]'); ?></code>
<p><strong>Ví dụ: Thử nghiệm A/B cho Hình ảnh Banner (HTML đầy đủ):</strong></p>
                    <code><?php echo esc_html('[ab_test_variant experiment="homepage_banner_test" variant="banner_co_gai"]
    <amp-img src="/wp-content/uploads/2025/01/banner-co-gai.jpg" width="800" height="400" layout="responsive" alt="Banner Giam Gia"></amp-img>
[/ab_test_variant]

[ab_test_variant experiment="homepage_banner_test" variant="banner_toa_nha"]
    <amp-img src="/wp-content/uploads/2025/01/banner-toa-nha.jpg" width="800" height="400" layout="responsive" alt="Banner Du An Moi"></amp-img>
[/ab_test_variant]'); ?></code>
                    <table>
                        <tr><th>Tham số</th><th>Mô tả</th></tr>
                        <tr><td><code>experiment</code></td><td><strong>(Bắt buộc)</strong> Tên (ID) của thử nghiệm. Tên này phải <strong>khớp</strong> với tên bạn đã tạo trong trang A/B Testing.</td></tr>
                        <tr><td><code>variant</code></td><td><strong>(Bắt buộc)</strong> Tên của biến thể. Tên này phải <strong>khớp</strong> với tên biến thể bạn đã tạo.</td></tr>
                    </table>
                </div>
            </div>

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
}