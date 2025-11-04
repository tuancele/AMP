<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * Template Name: Trang Log IP
 * Description: Bảng điều khiển so sánh truy cập giữa hôm nay và hôm qua.
 *
 * PHIÊN BẢN 4.0 (Phương án 1 - Tối ưu CSDL):
 * - Đọc dữ liệu trực tiếp từ bảng CSDL 'wp_visitor_logs' thay vì .txt.
 * - Loại bỏ hoàn toàn hàm analyze_log_file() và các thao tác I/O trên tệp.
 * - Sử dụng các truy vấn SQL (COUNT, GROUP BY) để lấy thống kê.
 * - Tốc độ tải trang gần như tức thì, bất kể số lượng log.
 */

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'visitor_logs';
$today_date = current_time('Y-m-d');
$yesterday_date = date('Y-m-d', strtotime('-1 day'));

// --- BẮT ĐẦU KHỐI LOGIC PHÂN TÍCH (ĐÃ NÂNG CẤP) ---

/**
 * [NÂNG CẤP v4.0] Hàm helper để render một card thống kê.
 * Hàm này giờ đây nhận dữ liệu trực tiếp từ truy vấn CSDL (ARRAY_A).
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
                $top_ips = $stats_data['ips']; // Đây là kết quả từ $wpdb->get_results(..., ARRAY_A)
                if (empty($top_ips)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_ips as $data) {
                        $country_flag = !empty($data['country_code']) && $data['country_code'] !== 'N/A'
                            ? '<img src="https://flagcdn.com/16x12/' . strtolower(esc_attr($data['country_code'])) . '.png" alt="' . esc_attr($data['location']) . '" style="margin-right: 8px; vertical-align: middle;">' 
                            : '';
                        
                        $isp_info = !empty($data['org']) && $data['org'] !== 'N/A' 
                            ? esc_html($data['org']) 
                            : esc_html($data['isp']);
                        
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

// --- Lấy dữ liệu thống kê cho HÔM QUA ---
$stats_yesterday = [
    'total_views' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE DATE(visit_time) = %s", $yesterday_date)
    ),
    'unique_ips_count' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE DATE(visit_time) = %s", $yesterday_date)
    ),
    'ips' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ip_address, location, isp, org, country_code, COUNT(id) as count 
             FROM $table_name 
             WHERE DATE(visit_time) = %s 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 10", 
            $yesterday_date
        ), ARRAY_A
    ),
];

// --- Lấy dữ liệu thống kê cho HÔM NAY ---
$stats_today = [
    'total_views' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE DATE(visit_time) = %s", $today_date)
    ),
    'unique_ips_count' => (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(DISTINCT ip_address) FROM $table_name WHERE DATE(visit_time) = %s", $today_date)
    ),
    'ips' => $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ip_address, location, isp, org, country_code, COUNT(id) as count 
             FROM $table_name 
             WHERE DATE(visit_time) = %s 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 10", 
            $today_date
        ), ARRAY_A
    ),
];

// --- Lấy 200 dòng log gần đây nhất cho HÔM NAY ---
$today_recent_logs = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE DATE(visit_time) = %s 
         ORDER BY id DESC 
         LIMIT 200",
        $today_date
    )
);

// --- KẾT THÚC KHỐI LOGIC ---
?>

<div class="log-page-container">
    <h1>Bảng điều khiển Phân tích Truy cập (v4.0 - DB)</h1>
    <p>Dọn dẹp log: Tự động xóa log cũ hơn 7 ngày (được định nghĩa trong `inc/helpers/logging.php`).</p>
    
    <hr style="margin: 30px 0;">

    <div class="daily-report" style="margin-bottom: 40px;">
        <h2>Báo cáo hôm qua (<?php echo esc_html($yesterday_date); ?>)</h2>
        <?php render_stats_card($stats_yesterday); ?>
    </div>

    <hr style="margin: 30px 0;">

    <div class="daily-report">
        <h2>Báo cáo hôm nay (<?php echo esc_html($today_date); ?>)</h2>
        <?php render_stats_card($stats_today); ?>
    </div>
    
    <h2 style="margin-top: 40px;">Nhật ký <?php echo count($today_recent_logs); ?> truy cập gần đây nhất (Hôm nay)</h2>
    <?php if (!empty($today_recent_logs)) : ?>
        <div class="log-table-wrapper">
            <table class="visitor-log-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Thời Gian</th>
                        <th>Địa chỉ IP</th>
                        <th>Vị trí</th>
                        <th>ISP / Tổ chức</th>
                        <th>QG</th>
                        <th>Url Truy Cập</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_recent_logs as $index => $row) : ?>
                        <?php
                        // [NÂNG CẤP] Đọc từ đối tượng $row (kết quả của CSDL)
                        $time = strtotime($row->visit_time);
                        $isp_info = !empty($row->org) && $row->org !== 'N/A' ? $row->org : $row->isp;
                        ?>
                        <tr>
                            <td data-label="STT"><?php echo $index + 1; ?></td>
                            <td data-label="Thời Gian"><?php echo date('H:i:s', $time); ?></td>
                            <td data-label="Địa chỉ IP"><?php echo esc_html($row->ip_address); ?></td>
                            <td data-label="Vị trí"><?php echo esc_html($row->location); ?></td>
                            <td data-label="ISP / Tổ chức"><?php echo esc_html($isp_info); ?></td>
                            <td data-label="QG"><?php echo esc_html($row->country_code); ?></td>
                            <td data-label="Url Truy Cập"><?php echo esc_html($row->request_uri); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>Chưa có dữ liệu truy cập nào được ghi lại cho hôm nay.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>