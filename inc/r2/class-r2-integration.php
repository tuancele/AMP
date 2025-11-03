<?php
// File: inc/r2/class-r2-integration.php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Tuancele_R2_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        $r2_dir = get_template_directory() . '/inc/r2/';
        require_once $r2_dir . 'class-r2-client.php';
        require_once $r2_dir . 'class-r2-webp.php';
        require_once $r2_dir . 'class-r2-actions.php';
        require_once $r2_dir . 'class-r2-rewriter.php';
        
        // Tải AWS SDK
        if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
            require_once get_template_directory() . '/vendor/autoload.php';
        }
    }

    private function init_hooks() {
        $client = Tuancele_R2_Client::get_instance();

        // Chỉ thêm các hook nếu R2 được bật
        if ($client->is_enabled()) {
            $actions = new Tuancele_R2_Actions();
            $rewriter = new Tuancele_R2_Rewriter();

            // Hooks cho upload và delete
            add_filter('wp_generate_attachment_metadata', [$actions, 'handle_upload'], 20, 2);
            add_action('delete_attachment', [$actions, 'handle_delete'], 10, 1);
            
            // Hooks cho việc viết lại URL
            add_filter('wp_get_attachment_url', [$rewriter, 'rewrite_attachment_url'], 99, 2);
            add_filter('wp_get_attachment_image_src', [$rewriter, 'rewrite_image_src'], 99, 2);
            add_filter('wp_calculate_image_srcset', [$rewriter, 'rewrite_srcset'], 99, 5);
        }

        // Hook để kiểm tra kết nối khi lưu cài đặt
        add_action('update_option_tuancele_r2_settings', [$this, 'handle_settings_update'], 10, 2);
    }
    
    public function handle_settings_update($old_value, $new_value) {
        if (!isset($new_value['enable_r2']) || $new_value['enable_r2'] !== 'on') {
            update_option('tuancele_r2_connection_status', ['success' => true, 'message' => 'Đã tắt.']);
            return;
        }
        $status = Tuancele_R2_Client::test_connection($new_value);
        update_option('tuancele_r2_connection_status', $status);
    }
}