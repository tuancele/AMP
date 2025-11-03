<?php
/**
 * functions.php
 * File chính của theme, chịu trách nhiệm tải các file chức năng con.
 */

// Ngăn truy cập trực tiếp
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Lấy đường dẫn thư mục theme
$theme_dir = get_template_directory();

/**
 * Tải tất cả các file chức năng của theme từ thư mục /inc.
 */
require_once $theme_dir . '/inc/core-setup.php';
require_once $theme_dir . '/inc/admin-settings.php';
require_once $theme_dir . '/inc/comments-handler.php';
require_once $theme_dir . '/inc/integrations.php';
require_once $theme_dir . '/inc/seo-helpers.php';
require_once $theme_dir . '/inc/shortcodes.php';
require_once $theme_dir . '/inc/template-helpers.php';
require_once $theme_dir . '/inc/event-manager.php';
require_once $theme_dir . '/inc/meta-boxes.php';

// [THÊM MỚI] Tải file quản lý dữ liệu Image Map
require_once $theme_dir . '/inc/image-map-data.php'; 
// [THAY ĐỔI] Khởi chạy Module Cloudflare R2
require_once $theme_dir . '/inc/r2/class-r2-integration.php';
Tuancele_R2_Integration::get_instance();


/**
 * =========================================================================
 * GIẢI PHÁP "ACTIVE LÀ CHẠY"
 * =========================================================================
 */

/**
 * 1. Tự động tạo các cài đặt mặc định khi kích hoạt theme.
 * Chạy một lần duy nhất khi người dùng kích hoạt theme.
 */
function tuancele_theme_activation_defaults() {
    // Chỉ thêm cài đặt nếu nó chưa tồn tại để không ghi đè lên cài đặt của người dùng
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
 * 2. Tự động tạo nội dung cho file iframe của Turnstile CAPTCHA.
 * Giúp người dùng không cần phải copy file thủ công.
 */
function tuancele_generate_turnstile_iframe() {
    // Kiểm tra nếu người dùng đang truy cập đúng đường dẫn
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


// Phần code đăng ký Custom Post Type "Sản phẩm" đã được loại bỏ.
/**
 * Đăng ký Custom Post Type cho Dịch vụ (Service)
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
        
        // [THAY ĐỔI DUY NHẤT Ở ĐÂY]
        // Thay 'true' bằng slug của menu cha 'Cài đặt AMP'
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