<?php
// File: inc/r2/class-r2-migration.php (PHIÊN BẢN SỬA LỖI DỨT ĐIỂM)

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Tuancele_R2_Migration {

    const STATUS_OPTION = 'tuancele_r2_migration_status';
    const QUEUE_TRANSIENT = 'tuancele_r2_migration_queue';
    const BATCH_SIZE = 5;
    const NONCE_ACTION = 'r2_migration_nonce'; // Định nghĩa action của nonce một lần duy nhất

    private $r2_actions;

    public function __construct(Tuancele_R2_Actions $r2_actions) {
        $this->r2_actions = $r2_actions;
    }

    /**
     * Hàm kiểm tra Nonce tùy chỉnh để cung cấp thông báo lỗi chi tiết.
     */
    private function verify_nonce() {
        $nonce_value = $_POST['_wpnonce'] ?? '';

        if ( ! wp_verify_nonce( $nonce_value, self::NONCE_ACTION ) ) {
            // Ghi log lỗi để có thể kiểm tra trên server nếu cần
            error_log('R2 Migration - Nonce Verification Failed. Received Nonce: ' . $nonce_value);
            // Trả về lỗi JSON chi tiết cho trình duyệt
            wp_send_json_error( [
                'message' => 'Lỗi bảo mật: Xác thực không thành công. Vui lòng tải lại trang (Hard Refresh: Ctrl+Shift+R) và thử lại.',
            ], 403 ); // 403 Forbidden là mã lỗi chính xác hơn cho trường hợp này
        }
    }

    public function ajax_start_migration() {
        $this->verify_nonce();

        $query = new WP_Query([
            'post_type'      => 'attachment', 'post_status'    => 'inherit', 'posts_per_page' => -1, 'fields'         => 'ids',
            'meta_query'     => [['key' => '_tuancele_r2_offloaded', 'compare' => 'NOT EXISTS']],
        ]);
        $attachment_ids = $query->posts;

        if (empty($attachment_ids)) {
            wp_send_json_error(['message' => 'Không tìm thấy tệp nào cần di chuyển.']);
        }

        set_transient(self::QUEUE_TRANSIENT, $attachment_ids, DAY_IN_SECONDS);
        update_option(self::STATUS_OPTION, ['running' => true, 'total' => count($attachment_ids), 'processed' => 0]);

        wp_schedule_single_event(time(), 'tuancele_r2_run_migration_batch');
        spawn_cron();
        wp_send_json_success();
    }

    public function process_batch() {
        $status = get_option(self::STATUS_OPTION, []);
        if (empty($status['running'])) { return; }

        $queue = get_transient(self::QUEUE_TRANSIENT);
        if ($queue === false) {
            update_option(self::STATUS_OPTION, array_merge($status, ['running' => false]));
            wp_clear_scheduled_hook('tuancele_r2_run_migration_batch');
            return;
        }

        $batch = array_splice($queue, 0, self::BATCH_SIZE);
        foreach ($batch as $attachment_id) {
            $this->r2_actions->offload_attachment($attachment_id);
        }

        $status['processed'] += count($batch);
        update_option(self::STATUS_OPTION, $status);
        set_transient(self::QUEUE_TRANSIENT, $queue, DAY_IN_SECONDS);

        if (!empty($queue)) {
            wp_schedule_single_event(time() + 2, 'tuancele_r2_run_migration_batch');
            spawn_cron();
        } else {
            update_option(self::STATUS_OPTION, array_merge($status, ['running' => false]));
        }
    }

    public function ajax_cancel_migration() {
        $this->verify_nonce();
        
        wp_clear_scheduled_hook('tuancele_r2_run_migration_batch');
        delete_transient(self::QUEUE_TRANSIENT);
        $status = get_option(self::STATUS_OPTION, []);
        update_option(self::STATUS_OPTION, array_merge($status, ['running' => false]));
        wp_send_json_success();
    }

    public function ajax_get_status() {
        $this->verify_nonce();
        
        $status = get_option(self::STATUS_OPTION, ['running' => false, 'total' => 0, 'processed' => 0]);
        wp_send_json_success($status);
    }
}