<?php
/**
 * View: Hiển thị công cụ R2 Migration.
 * (ĐÃ BỔ SUNG CSS BỊ THIẾU TỪ FILE GỐC admin-settings-module.php)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ?>
<style>
    #r2-migration-tool{border:1px solid #ccd0d4;padding:20px;background:#fff;border-radius:4px}
    #r2-migration-status{font-weight:700;margin-bottom:15px}
    #r2-progress-bar-container{width:100%;background-color:#e0e0e0;border-radius:4px;overflow:hidden;height:25px;margin-top:15px}
    #r2-progress-bar{width:0;height:100%;background-color:#4caf50;text-align:center;line-height:25px;color:#fff;transition:width .3s ease}
    #r2-migration-tool button{margin-right:10px}
</style>
<?php
// // NỘI DUNG PHP VÀ HTML HIỆN TẠI CỦA BẠN (VẪN GIỮ NGUYÊN)
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