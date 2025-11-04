<?php
// File: inc/admin/page-shortcode-guide.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Quản lý trang Hướng dẫn Shortcode (Trang tĩnh, không lưu cài đặt).
 */
class AMP_Shortcode_Guide_Page {
    
    private $id = 'tuancele-amp-settings'; // Trang này là trang chính
    private $parent_slug;

    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;
    }

    // Gắn vào menu (dưới dạng trang chính)
    public function add_menu_page() {
        // Hàm này được gọi bởi Loader (sẽ tạo ở Bước 3)
        // Nó sẽ ghi đè hook add_menu_page mặc định
        add_submenu_page(
            $this->parent_slug,
            'Hướng dẫn sử dụng Shortcode',
            'Hướng dẫn Shortcode',
            'manage_options',
            $this->id, // Trùng với slug cha để trở thành trang đầu tiên
            [ $this, 'render_page' ]
        );
    }

    // Render trang bằng cách gọi file view
    public function render_page() {
        // DÒNG ĐÚNG
require_once get_template_directory() . '/inc/admin/view-shortcode-guide.php';
    }
}