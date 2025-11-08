<?php
// File: inc/r2/class-r2-integration.php
// ĐÃ HOÀN THIỆN: Bổ sung các hook cho công cụ Migration.

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
        
        // [THÊM MỚI] Tải file logic migration
        require_once $r2_dir . 'class-r2-migration.php';
        
        // Tải AWS SDK
        if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
            require_once get_template_directory() . '/vendor/autoload.php';
        }
    }

    private function init_hooks() {
        $client = Tuancele_R2_Client::get_instance();
        
        // [THAY ĐỔI] Khởi tạo $actions ở phạm vi rộng hơn
        $actions = new Tuancele_R2_Actions();

        // Chỉ thêm các hook (upload mới, xóa, viết lại URL) nếu R2 được bật
        if ($client->is_enabled()) {
            $rewriter = new Tuancele_R2_Rewriter();

            // Hook để đổi tên file TRƯỚC KHI lưu và TRƯỚC KHI offload
            add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_file_on_upload' ], 10, 1 );

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
        
        // [THÊM MỚI] Kích hoạt các hook cho Công cụ Migration
        // Chúng ta cần đăng ký các hook này ngay cả khi R2 bị tắt,
        // để người dùng có thể truy cập trang và bắt đầu quá trình (nếu họ muốn).
        if (is_admin()) {
            // Khởi tạo lớp Migration và truyền đối tượng $actions vào
            $migration = new Tuancele_R2_Migration($actions);
            
            // Đăng ký 3 hook AJAX mà file admin-r2-migration.js đang gọi
            add_action('wp_ajax_tuancele_r2_start_migration', [$migration, 'ajax_start_migration']);
            add_action('wp_ajax_tuancele_r2_cancel_migration', [$migration, 'ajax_cancel_migration']);
            add_action('wp_ajax_tuancele_r2_get_migration_status', [$migration, 'ajax_get_status']);
            
            // Đăng ký hook WP-Cron để chạy từng batch
            add_action('tuancele_r2_run_migration_batch', [$migration, 'process_batch']);
        }
    }
    
    public function handle_settings_update($old_value, $new_value) {
        if (!isset($new_value['enable_r2']) || $new_value['enable_r2'] !== 'on') {
            update_option('tuancele_r2_connection_status', ['success' => true, 'message' => 'Đã tắt.']);
            return;
        }
        $status = Tuancele_R2_Client::test_connection($new_value);
        update_option('tuancele_r2_connection_status', $status);
    }
/**
     * [MỚI] Tự động đổi tên file ảnh khi upload theo định dạng chuẩn.
     * Chạy trước khi file được lưu vào thư mục uploads.
     *
     * @param array $file Mảng thông tin file upload.
     * @return array Mảng thông tin file đã được sửa đổi.
     */
    public function rename_file_on_upload( $file ) {
        // Lấy thông tin file
        $file_info = pathinfo( $file['name'] );
        
        // Lấy phần mở rộng (extension) một cách an toàn
        $extension = isset( $file_info['extension'] ) ? strtolower( $file_info['extension'] ) : '';
        
        // Xử lý cả file ảnh, video và tài liệu
        $allowed_extensions = [ 
            'jpg', 'jpeg', 'png', 'gif', 'webp', // Ảnh
            'mp4', 'mov', 'avi', 'wmv', // Video
            'mp3', 'wav', // Âm thanh
            'pdf' // Tài liệu
        ];

        if ( in_array( $extension, $allowed_extensions ) ) {
            
            // 1. Tạo chuỗi Ngày-Giờ-Phút-Giây
            try {
                // Sử dụng múi giờ của WordPress
                $datetime = new DateTime( 'now', wp_timezone() );
                $date_str = $datetime->format( 'dmY' ); // 07112025
                $time_str = $datetime->format( 'His' ); // 192658
            } catch ( Exception $e ) {
                // Fallback nếu có lỗi timezone
                $date_str = date( 'dmY' );
                $time_str = date( 'His' );
            }

            // 2. Tạo chuỗi ngẫu nhiên 6 ký tự
            $random_str = strtolower( substr( wp_generate_password( 12, false ), 0, 6 ) );

            // 3. Tạo tên file mới: 07112025-192658-abcdef.jpg
            $new_name = $date_str . '-' . $time_str . '-' . $random_str . '.' . $extension;
            
            // 4. Gán tên mới cho file
            $file['name'] = $new_name;
        }
        
        // Trả về file đã sửa đổi (hoặc file gốc nếu không phải ảnh)
        return $file;
    }

}