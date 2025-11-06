<?php
/**
 * inc/qapage/qapage-module.php
 *
 * TRÌNH TẢI CHÍNH (MAIN LOADER) CHO MODULE QAPAGE.
 * File này được gọi bởi functions.php.
 * Nhiệm vụ của nó là tải và khởi tạo tất cả các thành phần
 * của module QAPage một cách độc lập.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class AMP_QAPage_Module {

    /**
     * Khởi tạo module QAPage.
     * Tải các file phụ thuộc và khởi chạy các class điều khiển.
     */
    public function __construct() {
        // 1. Tải tất cả các file class cần thiết
        $this->load_dependencies();
        
        // 2. Khởi tạo các class (kích hoạt các hook)
        // Thứ tự khởi tạo rất quan trọng
        
        // Đăng ký CPT 'qapage_question'
        new AMP_QAPage_CPT();
        
        // Đăng ký Meta Box và Shortcode (liên kết nội dung)
        new AMP_QAPage_Metabox();
        new AMP_QAPage_Shortcodes();

        // Ghi đè template (Archive, Single, Comments)
        new AMP_QAPage_Templates();
        
        // Xử lý bảo mật (reCaptcha)
        new AMP_QAPage_Security();
        
        // Xử lý Schema (Gỡ Schema cũ, chèn QAPage Schema mới)
        new AMP_QAPage_Schema();
        
        // Đăng ký các endpoint AJAX (Vote, Accept, Ask)
        new AMP_QAPage_Ajax();
        
        // Tải CSS/JS động (Chỉ cho các trang QAPage)
        new AMP_QAPage_Assets();

        // Class Walker (AMP_QAPage_Walker_Comment) đã được tải,
        // nó sẽ được gọi bởi qapage-comments.php sau này.
    }

    /**
     * Tải các file class cần thiết từ thư mục /inc/qapage/
     */
    private function load_dependencies() {
        // Lấy đường dẫn thư mục module hiện tại
        $module_dir = get_template_directory() . '/inc/qapage/';

        // Tệp này (File 1) sẽ tải các file khác (2-10)
        
        // File 2: Đăng ký CPT
        require_once $module_dir . 'class-qapage-cpt.php';
        
        // File 3: Ghi đè Template
        require_once $module_dir . 'class-qapage-templates.php';
        
        // File 4: Logic Schema QAPage
        require_once $module_dir . 'class-qapage-schema.php';

        // File 5: Logic reCaptcha & Đăng ký
        require_once $module_dir . 'class-qapage-security.php';
        
        // File 6: Xử lý Vote & Accept Answer
        require_once $module_dir . 'class-qapage-ajax.php';
        
        // File 7: Meta box 'Link nội dung'
        require_once $module_dir . 'class-qapage-metabox.php';
        
        // File 8: Shortcode [qapage_related_list]
        require_once $module_dir . 'class-qapage-shortcodes.php';

        // File 9: Tải CSS/JS riêng
        require_once $module_dir . 'class-qapage-assets.php';
        
        // File 10: Hiển thị Answer/Comment
        require_once $module_dir . 'class-qapage-walker.php';
    }
}