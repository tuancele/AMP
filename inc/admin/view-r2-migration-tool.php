<?php
/**
 * View: Hiển thị công cụ R2 Migration.
 * (Tách ra từ admin-settings-module.php)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$status = get_option('tuancele_r2_migration_status', ['running' => false, 'total' => 0, 'processed' => 0]);
$is_running = $status['running'];

$local_query = new WP_Query([
    'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids',
    'meta_query' => [['key' => '_tuancele_r2_offloaded', 'compare' => 'NOT EXISTS']]
]);
$local_count = $local_query->post_count;
?>

<div id="r2-migration-tool">
    <div id="r2-migration-status"></div>
    <div id="r2-progress-bar-container"><div id="r2-progress-bar">0%</div></div>
    <p style="margin-top:15px">
        <button type="button" class="button button-primary" id="start-r2-migration" <?php if ($is_running || $local_count === 0) echo 'disabled'; ?>>Bắt đầu Di chuyển <?php echo $local_count; ?> tệp</button>
        <button type="button" class="button" id="cancel-r2-migration" <?php if (!$is_running) echo 'disabled'; ?>>Hủy bỏ</button>
        <button type="button" class="button" id="recheck-r2-migration" style="margin-left: 15px;" <?php if ($is_running) echo 'disabled'; ?>><?php echo $is_running ? 'Đang chạy...' : 'Kiểm tra lại'; ?></button>
    </p>
</div>