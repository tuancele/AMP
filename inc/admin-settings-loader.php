<?php
/**
 * File: inc/admin-settings-loader.php
 *
 * Tải và khởi tạo tất cả các trang cài đặt trong /inc/admin/
 * File này thay thế hoàn toàn cho /inc/admin-settings-module.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class AMP_Admin_Settings_Loader {

    /**
     * Danh sách các đối tượng trang cài đặt.
     * @var array
     */
    private $pages = [];

    /**
     * Slug của menu cha chính.
     * @var string
     */
    private $main_menu_slug = 'tuancele-amp-settings';

    /**
     * Khởi tạo loader: Tải file, khởi tạo class, và gán hook.
     */
    public function __construct() {
        // Tải tất cả các file class con
        $this->load_files();
        
        // Khởi tạo các đối tượng class
        $this->init_pages();

        // Gán các hook chính của WordPress
        add_action( 'admin_menu', [ $this, 'create_admin_menus' ] );
        add_action( 'admin_init', [ $this, 'register_all_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * 1. Tải tất cả các file class cài đặt từ thư mục /inc/admin/
     */
    private function load_files() {
        $admin_dir = get_template_directory() . '/inc/admin/';
        
        // Tải Base Class (Lớp Cơ sở) trước
        require_once $admin_dir . 'class-admin-settings-page.php';
        
        // Tải các class của từng trang con
        require_once $admin_dir . 'page-shortcode-guide.php';
        require_once $admin_dir . 'page-integrations-settings.php';
        require_once $admin_dir . 'page-schema-settings.php';
        require_once $admin_dir . 'page-smtp-settings.php';
        require_once $admin_dir . 'page-r2-settings.php';
        require_once $admin_dir . 'page-recaptcha-settings.php';
        require_once $admin_dir . 'page-floating-buttons-settings.php';
    }

    /**
     * 2. Khởi tạo các đối tượng trang (pages)
     */
    private function init_pages() {
        // Trang Shortcode Guide (trang đặc biệt, không kế thừa Base Class)
        $this->pages['guide'] = new AMP_Shortcode_Guide_Page( $this->main_menu_slug );
        
        // Các trang cài đặt (kế thừa Base Class)
        $this->pages['integrations'] = new AMP_Integrations_Settings_Page( $this->main_menu_slug );
        $this->pages['schema']       = new AMP_Schema_Settings_Page( $this->main_menu_slug );
        $this->pages['smtp']         = new AMP_SMTP_Settings_Page( $this->main_menu_slug );
        $this->pages['r2']           = new AMP_R2_Settings_Page( $this->main_menu_slug );
        $this->pages['recaptcha']    = new AMP_Recaptcha_Settings_Page( $this->main_menu_slug );
        $this->pages['floating']     = new AMP_Floating_Buttons_Page( $this->main_menu_slug );
    }

    /**
     * 3. Gắn các trang vào Menu Admin
     */
    public function create_admin_menus() {
        // Tạo menu cha chính
        add_menu_page(
            'Cài đặt Theme AMP',           // Tiêu đề trang
            'Cài đặt AMP',                 // Tiêu đề menu
            'manage_options',              // Quyền
            $this->main_menu_slug,         // Slug menu
            [ $this->pages['guide'], 'render_page' ], // Trang "Guide" là trang chính
            'dashicons-superhero-alt',     // Icon
            60                             // Vị trí
        );

        // Tạo các trang con (submenu)
        foreach ( $this->pages as $page ) {
            $page->add_menu_page();
        }
    }

    /**
     * 4. Đăng ký tất cả các nhóm cài đặt (Settings)
     */
    public function register_all_settings() {
        foreach ( $this->pages as $page ) {
            // Chỉ gọi register_settings nếu class đó có hàm này (trang Guide không có)
            if ( method_exists( $page, 'register_settings' ) ) {
                $page->register_settings();
            }
        }
    }

    /**
     * 5. Tải các CSS/JS cho trang Admin (Phiên bản đã tách file)
     */
    public function enqueue_admin_assets( $hook ) {
        // Chỉ tải script/style nếu ĐÚNG là các trang cài đặt của theme
        if ( strpos( $hook, 'tuancele-amp-' ) === false ) {
            return;
        }

        // Tải CSS/JS chung cho các trang cài đặt
        wp_enqueue_style(
            'tuancele-admin-settings',
            get_template_directory_uri() . '/assets/css/admin-settings.css',
            [], '1.0'
        );
        wp_enqueue_script(
            'tuancele-admin-settings',
            get_template_directory_uri() . '/assets/js/admin-settings.js',
            ['jquery'], '1.0', true
        );

        // Logic tải script Migration cho trang R2 (file này là chuyên biệt, giữ nguyên)
        if ( $hook === 'cai-dat-amp_page_tuancele-amp-r2' ) {
            
            $local_js_url = get_site_url( null, 'wp-content/themes/amp/inc/r2/admin-r2-migration.js' );

            wp_enqueue_script(
                'tuancele-r2-migration',
                $local_js_url,
                ['jquery'], '1.2', true
            );

            // Truyền nonce và ajax_url cho script R2
            $nonce_data_script = sprintf(
                'const tuanceleR2Data = { ajax_url: "%s", nonce: "%s" };',
                admin_url( 'admin-ajax.php' ),
                wp_create_nonce( 'r2_migration_nonce' )
            );

            wp_add_inline_script( 'tuancele-r2-migration', $nonce_data_script, 'before' );
        }
    }
}