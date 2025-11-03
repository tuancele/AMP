<?php
// File: inc/r2/class-r2-migration.php
// ĐÃ SỬA LỖI: Thay thế spawn_cron() bằng wp_remote_post() để kích hoạt cron job đáng tin cậy hơn.

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Tuancele_R2_Migration {

    const STATUS_OPTION = 'tuancele_r2_migration_status';
    const QUEUE_TRANSIENT = 'tuancele_r2_migration_queue';
    const BATCH_SIZE = 5;
    const NONCE_ACTION = 'r2_migration_nonce';

    private $r2_actions;

    public function __construct(Tuancele_R2_Actions $r2_actions) {
        $this->r2_actions = $r2_actions;
    }

    private function verify_nonce() {
        $nonce_value = $_POST['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce_value, self::NONCE_ACTION ) ) {
            error_log('R2 Migration - Nonce Verification Failed. Received Nonce: ' . $nonce_value);
            wp_send_json_error( [
                'message' => 'Lỗi bảo mật: Xác thực không thành công. Vui lòng tải lại trang (Hard Refresh: Ctrl+Shift+R) và thử lại.',
            ], 403 );
        }
    }

    /**
     * [SỬA LỖI] Kích hoạt WP-Cron bằng một yêu cầu non-blocking
     */
    private function trigger_cron() {
        wp_remote_post(site_url('wp-cron.php?doing_wp_cron'), [
            'timeout'   => 0.01,
            'blocking'  => false,
            'sslverify' => false,
        ]);
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
        
        // [SỬA LỖI] Kích hoạt cron
        $this->trigger_cron();
        
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
            if (method_exists($this->r2_actions, 'offload_attachment')) {
                 $this->r2_actions->offload_attachment($attachment_id);
            }
        }

        $status['processed'] += count($batch);
        update_option(self::STATUS_OPTION, $status);
        set_transient(self::QUEUE_TRANSIENT, $queue, DAY_IN_SECONDS);

        if (!empty($queue)) {
            wp_schedule_single_event(time() + 2, 'tuancele_r2_run_migration_batch');
            
            // [SỬA LỖI] Kích hoạt cron
            $this->trigger_cron();

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
        
        $local_query = new WP_Query([
            'post_type'      => 'attachment', 'post_status'    => 'inherit', 'posts_per_page' => -1, 'fields'         => 'ids',
            'meta_query'     => [['key' => '_tuancele_r2_offloaded', 'compare' => 'NOT EXISTS']],
        ]);
        $local_count = $local_query->post_count;
        
        $status['local_files_remaining'] = $local_count;

        if ($status['running'] === false) {
             $status['total'] = $local_count;
             $status['processed'] = 0;
             update_option(self::STATUS_OPTION, $status);
        }
        
        wp_send_json_success($status);
    }
}