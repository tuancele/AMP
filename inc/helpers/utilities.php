<?php
/**
 * inc/helpers/utilities.php
 *
 * Chứa các hàm tiện ích chuyên dụng và độc lập, ví dụ như logic cho PWA
 * và công cụ kiểm tra trạng thái máy chủ (Live Status Checker).
 * Tệp này là một phần của quá trình tái cấu trúc từ template-helpers.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// LIVE STATUS CHECKER HELPERS (Sử dụng bởi page-live-status.php)
// =========================================================================

/**
 * Kiểm tra trạng thái "sống" của một domain/port.
 */
function tuancele_check_live_status($domain, $port, $timeout = 5) {
    $connection = @fsockopen($domain, $port, $errno, $errstr, $timeout);
    if (is_resource($connection)) {
        fclose($connection);
        return 'online';
    } else {
        return 'offline';
    }
}

/**
 * Lấy trạng thái "sống" đã được cache (transient) để tránh kiểm tra liên tục.
 */
function tuancele_get_cached_live_status($domain, $port, $cache_duration = 300) {
    $transient_key = 'live_status_' . sanitize_title($domain) . '_' . $port;
    $cached_status = get_transient($transient_key);
    if (false !== $cached_status) {
        return $cached_status;
    }
    $current_status = tuancele_check_live_status($domain, $port);
    set_transient($transient_key, $current_status, $cache_duration);
    return $current_status;
}

// =========================================================================
// PWA (PROGRESSIVE WEB APP) HELPERS
// =========================================================================

/**
 * Tạo nội dung tệp manifest.json động từ cài đặt WordPress (Cho PWA).
 */
function tuancele_generate_web_manifest() {
    // Chỉ chạy nếu URL đang truy cập là /site.webmanifest (URL ảo)
    if ( isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/site.webmanifest' ) {
        
        // Lấy dữ liệu động từ cài đặt WordPress
        $site_name = get_bloginfo('name');
        $short_name = strtok($site_name, ' '); // Lấy từ đầu tiên (tên ngắn gọn)
        $description = get_bloginfo('description');
        $theme_uri = get_template_directory_uri();
        
        // Tạo cấu trúc JSON
        $manifest = [
            "name" => $site_name,
            "short_name" => $short_name,
            "description" => $description,
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#17a2b8", // Bạn có thể thay đổi màu này
            "icons" => [
                [
                    "src" => $theme_uri . "/assets/icons/android-chrome-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => $theme_uri . "/assets/icons/android-chrome-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png"
                ]
            ]
        ];

        // Xuất ra header và nội dung JSON
        header('Content-Type: application/manifest+json; charset=utf-8');
        echo json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        exit(); // Dừng xử lý WordPress
    }
}
add_action('init', 'tuancele_generate_web_manifest');