<?php
/**
 * inc/helpers/logging.php
 *
 * Chứa tất cả logic liên quan đến việc ghi log IP, tra cứu IP và xoay vòng log.
 * PHIÊN BẢN 4.0 (Phương án 1): Chuyển sang ghi log vào CSDL tùy chỉnh.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// LOGGING, IP TOOLS & LOG ROTATION
// =========================================================================

/**
 * Lấy địa chỉ IP chính xác của người dùng.
 * (Không thay đổi)
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
 * [OPTIMIZED] Lên lịch ghi log IP thay vì thực hiện đồng bộ.
 * (Không thay đổi)
 */
function log_visitor_data() {
    if (is_user_logged_in() || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])) || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    // Thu thập dữ liệu cơ bản (rất nhanh)
    $log_data = [
        'time' => time(), // Giữ lại time() để tương thích với hook
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
 * [NÂNG CẤP v4.0] Hàm này được gọi bởi WP-Cron để ghi log vào BẢNG CSDL.
 * Thay thế logic file_put_contents() bằng $wpdb->insert().
 */
function tuancele_log_visitor_data_async($log_data) {
    global $wpdb;
    // Tên bảng CSDL tùy chỉnh mà chúng ta đã tạo
    $table_name = $wpdb->prefix . 'visitor_logs'; 

    // 1. Gọi hàm tra cứu API (vẫn giữ nguyên từ Phương án 1)
    $ip_details = get_ip_info_from_api($log_data['ip']);

    // 2. Trích xuất và làm sạch dữ liệu
    $location = trim(($ip_details['city'] ?? 'N/A') . ', ' . ($ip_details['country'] ?? 'N/A'), ', ');
    
    $data_to_insert = [
        'visit_time'   => current_time('mysql'), // Chuyển sang định dạng DATETIME của CSDL
        'ip_address'   => $log_data['ip'],
        'location'     => sanitize_text_field($location),
        'isp'          => sanitize_text_field($ip_details['isp'] ?? 'N/A'),
        'org'          => sanitize_text_field($ip_details['org'] ?? 'N/A'),
        'country_code' => sanitize_text_field($ip_details['countryCode'] ?? 'N/A'),
        'request_uri'  => esc_url_raw($log_data['uri']),
    ];
    
    // 3. Ghi vào CSDL
    $wpdb->insert($table_name, $data_to_insert);
}
add_action('tuancele_async_log_visitor', 'tuancele_log_visitor_data_async', 10, 1);

/**
 * [NÂNG CẤP v4.0] Thực thi việc xoay vòng (xóa) log cũ khỏi CSDL.
 * Thay thế logic nén file .txt bằng lệnh DELETE SQL.
 */
function tuancele_perform_log_rotation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_logs';
    
    // Giữ lại 7 ngày log gần nhất (bạn có thể thay đổi số này)
    $days_to_keep = 7; 
    $delete_before_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
    
    // Chạy lệnh DELETE để xóa các dòng log cũ hơn 7 ngày
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE visit_time < %s",
            $delete_before_date
        )
    );
}
// Đăng ký lịch chạy hằng ngày (không thay đổi)
if (!wp_next_scheduled('tuancele_daily_log_rotation_event')) { wp_schedule_event(strtotime('02:00:00'), 'daily', 'tuancele_daily_log_rotation_event'); }
add_action('tuancele_daily_log_rotation_event', 'tuancele_perform_log_rotation');

/**
 * Tra cứu thông tin chi tiết của IP (vẫn được giữ lại cho my-ip.php và hàm async)
 * (Không thay đổi)
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
        set_transient($cache_key, $ip_details, HOUR_IN_SECONDS); // Cache 1 giờ
        return $ip_details;
    }
    return [];
}

/**
 * [NÂNG CẤP v4.0] Hiển thị thanh debug trạng thái BẢNG CSDL (thay vì file).
 */
function add_logging_debugger() {
    if (current_user_can('manage_options')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visitor_logs';
        // Kiểm tra xem bảng có tồn tại không
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        echo "<div style='position: fixed; bottom: 0; left: 0; width: 100%; background: #222; color: white; padding: 10px; z-index: 99999; font-family: monospace; font-size: 12px; text-align: center;'><strong>[DEBUGGER - ADMIN ONLY]</strong> Bảng Log: <code>" . esc_html($table_name) . "</code> | Trạng thái: <strong style='color: " . ($table_exists ? 'green' : 'red') . ";'>" . ($table_exists ? 'TỒN TẠI' : 'KHÔNG TÌM THẤY (Hãy kích hoạt lại theme)') . "</strong></div>";
    }
}
add_action('wp_footer', 'add_logging_debugger');

// =========================================================================
// [MỚI] MODULE TƯỜNG LỬA IP (GATEKEEPER & DETECTIVE)
// =========================================================================

/**
 * 1. BỘ LỌC CỔNG (THE GATEKEEPER)
 * Chạy đồng bộ, kiểm tra IP của khách truy cập
 * và chặn ngay lập tức nếu có trong danh sách.
 */
function tuancele_ip_blocker_gatekeeper() {
    // Lấy cài đặt (chỉ 1 lần)
    $options = get_option('tuancele_integrations_settings', []);
    
    // Nếu tính năng bị tắt, dừng ngay
    if ( !isset($options['enable_ip_blocker']) || $options['enable_ip_blocker'] !== 'on' ) {
        return;
    }

    // Không chặn Admin đã đăng nhập
    if ( current_user_can('manage_options') ) {
        return;
    }

    $user_ip = get_the_user_ip();

    // 1. Kiểm tra Whitelist (Ưu tiên cao nhất)
    $whitelist_raw = $options['ip_manual_whitelist'] ?? '';
    if ( !empty($whitelist_raw) ) {
        $whitelist = preg_split('/[\r\n]+/', $whitelist_raw, -1, PREG_SPLIT_NO_EMPTY);
        $whitelist = array_map('trim', $whitelist);
        
        if ( in_array($user_ip, $whitelist) ) {
            return; // IP an toàn, cho phép truy cập
        }
    }

    // 2. Kiểm tra Blacklist thủ công
    $blacklist_raw = $options['ip_manual_blacklist'] ?? '';
    $manual_blacklist = [];
    if ( !empty($blacklist_raw) ) {
        $manual_blacklist = preg_split('/[\r\n]+/', $blacklist_raw, -1, PREG_SPLIT_NO_EMPTY);
        $manual_blacklist = array_map('trim', $manual_blacklist);
    }

    // 3. Kiểm tra Blacklist tự động (từ cache)
    $auto_blacklist = get_transient('tuancele_auto_blocklist') ?: [];

    // 4. Gộp 2 danh sách cấm
    $full_blacklist = array_merge($manual_blacklist, $auto_blacklist);
    if ( empty($full_blacklist) ) {
        return; // Không có IP nào bị cấm
    }

    // 5. Chặn
    if ( in_array($user_ip, $full_blacklist) ) {
        wp_die(
            'Your IP address has been temporarily blocked due to unusual activity. Please try again later. (IP của bạn đã bị tạm khóa do hoạt động bất thường.)',
            'Access Denied (Truy cập bị từ chối)',
            ['response' => 403]
        );
    }
}
// Chạy "Bộ lọc Cổng" ở mức ưu tiên 1 (sớm nhất có thể)
add_action('init', 'tuancele_ip_blocker_gatekeeper', 1);


/**
 * 2. BỘ PHÂN TÍCH (THE DETECTIVE)
 * Chạy ngầm (Cron) mỗi giờ để phân tích log và cập nhật danh sách cấm.
 */

// 2a. Đăng ký lịch chạy cron (mỗi giờ)
if ( !wp_next_scheduled('tuancele_ip_analyzer_cron_hook') ) {
    wp_schedule_event(time(), 'hourly', 'tuancele_ip_analyzer_cron_hook');
}

// 2b. Gán hàm xử lý cho hook
add_action('tuancele_ip_analyzer_cron_hook', 'tuancele_run_log_analyzer');

// 2c. Hàm xử lý (Cron function)
function tuancele_run_log_analyzer() {
    global $wpdb;
    $options = get_option('tuancele_integrations_settings', []);
    
    // Nếu tính năng tắt, xóa transient (nếu có) và dừng lại
    if ( !isset($options['enable_ip_blocker']) || $options['enable_ip_blocker'] !== 'on' ) {
        delete_transient('tuancele_auto_blocklist');
        return;
    }

    // Lấy ngưỡng chặn, đảm bảo an toàn
    $threshold = absint($options['blocking_threshold'] ?? 500);
    if ($threshold < 50) $threshold = 50; // Đặt ngưỡng tối thiểu 50 để tránh tự chặn nhầm

    $table_name = $wpdb->prefix . 'visitor_logs';
    $time_sql = 'NOW() - INTERVAL 1 HOUR'; // Quét log trong 1 giờ qua

    // Lấy Whitelist để loại trừ khỏi truy vấn
    $whitelist_raw = $options['ip_manual_whitelist'] ?? '';
    $whitelist = preg_split('/[\r\n]+/', $whitelist_raw, -1, PREG_SPLIT_NO_EMPTY);
    $whitelist = array_map('trim', $whitelist);
    
    // Thêm các IP local và IP hợp lệ vào Whitelist (để không bao giờ tự động chặn)
    $default_whitelist = ['127.0.0.1', '::1'];
    $whitelist = array_merge($whitelist, $default_whitelist);
    
    $placeholders = implode( ', ', array_fill( 0, count( $whitelist ), '%s' ) );
    
    // Câu SQL để tìm các IP có số request > $threshold trong 1 giờ qua
    // VÀ không nằm trong whitelist
    $sql = $wpdb->prepare(
        "SELECT ip_address
        FROM {$table_name}
        WHERE visit_time > ({$time_sql})
        AND ip_address NOT IN ({$placeholders})
        GROUP BY ip_address
        HAVING COUNT(id) > %d",
        array_merge($whitelist, [$threshold])
    );

    $bad_ips = $wpdb->get_col($sql);

    // Lưu kết quả vào transient, cache trong 1 giờ
    // "Bộ lọc Cổng" sẽ đọc transient này
    set_transient('tuancele_auto_blocklist', $bad_ips, HOUR_IN_SECONDS);
    
    // Ghi lại thời gian chạy lần cuối (để hiển thị trong admin)
    update_option('tuancele_ip_analyzer_last_run', time());
}