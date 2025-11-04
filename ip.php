<?php
/**
 * Template Name: Trang Log IP
 * Description: Bảng điều khiển so sánh truy cập giữa hôm nay và hôm qua.
 *
 * PHIÊN BẢN 3.1 (TỐI ƯU API):
 * - Đọc định dạng log 7 trường mới (Time|IP|Location|ISP|Org|CountryCode|URI).
 * - Loại bỏ hoàn toàn việc gọi API (get_ip_info_from_api) khi tải trang.
 * - Trang này giờ đây chỉ "đọc" dữ liệu đã được thu thập sẵn.
 */

get_header();

// --- BẮT ĐẦU KHỐI LOGIC PHÂN TÍCH (ĐÃ NÂNG CẤP) ---

/**
 * Hàm helper để phân tích một file log (định dạng 7 trường) và trả về mảng thống kê.
 */
function analyze_log_file($file_path) {
    $stats = [
        'total_views'      => 0,
        'unique_ips_count' => 0,
        'ips'              => [], // Mảng này sẽ chứa dữ liệu chi tiết
        'urls'             => [],
        'log_data'         => [], // Dành cho log hôm nay (200 dòng cuối)
    ];

    if (!file_exists($file_path)) {
        return $stats;
    }

    $raw_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (is_array($raw_lines) && !empty($raw_lines)) {
        $stats['total_views'] = count($raw_lines);
        
        // Lấy 200 dòng cuối để hiển thị (giữ nguyên logic cũ)
        $reversed_lines = array_reverse($raw_lines);
        $stats['log_data'] = array_slice($reversed_lines, 0, 200);

        // Lặp qua TẤT CẢ các dòng để lấy thống kê
        foreach ($raw_lines as $line) {
            // [NÂNG CẤP] Phân tách thành 7 phần
            $parts = explode('|', $line, 7);
            
            // Nếu là log cũ (ít hơn 7 phần), bỏ qua để tránh lỗi
            if (count($parts) < 7) continue; 

            // [NÂNG CẤP] Lấy dữ liệu đã được thu thập sẵn
            list($time, $ip, $location, $isp, $org, $countryCode, $url) = $parts;

            // Thống kê URL
            $stats['urls'][$url] = ($stats['urls'][$url] ?? 0) + 1;

            // [NÂNG CẤP] Thống kê IP và lưu trữ thông tin chi tiết
            if (!isset($stats['ips'][$ip])) {
                $stats['ips'][$ip] = [
                    'count'       => 0,
                    'location'    => $location,
                    'isp'         => $isp,
                    'org'         => $org,
                    'countryCode' => $countryCode,
                ];
            }
            $stats['ips'][$ip]['count']++;
        }
        
        $stats['unique_ips_count'] = count($stats['ips']);
        
        // Sắp xếp IP theo số lượng (count) giảm dần
        uasort($stats['ips'], function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        // Sắp xếp URL theo số lượng giảm dần
        arsort($stats['urls']);
    }
    return $stats;
}

/**
 * Hàm helper để render một card thống kê (ĐÃ NÂNG CẤP)
 * Sẽ không gọi API tra cứu IP nữa.
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
             <h4 style="margin-top: 0;">Top 10 IP truy cập nhiều nhất (Đã thu thập)</h4>
             <div class="ip-details-list" style="font-size: 14px; line-height: 1.6;">
                <?php 
                $top_ips = array_slice($stats_data['ips'], 0, 10, true); // Giữ nguyên key (IP)
                if (empty($top_ips)) { echo "<p>Chưa có dữ liệu.</p>"; } 
                else {
                    foreach($top_ips as $ip => $data) {
                        // [TỐI ƯU] Lấy dữ liệu trực tiếp từ mảng, không gọi API
                        $country_flag = !empty($data['countryCode']) && $data['countryCode'] !== 'N/A'
                            ? '<img src="https://flagcdn.com/16x12/' . strtolower(esc_attr($data['countryCode'])) . '.png" alt="' . esc_attr($data['location']) . '" style="margin-right: 8px; vertical-align: middle;">' 
                            : '';
                        
                        // Ưu tiên hiển thị Org (ASxxx), nếu không có thì dùng ISP
                        $isp_info = !empty($data['org']) && $data['org'] !== 'N/A' 
                            ? esc_html($data['org']) 
                            : esc_html($data['isp']);
                        
                        echo '<div class="ip-item" style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0;">' .
                             '  <div style="font-weight: bold;">' . esc_html($ip) . '</div>' .
                             '  <div style="color: #555; text-align: right;">' . $country_flag . $isp_info . ' <strong style="color: #dc3545; margin-left:10px;">' . number_format_i18n($data['count']) . '</strong></div>' .
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
                    <?php foreach ($stats_today['log_data'] as $index => $line) : ?>
                        <?php 
                        // [NÂNG CẤP] Phân tách 7 trường
                        $parts = explode('|', $line, 7);
                        // Xử lý an toàn nếu gặp phải log cũ (ít hơn 7 trường)
                        if (count($parts) < 7) {
                            $parts = array_pad($parts, 7, 'N/A'); // Đệm 'N/A' vào các trường thiếu
                        }
                        list($time, $ip, $location, $isp, $org, $countryCode, $uri) = $parts;
                        ?>
                        <tr>
                            <td data-label="STT"><?php echo $index + 1; ?></td>
                            <td data-label="Thời Gian"><?php echo date('H:i:s', (int)$time); ?></td>
                            <td data-label="Địa chỉ IP"><?php echo esc_html($ip); ?></td>
                            <td data-label="Vị trí"><?php echo esc_html($location); ?></td>
                            <td data-label="ISP / Tổ chức"><?php echo esc_html($org !== 'N/A' ? $org : $isp); ?></td>
                            <td data-label="QG"><?php echo esc_html($countryCode); ?></td>
                            <td data-label="Url Truy Cập"><?php echo esc_html($uri); ?></td>
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