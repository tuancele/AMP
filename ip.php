<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * [TỐI ƯU BẢO MẬT]
 * Chỉ cho phép Quản trị viên (Admin) đã đăng nhập xem trang này.
 */
if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) {
    wp_redirect( home_url('/') );
    exit;
}
?>
<?php
/**
 * Template Name: Trang Log IP
 * Description: Bảng điều khiển so sánh truy cập.
 *
 * [NÂNG CẤP V5.5 - BÁO CÁO 10 NGÀY]
 * - Thay đổi truy vấn để lấy dữ liệu tổng hợp 10 ngày gần nhất.
 *
 * [NÂNG CẤP V5.6 - BÁO CÁO UA & REFERER]
 * - Thêm truy vấn và bảng thống kê cho User-Agent và Referer.
 * - Cập nhật Bảng Log Chi Tiết để hiển thị 2 cột mới.
 */

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'visitor_logs';
$start_date = date('Y-m-d H:i:s', strtotime('-10 days'));
$home_url_like = '%' . esc_url( home_url('/') ) . '%'; // Chuỗi để lọc referer nội bộ

// --- BẮT ĐẦU KHỐI LOGIC PHÂN TÍCH (ĐÃ NÂNG CẤP) ---

/**
 * Hàm helper để render một card thống kê Top IP.
 */
function render_stats_card($stats_data) {
    ?>
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0;">Tổng Lượt Truy Cập</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 0; color: #0056b3;"><?php echo number_format_i18n($stats_data['total_views']); ?></p>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0;">Số IP Duy Nhất</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 0; color: #17a2b8;"><?php echo number_format_i18n($stats_data['unique_ips_count']); ?></p>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); grid-column: 1 / -1;">
             <h4 style="margin-top: 0;">Top 10 IP truy cập nhiều nhất</h4>
             <div class="ip-details-list" style="font-size: 14px; line-height: 1.6;">
                <?php 
                $top_ips = $stats_data['ips'];
                if (empty($top_ips)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_ips as $data) {
                        $country_flag = !empty($data['country_code']) && $data['country_code'] !== 'N/A'
                            ? '<img src="https://flagcdn.com/16x12/' . strtolower(esc_attr($data['country_code'])) . '.png" alt="' . esc_attr($data['location']) . '" style="margin-right: 8px; vertical-align: middle;">' 
                            : '';
                        $isp_info = !empty($data['org']) && $data['org'] !== 'N/A' ? esc_html($data['org']) : esc_html($data['isp']);
                        echo '<div class="ip-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0;">' .
                             '  <div style="font-weight: bold;">' . esc_html($data['ip_address']) . '</div>' .
                             '  <div style="color: #555; text-align: right;">' . $country_flag . $isp_info . ' <strong style="color: #dc3545; margin-left:10px;">' . number_format_i18n($data['count']) . '</strong></div>' .
                             '</div>';
                    }
                } ?>
             </div>
        </div>
    </div>
    <?php
}

/**
 * Hàm helper để render một card thống kê Top URL.
 */
function render_url_stats_card($stats_data) {
    ?>
    <div class="stats-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); grid-column: 1 / -1;">
             <h4 style="margin-top: 0;">Top 10 URL được truy cập nhiều nhất</h4>
             <div class="url-details-list" style="font-size: 14px; line-height: 1.6;">
                <?php 
                $top_urls = $stats_data['urls'];
                if (empty($top_urls)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_urls as $data) {
                        $is_suspicious = ( strpos($data['request_uri'], 'wp-login.php') !== false || strpos($data['request_uri'], 'xmlrpc.php') !== false );
                        $url_style = $is_suspicious ? 'font-weight: bold; color: #dc3545;' : 'font-weight: bold;';
                        echo '<div class="url-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0; word-break: break-all;">' .
                             '  <div style="' . $url_style . '">' . esc_html(urldecode($data['request_uri'])) . '</div>' .
                             '  <div style="color: #555; text-align: right; padding-left: 15px; white-space: nowrap;">' .
                             '    <strong style="color: #dc3545;">' . number_format_i18n($data['count']) . '</strong> lượt' .
                             '  </div>' .
                             '</div>';
                    }
                } ?>
             </div>
        </div>
    </div>
    <?php
}

/**
 * [NÂNG CẤP V5.6] Hàm helper để render card thống kê Top User Agent.
 */
function render_user_agent_stats_card($stats_data) {
    ?>
    <div class="stats-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); grid-column: 1 / -1;">
             <h4 style="margin-top: 0;">Top 10 User Agent (Trình duyệt/Bot)</h4>
             <div class="ua-details-list" style="font-size: 14px; line-height: 1.6;">
                <?php 
                $top_uas = $stats_data['user_agents'];
                if (empty($top_uas)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_uas as $data) {
                        $is_bot = ( preg_match('/bot|crawl|slurp|spider|python|curl|wget/i', $data['user_agent']) );
                        $ua_style = $is_bot ? 'font-weight: bold; color: #dc3545;' : '';
                        echo '<div class="ua-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0; word-break: break-all;">' .
                             '  <div style="' . $ua_style . '">' . esc_html($data['user_agent']) . '</div>' .
                             '  <div style="color: #555; text-align: right; padding-left: 15px; white-space: nowrap;">' .
                             '    <strong style="color: #dc3545;">' . number_format_i18n($data['count']) . '</strong> lượt' .
                             '  </div>' .
                             '</div>';
                    }
                } ?>
             </div>
        </div>
    </div>
    <?php
}

/**
 * [NÂNG CẤP V5.6] Hàm helper để render card thống kê Top Referer.
 */
function render_referer_stats_card($stats_data) {
    ?>
    <div class="stats-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); grid-column: 1 / -1;">
             <h4 style="margin-top: 0;">Top 10 Nguồn truy cập (Referer)</h4>
             <div class="referer-details-list" style="font-size: 14px; line-height: 1.6;">
                <?php 
                $top_refs = $stats_data['referers'];
                if (empty($top_refs)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_refs as $data) {
                        $referer_host = parse_url($data['referer'], PHP_URL_HOST) ?: $data['referer'];
                        echo '<div class="referer-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0; word-break: break-all;">' .
                             '  <div><a href="' . esc_url($data['referer']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($referer_host) . '</a></div>' .
                             '  <div style="color: #555; text-align: right; padding-left: 15px; white-space: nowrap;">' .
                             '    <strong style="color: #dc3545;">' . number_format_i18n($data['count']) . '</strong> lượt' .
                             '  </div>' .
                             '</div>';
                    }
                } ?>
             </div>
        </div>
    </div>
    <?php
}


// --- [NÂNG CẤP V5.6] Lấy dữ liệu thống kê tổng hợp 10 NGÀY QUA ---
$stats_last_10_days = [
    'total_views' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE visit_time >= %s", $start_date)
    ),
    'unique_ips_count' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE visit_time >= %s", $start_date)
    ),
    'ips' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ip_address, location, isp, org, country_code, COUNT(id) as count 
             FROM $table_name 
             WHERE visit_time >= %s 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 10", 
            $start_date
        ), ARRAY_A
    ),
    'urls' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT request_uri, COUNT(id) as count 
             FROM $table_name 
             WHERE visit_time >= %s 
             GROUP BY request_uri 
             ORDER BY count DESC 
             LIMIT 10", 
            $start_date
        ), ARRAY_A
    ),
    'user_agents' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_agent, COUNT(id) as count 
             FROM $table_name 
             WHERE visit_time >= %s 
             GROUP BY user_agent 
             ORDER BY count DESC 
             LIMIT 10", 
            $start_date
        ), ARRAY_A
    ),
    'referers' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT referer, COUNT(id) as count 
             FROM $table_name 
             WHERE visit_time >= %s AND referer != 'N/A' AND referer NOT LIKE %s
             GROUP BY referer 
             ORDER BY count DESC 
             LIMIT 10", 
            $start_date,
            $home_url_like
        ), ARRAY_A
    ),
];

// --- [NÂNG CẤP V5.6] Lấy 200 dòng log gần đây nhất (trong 10 ngày qua) ---
$recent_logs_10_days = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE visit_time >= %s 
         ORDER BY id DESC 
         LIMIT 200",
        $start_date
    )
);

// --- KẾT THÚC KHỐI LOGIC ---
?>

<div class="log-page-container">
    <h1>Bảng điều khiển Phân tích Truy cập (10 Ngày Gần Nhất)</h1>
    <p>Dọn dẹp log: Tự động xóa log cũ hơn 10 ngày (được định nghĩa trong `inc/helpers/logging.php`).</p>
    
    <hr style="margin: 30px 0;">

    <div class="daily-report">
        <h2>Báo cáo tổng hợp (10 ngày gần nhất)</h2>
        <?php 
        render_stats_card($stats_last_10_days); // Thống kê Top IP
        render_url_stats_card($stats_last_10_days); // Thống kê Top URL
        render_user_agent_stats_card($stats_last_10_days); // [MỚI] Thống kê Top User Agent
        render_referer_stats_card($stats_last_10_days); // [MỚI] Thống kê Top Referer
        ?>
    </div>
    
    <h2 style="margin-top: 40px;">Nhật ký <?php echo count($recent_logs_10_days); ?> truy cập gần đây nhất (10 ngày qua)</h2>
    <?php if (!empty($recent_logs_10_days)) : ?>
        <div class="log-table-wrapper">
            <table class="visitor-log-table">
                <thead>
                    <tr>
                        <th>Thời Gian</th>
                        <th>Địa chỉ IP</th>
                        <th>Url Truy Cập</th>
                        <th>User Agent</th>
                        <th>Referer</th>
                        <th>Vị trí</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs_10_days as $row) : ?>
                        <?php $time = strtotime($row->visit_time); ?>
                        <tr>
                            <td data-label="Thời Gian"><?php echo date('H:i:s d/m', $time); ?></td>
                            <td data-label="Địa chỉ IP"><?php echo esc_html($row->ip_address); ?></td>
                            <td data-label="Url Truy Cập" style="word-break: break-all;"><?php echo esc_html(urldecode($row->request_uri)); ?></td>
                            <td data-label="User Agent" style="font-size: 12px; color: #555; word-break: break-all;"><?php echo esc_html($row->user_agent); ?></td>
                            <td data-label="Referer" style="font-size: 12px; color: #555; word-break: break-all;"><?php echo esc_html(urldecode($row->referer)); ?></td>
                            <td data-label="Vị trí"><?php echo esc_html($row->location); ?> (<?php echo esc_html($row->country_code); ?>)</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>Chưa có dữ liệu truy cập nào được ghi lại trong 10 ngày qua.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>