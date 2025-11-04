<?php
/**
 * inc/helpers/logging.php
 *
 * Chứa tất cả logic liên quan đến việc ghi log IP, tra cứu IP và xoay vòng log.
 * PHIÊN BẢN 2.0: Tối ưu API - Tra cứu thông tin chi tiết trong hàm async.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// LOGGING, IP TOOLS & LOG ROTATION
// =========================================================================

/**
 * Lấy địa chỉ IP chính xác của người dùng.
 */
function get_the_user_ip() {
    $ip = 'Unknown';
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) $ip = $_SERVER['HTTP_CLIENT_IP'];
    elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) $ip = $_SERVER['REMOTE_ADDR'];
    if ( strpos( $ip, ',' ) !== false ) $ip = trim( explode( ',', $ip )[0] );
    return filter_var($ip, FILTER_VALIDATE_IP) ?: 'Invalid IP';
}

/**
 * [ĐÃ XÓA] Hàm get_visitor_location_from_ip() đã bị loại bỏ 
 * vì hàm tuancele_log_visitor_data_async() giờ đây sẽ gọi thẳng
 * hàm get_ip_info_from_api() để lấy dữ liệu đầy đủ.
 */

/**
 * [OPTIMIZED] Lên lịch ghi log IP thay vì thực hiện đồng bộ.
 */
function log_visitor_data() {
    if (is_user_logged_in() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])) || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    // Thu thập dữ liệu cơ bản (rất nhanh)
    $log_data = [
        'time' => time(),
        'ip'   => get_the_user_ip(),
        'uri'  => esc_url_raw($_SERVER['REQUEST_URI'] ?? '/')
    ];

    // Lên lịch cho một tác vụ chạy nền ngay lập tức
    if (!wp_next_scheduled('tuancele_async_log_visitor', array($log_data))) {
        wp_schedule_single_event(time(), 'tuancele_async_log_visitor', array($log_data));
    }
}
add_action('template_redirect', 'log_visitor_data');

/**
 * [NÂNG CẤP] Hàm này được gọi bởi WP-Cron để ghi log IP trong nền.
 * Giờ đây nó sẽ gọi API tra cứu đầy đủ và lưu 7 trường dữ liệu.
 */
function tuancele_log_visitor_data_async($log_data) {
    // 1. Gọi hàm tra cứu API đầy đủ
    $ip_details = get_ip_info_from_api($log_data['ip']);

    // 2. Trích xuất và làm sạch dữ liệu
    // Ưu tiên 'N/A' nếu rỗng và thay thế dấu '|' để tránh vỡ cấu trúc log
    $location = trim(($ip_details['city'] ?? 'N/A') . ', ' . ($ip_details['country'] ?? 'N/A'), ', ');
    $location = str_replace('|', '-', sanitize_text_field($location));
    $isp = str_replace('|', '-', sanitize_text_field($ip_details['isp'] ?? 'N/A'));
    $org = str_replace('|', '-', sanitize_text_field($ip_details['org'] ?? 'N/A'));
    $countryCode = str_replace('|', '-', sanitize_text_field($ip_details['countryCode'] ?? 'N/A'));
    $uri = str_replace('|', '-', $log_data['uri']); // URI đã được sanitize_url từ trước

    // 3. Ghi vào file với định dạng log MỚI (7 trường)
    $upload_dir = wp_get_upload_dir();
    $log_file = $upload_dir['basedir'] . '/ip_log-' . date('Y-m-d') . '.txt';
    
    // Định dạng mới: Time|IP|Location|ISP|Org|CountryCode|URI
    $log_entry = $log_data['time'] . "|" . $log_data['ip'] . "|" . $location . "|" . $isp . "|" . $org . "|" . $countryCode . "|" . $uri . "\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
add_action('tuancele_async_log_visitor', 'tuancele_log_visitor_data_async', 10, 1);

/**
 * Thực thi việc xoay vòng (nén và xóa) log cũ.
 */
function tuancele_perform_log_rotation() {
    $upload_dir = wp_get_upload_dir(); $log_dir = $upload_dir['basedir'] . '/';
    $log_file_to_archive = $log_dir . 'ip_log-' . date('Y-m-d', strtotime('-3 days')) . '.txt';
    if (!file_exists($log_file_to_archive)) return;
    $archive_dir = $upload_dir['basedir'] . '/ip_logs_archive/';
    if (!file_exists($archive_dir)) wp_mkdir_p($archive_dir);
    $archive_file_path = $archive_dir . 'ip_log-' . date('Y-m-d', strtotime('-3 days')) . '.txt.gz';
    if ($gz = gzopen($archive_file_path, 'w9')) { gzwrite($gz, file_get_contents($log_file_to_archive)); gzclose($gz); unlink($log_file_to_archive); }
}
// Đăng ký lịch chạy hằng ngày
if (!wp_next_scheduled('tuancele_daily_log_rotation_event')) { wp_schedule_event(strtotime('02:00:00'), 'daily', 'tuancele_daily_log_rotation_event'); }
add_action('tuancele_daily_log_rotation_event', 'tuancele_perform_log_rotation');

/**
 * Tra cứu thông tin chi tiết của IP (vẫn được giữ lại cho my-ip.php và hàm async)
 */
function get_ip_info_from_api($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return [];
    $cache_key = 'ip_info_' . md5($ip);
    if (false !== ($cached_data = get_transient($cache_key))) return $cached_data;
    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,timezone,continent,isp,org,as,reverse");
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return [];
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($data && $data['status'] === 'success') {
        $ip_details = [ 
            'hostname' => $data['reverse']??'N/A', 
            'isp' => $data['isp']??'N/A', 
            'org' => $data['as']??'N/A', 
            'country' => $data['country']??'N/A', 
            'countryCode' => $data['countryCode']??'N/A', 
            'region' => $data['regionName']??'N/A', 
            'city' => $data['city']??'N/A', // Thêm city để xây dựng location
            'timezone' => str_replace('_', ' ', $data['timezone']??'N/A'), 
            'continent' => $data['continent']??'N/A' 
        ];
        set_transient($cache_key, $ip_details, HOUR_IN_SECONDS);
        return $ip_details;
    }
    return [];
}

/**
 * Hiển thị thanh debug trạng thái ghi log ở footer (chỉ cho admin).
 */
function add_logging_debugger() {
    if (current_user_can('manage_options')) {
        $log_dir = wp_get_upload_dir()['basedir'];
        $writable = is_writable($log_dir);
        echo "<div style='position: fixed; bottom: 0; left: 0; width: 100%; background: #222; color: white; padding: 10px; z-index: 99999; font-family: monospace; font-size: 12px; text-align: center;'><strong>[DEBUGGER - ADMIN ONLY]</strong> Thư mục log: <code>" . esc_html($log_dir) . "</code> | Trạng thái: <strong style='color: " . ($writable ? 'green' : 'red') . ";'>" . ($writable ? 'CÓ THỂ GHI' : 'KHÔNG THỂ GHI') . "</strong></div>";
    }
}
add_action('wp_footer', 'add_logging_debugger');