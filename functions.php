<?php
/**
 * functions.php
 * Tệp chính của theme, chịu trách nhiệm tải các file chức năng con.
 * PHIÊN BẢN 2.0 (Tái cấu trúc)
 *
 * [TỐI ƯU V1 - CSS CACHE]
 * - Đã thêm logic xóa transient CSS trong hàm tuancele_theme_activation_flush_rewrites().
 */

// Ngăn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Lấy đường dẫn thư mục theme
$theme_dir = get_template_directory();

/**
 * Tải thư viện Composer (nếu có)
 */
if ( file_exists( $theme_dir . '/vendor/autoload.php' ) ) {
    require_once $theme_dir . '/vendor/autoload.php';
}

/**
 * =========================================================================
 * 1. TẢI CÁC THIẾT LẬP GIAO DIỆN (SKIN)
 * =========================================================================
 */
// QUAN TRỌNG: File này phải được tải TRƯỚC BƯỚC 3
// vì nó định nghĩa hằng số TUANCELE_CSS_CACHE_KEY
require_once $theme_dir . '/inc/theme-setup.php'; // (Từ Bước 1)

/**
 * =========================================================================
 * 2. TẢI CÁC MODULE CHỨC NĂNG (CORE LOGIC)
 * =========================================================================
 */

// Tải Lõi AMP Engine
require_once $theme_dir . '/inc/amp-core.php'; // (Từ Bước 1)

// Tải các Module Class
// require_once $theme_dir . '/inc/admin-settings-loader.php'; // (ĐÃ VÔ HIỆU HÓA TỆP MỚI)
require_once $theme_dir . '/inc/admin-settings-module.php'; // (ĐÃ KÍCH HOẠT LẠI TỆP CŨ)

// [THÊM MỚI] Tải tệp Hướng dẫn Shortcode đã được tách
require_once $theme_dir . '/inc/admin-shortcode-guide.php'; 

require_once $theme_dir . '/inc/integrations-module.php'; // (Từ Bước 4)
require_once $theme_dir . '/inc/comments-module.php';     // (Từ Bước 4)
require_once $theme_dir . '/inc/seo-module.php';         // (Từ Bước 3)
require_once $theme_dir . '/inc/shortcodes-module.php'; // (Từ Bước 2)

// [THAY ĐỔI BẮT ĐẦU - BƯỚC 6]
require_once $theme_dir . '/inc/event-module.php';         // (Mới)
// [SỬA LỖI] Khởi chạy Module Sự kiện ngay lập tức
// để CPT 'event' được đăng ký ở 'init' priority 5
// trước khi 'admin_menu' được gọi.
new AMP_Event_Module();
// [THAY ĐỔI KẾT THÚC - BƯỚC 6]

// Tải các tệp logic "loose" còn lại (ĐÃ TÁI CẤU TRÚC)
$helpers_dir = $theme_dir . '/inc/helpers/';
require_once $helpers_dir . 'logging.php';
require_once $helpers_dir . 'template-tags.php';
require_once $helpers_dir . 'content-filters.php';
require_once $helpers_dir . 'query-filters.php';
require_once $helpers_dir . 'utilities.php';
require_once $theme_dir . '/inc/meta-boxes.php';
require_once $theme_dir . '/inc/image-map-data.php';

// Khởi chạy Module Cloudflare R2
require_once $theme_dir . '/inc/r2/class-r2-integration.php';
Tuancele_R2_Integration::get_instance();

// (ĐÃ VÔ HIỆU HÓA LỆNH KHỞI CHẠY CỦA TỆP MỚI)
// if ( is_admin() ) {
//     new AMP_Admin_Settings_Loader();
// }

/**
 * =========================================================================
 * 3. LOGIC KHI KÍCH HOẠT THEME (ACTIVE TO RUN)
 * =========================================================================
 */

/**
 * 1. Tự động tạo các cài đặt mặc định khi kích hoạt theme.
 *
 */
function tuancele_theme_activation_defaults() {
    // Chỉ thêm cài đặt nếu nó chưa tồn tại
    if ( get_option('tuancele_floating_buttons_options') === false ) {
        update_option('tuancele_floating_buttons_options', [
            'enable_call_button' => 'on',
            'enable_form_button' => 'on',
        ]);
    }
    if ( get_option('tuancele_smtp_settings') === false ) {
        update_option('tuancele_smtp_settings', [
            'notification_email' => get_option('admin_email'),
            'enable_smtp'        => '', // Mặc định tắt
        ]);
    }
}
add_action('after_switch_theme', 'tuancele_theme_activation_defaults');

/**
 * Flush rewrite rules khi kích hoạt để nhận CPTs mới.
 * [TỐI ƯU V1]: Đồng thời xóa cache CSS.
 */
function tuancele_theme_activation_flush_rewrites() {
    // Tải các CPT để đăng ký
    tuancele_register_service_cpt();
    
    // Tải và gọi hàm đăng ký CPT từ Module Event
    require_once get_template_directory() . '/inc/event-module.php';
    (new AMP_Event_Module())->register_event_cpt();

    // Tải và gọi hàm đăng ký CPT Image Map
    require_once get_template_directory() . '/inc/image-map-data.php';
    tuancele_register_image_map_cpt();

    // [THÊM MỚI] Kiểm tra và đăng ký CPT Bất động sản
    $integration_options = get_option('tuancele_integrations_settings', []);
    $is_property_enabled = isset($integration_options['enable_property_cpt']) && $integration_options['enable_property_cpt'] === 'on';

    if ($is_property_enabled) {
        // Gọi hàm đăng ký CPT BĐS (hàm này nằm ngay bên dưới)
        tuancele_register_property_cpt();
    }
    
    // Flush rules
    flush_rewrite_rules();
    
    // [TỐI ƯU V1 - BẮT ĐẦU]
    // Xóa transient cache CSS để đảm bảo các thay đổi mới nhất được áp dụng.
    // Hằng số TUANCELE_CSS_CACHE_KEY đã được định nghĩa trong 'inc/theme-setup.php' (được tải ở đầu file functions.php)
    delete_transient( TUANCELE_CSS_CACHE_KEY );
    // [TỐI ƯU V1 - KẾT THÚC]
}
add_action('after_switch_theme', 'tuancele_theme_activation_flush_rewrites');


/**
 * Đăng ký Custom Post Type cho Dịch vụ (Service)
 *
 */
function tuancele_register_service_cpt() {
    $labels = [
        'name'                  => _x('Dịch vụ', 'Post Type General Name', 'tuancele-amp'),
        'singular_name'         => _x('Dịch vụ', 'Post Type Singular Name', 'tuancele-amp'),
        'menu_name'             => __('Dịch vụ', 'tuancele-amp'),
        'name_admin_bar'        => __('Dịch vụ', 'tuancele-amp'),
        'add_new'               => __('Thêm mới', 'tuancele-amp'),
        'add_new_item'          => __('Thêm Dịch vụ mới', 'tuancele-amp'),
        'edit_item'             => __('Chỉnh sửa Dịch vụ', 'tuancele-amp'),
        'all_items'             => __('Tất cả Dịch vụ', 'tuancele-amp'),
    ];
    $args = [
        'label'                 => __('Dịch vụ', 'tuancele-amp'),
        'description'           => __('Quản lý các dịch vụ', 'tuancele-amp'),
        'labels'                => $labels,
        'supports'              => ['title', 'editor', 'excerpt', 'thumbnail'],
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'tuancele-amp-settings',
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-star-filled',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true, 
        'rewrite'               => ['slug' => 'dich-vu'],
    ];
    register_post_type('service', $args);
}
add_action('init', 'tuancele_register_service_cpt', 0);

/**
 * Đăng ký Custom Post Type cho Bất động sản (Property)
 *
 */
// [THÊM MỚI] Lấy cài đặt Tích hợp
$integration_options = get_option('tuancele_integrations_settings', []);
$is_property_enabled = isset($integration_options['enable_property_cpt']) && $integration_options['enable_property_cpt'] === 'on';

// [THÊM MỚI] Chỉ chạy code BĐS nếu được kích hoạt
if ($is_property_enabled) {

function tuancele_register_property_cpt() {
    $labels = [
        'name'                  => _x('Bất động sản', 'Post Type General Name', 'tuancele-amp'),
        'singular_name'         => _x('Bất động sản', 'Post Type Singular Name', 'tuancele-amp'),
        'menu_name'             => __('Tin BĐS', 'tuancele-amp'),
        'name_admin_bar'        => __('Tin BĐS', 'tuancele-amp'),
        'add_new'               => __('Đăng tin mới', 'tuancele-amp'),
        'add_new_item'          => __('Đăng tin BĐS mới', 'tuancele-amp'),
        'edit_item'             => __('Chỉnh sửa tin', 'tuancele-amp'),
        'all_items'             => __('Tất cả tin BĐS', 'tuancele-amp'),
    ];
    $args = [
        'label'                 => __('Bất động sản', 'tuancele-amp'),
        'description'           => __('Quản lý tin đăng Bất động sản', 'tuancele-amp'),
        'labels'                => $labels,
        'supports'              => ['title', 'editor', 'excerpt', 'thumbnail', 'comments'], // Thêm 'comments'
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true, // Hiển thị ở menu chính
        'menu_position'         => 5, // Đặt ngay dưới "Bài viết"
        'menu_icon'             => 'dashicons-admin-home', // Icon ngôi nhà
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true, 
        'rewrite'               => ['slug' => 'bat-dong-san'],
    ];
    register_post_type('property', $args);
}
add_action('init', 'tuancele_register_property_cpt', 0);
}

/**
 * =========================================================================
 * 4. KHỞI CHẠY CÁC MODULE CLASS
 * =========================================================================
 */
function tuancele_init_functional_modules() {

    // [SỬA LỖI] Khởi chạy Module QAPage MỚI ở đây (bên trong 'init')
    new AMP_QAPage_Module();
    
    // Khởi chạy Module Shortcodes
    new AMP_Shortcodes_Module();

    // Khởi chạy Module SEO
    new AMP_SEO_Module();

    // Khởi chạy Module Tích hợp
    new AMP_Integrations_Module();

    // Khởi chạy Module Bình luận
    new AMP_Comments_Module();

    // [THAY ĐỔI MỚI - BƯỚC 6]
    // Khởi chạy Module Sự kiện (Event)
    // (ĐÃ DI CHUYỂN LÊN TRÊN GLOBAL SCOPE ĐỂ SỬA LỖI)
    // [THAY ĐỔI KẾT THÚC - BƯỚC 6]

    // [THÊM MỚI] Khởi chạy Module Cài đặt Admin (Từ file cũ)
    if ( is_admin() ) {
        new AMP_Admin_Settings_Module();
        
        // [THÊM MỚI] Khởi chạy Module Hướng dẫn Shortcode
        new AMP_Shortcode_Guide_Module();
    }
}
add_action('init', 'tuancele_init_functional_modules');

/**
 * =========================================================================
 * KHỞI CHẠY MODULE QAPAGE MỚI (ĐỘC LẬP)
 * =========================================================================
 */
require_once $theme_dir . '/inc/qapage/qapage-module.php';

/**
 * =========================================================================
 * NÂNG CẤP LOG V4.0 - TẠO BẢNG CSDL KHI KÍCH HOẠT THEME
 * =========================================================================
 */

/**
 * Hàm này sẽ được gọi khi theme được kích hoạt (hoặc kích hoạt lại)
 * để tạo bảng CSDL tùy chỉnh cho việc ghi log.
 */
function tuancele_create_visitor_log_table() {
    global $wpdb;
    
    // Tên bảng mới của chúng ta, có tiền tố (prefix) của WordPress
    $table_name = $wpdb->prefix . 'visitor_logs';
    
    // Lấy collation của CSDL (ví dụ: utf8mb4_unicode_ci)
    $charset_collate = $wpdb->get_charset_collate();

    // Câu lệnh SQL để tạo bảng
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        visit_time DATETIME NOT NULL,
        ip_address VARCHAR(100) NOT NULL,
        location VARCHAR(255) NOT NULL,
        isp VARCHAR(255) NOT NULL,
        org VARCHAR(255) NOT NULL,
        country_code CHAR(5) NOT NULL,
        request_uri VARCHAR(1024) NOT NULL,
        
        PRIMARY KEY  (id),
        INDEX time_idx (visit_time),
        INDEX ip_idx (ip_address),
        INDEX uri_idx (request_uri(191))
    ) $charset_collate;";

    // Yêu cầu WordPress thực thi câu lệnh SQL
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Đăng ký hàm này để nó chạy mỗi khi theme được kích hoạt
add_action('after_switch_theme', 'tuancele_create_visitor_log_table');

// [ĐÃ XÓA DÒNG '}' BỊ THỪA GÂY LỖI]
// TẠM THỜI: Xóa cache CSS
delete_transient('tuancele_amp_css_cache_v2');