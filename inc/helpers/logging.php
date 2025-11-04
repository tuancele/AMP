<?php
/**
 * inc/helpers/logging.php
 *
 * Chứa tất cả logic liên quan đến việc ghi log IP, tra cứu IP và xoay vòng log.
 * Tệp này là một phần của quá trình tái cấu trúc từ template-helpers.php.
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
 * Tra cứu vị trí đơn giản từ IP (sử dụng cho log).
 */
function get_visitor_location_from_ip($ip) {
    if (in_array($ip, ['127.0.0.1', '::1', 'Invalid IP'])) return 'Localhost';
    $cache_key = 'ip_location_' . md5($ip);
    if (false !== ($cached_location = get_transient($cache_key))) return $cached_location;
    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,message,country,city");
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return 'N/A';
    $data = json_decode(wp_remote_retrieve_body($response));
    if ($data && $data->status === 'success') {
        $location = trim($data->city . ', ' . $data->country, ', ');
        set_transient($cache_key, $location, DAY_IN_SECONDS);
        return $location;
    }
    return 'N/A';
}

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
 * [ASYNC] Hàm này được gọi bởi WP-Cron để ghi log IP trong nền.
 */
function tuancele_log_visitor_data_async($log_data) {
    // Lấy vị trí từ IP (có thể vẫn dùng cache nếu có)
    $location = get_visitor_location_from_ip($log_data['ip']);

    // Ghi vào file
    $upload_dir = wp_get_upload_dir();
    $log_file = $upload_dir['basedir'] . '/ip_log-' . date('Y-m-d') . '.txt';
    $log_entry = $log_data['time'] . "|" . $log_data['ip'] . "|" . sanitize_text_field($location) . "|" . $log_data['uri'] . "\n";

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
 * Tra cứu thông tin chi tiết của IP (dùng cho trang my-ip.php)
 */
function get_ip_info_from_api($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return [];
    $cache_key = 'ip_info_' . md5($ip);
    if (false !== ($cached_data = get_transient($cache_key))) return $cached_data;
    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,timezone,continent,isp,org,as,reverse");
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return [];
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($data && $data['status'] === 'success') {
        $ip_details = [ 'hostname' => $data['reverse']??'N/A', 'isp' => $data['isp']??'N/A', 'org' => $data['as']??'N/A', 'country' => $data['country']??'N/A', 'countryCode' => $data['countryCode']??'N/A', 'region' => $data['regionName']??'N/A', 'timezone' => str_replace('_', ' ', $data['timezone']??'N/A'), 'continent' => $data['continent']??'N/A' ];
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