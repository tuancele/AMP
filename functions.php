<?php
/**
 * functions.php
 * Tệp chính của theme, chịu trách nhiệm tải các file chức năng con.
 * PHIÊN BẢN 2.0 (Tái cấu trúc)
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
require_once $theme_dir . '/inc/theme-setup.php'; // (Từ Bước 1)

/**
 * =========================================================================
 * 2. TẢI CÁC MODULE CHỨC NĂNG (CORE LOGIC)
 * =========================================================================
 */

// Tải Lõi AMP Engine
require_once $theme_dir . '/inc/amp-core.php'; // (Từ Bước 1)

// Tải các Module Class
require_once $theme_dir . '/inc/admin-settings-module.php'; // (Từ Bước 5)
require_once $theme_dir . '/inc/integrations-module.php'; // (Từ Bước 4)
require_once $theme_dir . '/inc/comments-module.php';     // (Từ Bước 4)
require_once $theme_dir . '/inc/seo-module.php';         // (Từ Bước 3)
require_once $theme_dir . '/inc/shortcodes-module.php'; // (Từ Bước 2)

// [THAY ĐỔI BẮT ĐẦU - BƯỚC 6]
require_once $theme_dir . '/inc/event-module.php';         // (Mới)
// [THAY ĐỔI KẾT THÚC - BƯỚC 6]

// Tải các tệp logic "loose" còn lại
require_once $theme_dir . '/inc/template-helpers.php';
require_once $theme_dir . '/inc/meta-boxes.php';
require_once $theme_dir . '/inc/image-map-data.php';

// Khởi chạy Module Cloudflare R2
require_once $theme_dir . '/inc/r2/class-r2-integration.php';
Tuancele_R2_Integration::get_instance();
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
 */
function tuancele_theme_activation_flush_rewrites() {
    // Tải các CPT để đăng ký
    tuancele_register_service_cpt(); // Hàm này vẫn ở global (functions.php)
    
    // [THAY ĐỔI BẮT ĐẦU - BƯỚC 6]
    // Tải và gọi hàm đăng ký CPT từ Module Event
    require_once get_template_directory() . '/inc/event-module.php';
    (new AMP_Event_Module())->register_event_cpt();
    // [THAY ĐỔI KẾT THÚC - BƯỚC 6]

    // (Chúng ta sẽ xử lý image-map ở bước tiếp theo)
    require_once get_template_directory() . '/inc/image-map-data.php';
    tuancele_register_image_map_cpt(); //
    
    // Flush rules
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'tuancele_theme_activation_flush_rewrites');


/**
 * 2. Tự động tạo nội dung cho file iframe của Turnstile CAPTCHA.
 *
 */
function tuancele_generate_turnstile_iframe() {
    if ( isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/turnstile-iframe.html' ) {
        // Lấy Site Key từ cài đặt
        $options = get_option('tuancele_turnstile_settings', []);
        $site_key = $options['site_key'] ?? '';

        // In ra nội dung HTML và dừng lại
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Turnstile AMP Iframe</title>
            <script src="https://challenges.cloudflare.com/api/v0/api.js?onload=onTurnstileLoad" async defer></script>
        </head>
        <body>
            <div id="turnstile-widget-container"></div>
            <script>
                function onTurnstileLoad() {
                    if (typeof turnstile !== 'undefined') {
                        turnstile.render('#turnstile-widget-container', {
                            sitekey: '<?php echo esc_js($site_key); ?>',
                            callback: function(token) {
                                if (window.parent) {
                                    window.parent.postMessage({
                                        sentinel: 'amp',
                                        type: 'turnstile-token',
                                        token: token
                                    }, '*');
                                }
                            },
                        });
                    }
                }
            </script>
        </body>
        </html>
        <?php
        exit(); // Dừng WordPress không xử lý tiếp
    }
}
add_action('init', 'tuancele_generate_turnstile_iframe');


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
 * =========================================================================
 * 4. KHỞI CHẠY CÁC MODULE CLASS
 * =========================================================================
 */
function tuancele_init_functional_modules() {
    // Khởi chạy Module Shortcodes
    new AMP_Shortcodes_Module();

    // Khởi chạy Module SEO
    new AMP_SEO_Module();

    // Khởi chạy Module Tích hợp
    new AMP_Integrations_Module();

    // Khởi chạy Module Bình luận
    new AMP_Comments_Module();
    
    // Khởi chạy Module Cài đặt Admin
    if ( is_admin() ) {
        new AMP_Admin_Settings_Module();
    }

    // [THAY ĐỔI MỚI - BƯỚC 6]
    // Khởi chạy Module Sự kiện (Event)
    new AMP_Event_Module();
    // [THAY ĐỔI KẾT THÚC - BƯỚC 6]
}
add_action('init', 'tuancele_init_functional_modules');