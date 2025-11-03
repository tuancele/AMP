<?php
/**
 * Template Name: Trang Log IP
 * Description: Bảng điều khiển so sánh truy cập giữa hôm nay và hôm qua.
 *
 * PHIÊN BẢN COMPARISON DASHBOARD v3.0:
 * - Phân tích và hiển thị báo cáo cho cả ngày hôm nay và ngày hôm qua.
 * - Đọc dữ liệu từ các file log được đặt tên theo ngày.
 */

get_header();

// --- BẮT ĐẦU KHỐI LOGIC PHÂN TÍCH ---

// Hàm helper để phân tích một file log và trả về mảng thống kê
function analyze_log_file($file_path) {
    $stats = [
        'total_views'      => 0,
        'unique_ips_count' => 0,
        'ips'              => [],
        'urls'             => [],
        'log_data'         => [], // Dành cho log hôm nay
    ];

    if (!file_exists($file_path)) {
        return $stats;
    }

    $raw_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (is_array($raw_lines) && !empty($raw_lines)) {
        $stats['total_views'] = count($raw_lines);
        
        // Lấy các dòng cuối để hiển thị (chỉ cần cho log hôm nay)
        $reversed_lines = array_reverse($raw_lines);
        $stats['log_data'] = array_slice($reversed_lines, 0, 200);

        foreach ($raw_lines as $line) {
            $parts = explode('|', $line, 4);
            if (count($parts) < 4) continue;
            
            $ip = $parts[1];
            $url = $parts[3];

            $stats['ips'][$ip] = ($stats['ips'][$ip] ?? 0) + 1;
            $stats['urls'][$url] = ($stats['urls'][$url] ?? 0) + 1;
        }
        
        $stats['unique_ips_count'] = count($stats['ips']);
        arsort($stats['ips']);
        arsort($stats['urls']);
    }
    return $stats;
}

// Hàm helper để render một card thống kê
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
                $top_ips = array_slice($stats_data['ips'], 0, 10);
                if (empty($top_ips)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_ips as $ip => $count) {
                        $ip_info = function_exists('get_ip_info_from_api') ? get_ip_info_from_api($ip) : [];
                        $country_flag = !empty($ip_info['countryCode']) ? '<img src="https://flagcdn.com/16x12/' . strtolower(esc_attr($ip_info['countryCode'])) . '.png" alt="' . esc_attr($ip_info['country']) . '" style="margin-right: 8px; vertical-align: middle;">' : '';
                        $isp_info = !empty($ip_info['org']) ? esc_html($ip_info['org']) : 'N/A';
                        echo '<div class="ip-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0;">' .
                             '  <div style="font-weight: bold;">' . esc_html($ip) . '</div>' .
                             '  <div style="color: #555; text-align: right;">' . $country_flag . $isp_info . ' <strong style="color: #dc3545; margin-left:10px;">' . number_format_i18n($count) . '</strong></div>' .
                             '</div>';
                    }
                } ?>
             </div>
        </div>
    </div>
    <?php
}

$upload_dir = wp_get_upload_dir();

// Phân tích log hôm nay
$today_date = date('Y-m-d');
$today_log_file = $upload_dir['basedir'] . '/ip_log-' . $today_date . '.txt';
$stats_today = analyze_log_file($today_log_file);

// Phân tích log hôm qua
$yesterday_date = date('Y-m-d', strtotime('-1 day'));
$yesterday_log_file = $upload_dir['basedir'] . '/ip_log-' . $yesterday_date . '.txt';
$stats_yesterday = analyze_log_file($yesterday_log_file);

// --- KẾT THÚC KHỐI LOGIC ---
?>

<div class="log-page-container">
    <h1>Bảng điều khiển Phân tích Truy cập</h1>
    <p>Lịch trình nén log: File log của một ngày sẽ được nén và lưu trữ sau 3 ngày.</p>
    
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
    
    <h2 style="margin-top: 40px;">Nhật ký <?php echo count($stats_today['log_data']); ?> truy cập gần đây nhất (Hôm nay)</h2>
    <?php if (!empty($stats_today['log_data'])) : ?>
        <div class="log-table-wrapper">
            <table class="visitor-log-table">
                <thead>
                    <tr>
                        <th>STT</th><th>Địa chỉ IP</th><th>Vị trí (Ước tính)</th><th>Url Truy Cập</th><th>Thời Gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_today['log_data'] as $index => $line) : ?>
                        <?php $parts = explode('|', $line, 4); if (count($parts) < 4) continue; ?>
                        <tr>
                            <td data-label="STT"><?php echo $index + 1; ?></td>
                            <td data-label="Địa chỉ IP"><?php echo esc_html($parts[1]); ?></td>
                            <td data-label="Vị trí (Ước tính)"><?php echo esc_html($parts[2]) ?: 'Không xác định'; ?></td>
                            <td data-label="Url Truy Cập"><?php echo esc_html($parts[3]); ?></td>
                            <td data-label="Thời Gian"><?php echo date('Y-m-d H:i:s', (int)$parts[0]); ?></td>
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