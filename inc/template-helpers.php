<?php
/**
 * inc/template-helpers.php
 * Chứa các hàm tiện ích cho template (breadcrumbs, log, reading time, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// LOGGING, IP TOOLS & LOG ROTATION
// =========================================================================

function get_the_user_ip() {
    $ip = 'Unknown';
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) $ip = $_SERVER['HTTP_CLIENT_IP'];
    elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) $ip = $_SERVER['REMOTE_ADDR'];
    if ( strpos( $ip, ',' ) !== false ) $ip = trim( explode( ',', $ip )[0] );
    return filter_var($ip, FILTER_VALIDATE_IP) ?: 'Invalid IP';
}
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
function tuancele_perform_log_rotation() {
    $upload_dir = wp_get_upload_dir(); $log_dir = $upload_dir['basedir'] . '/';
    $log_file_to_archive = $log_dir . 'ip_log-' . date('Y-m-d', strtotime('-3 days')) . '.txt';
    if (!file_exists($log_file_to_archive)) return;
    $archive_dir = $upload_dir['basedir'] . '/ip_logs_archive/';
    if (!file_exists($archive_dir)) wp_mkdir_p($archive_dir);
    $archive_file_path = $archive_dir . 'ip_log-' . date('Y-m-d', strtotime('-3 days')) . '.txt.gz';
    if ($gz = gzopen($archive_file_path, 'w9')) { gzwrite($gz, file_get_contents($log_file_to_archive)); gzclose($gz); unlink($log_file_to_archive); }
}
if (!wp_next_scheduled('tuancele_daily_log_rotation_event')) { wp_schedule_event(strtotime('02:00:00'), 'daily', 'tuancele_daily_log_rotation_event'); }
add_action('tuancele_daily_log_rotation_event', 'tuancele_perform_log_rotation');
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
function add_logging_debugger() {
    if (current_user_can('manage_options')) {
        $log_dir = wp_get_upload_dir()['basedir'];
        $writable = is_writable($log_dir);
        echo "<div style='position: fixed; bottom: 0; left: 0; width: 100%; background: #222; color: white; padding: 10px; z-index: 99999; font-family: monospace; font-size: 12px; text-align: center;'><strong>[DEBUGGER - ADMIN ONLY]</strong> Thư mục log: <code>" . esc_html($log_dir) . "</code> | Trạng thái: <strong style='color: " . ($writable ? 'green' : 'red') . ";'>" . ($writable ? 'CÓ THỂ GHI' : 'KHÔNG THỂ GHI') . "</strong></div>";
    }
}
add_action('wp_footer', 'add_logging_debugger');

// =========================================================================
// MISCELLANEOUS HELPERS
// =========================================================================

function tuancele_amp_display_breadcrumbs() {
    if ( is_front_page() ) return;
    echo '<nav aria-label="breadcrumb" class="breadcrumb-container"><ol class="breadcrumbs-list">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Trang Chủ</a></li>';
    if ( is_singular( 'post' ) ) {
        $categories = get_the_category();
        if ( ! empty( $categories ) ) echo '<li><a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></li>';
        echo '<li class="current-item">' . get_the_title() . '</li>';
    } elseif ( is_page() ) { echo '<li class="current-item">' . get_the_title() . '</li>';
    } elseif ( is_archive() ) { 
    // Lấy tiêu đề và loại bỏ thẻ HTML ngay lập tức
    $archive_title = strip_tags(get_the_archive_title()); 
    echo '<li class="current-item">' . esc_html($archive_title) . '</li>'; 
}
    echo '</ol></nav>';
}

function tuancele_custom_posts_per_page($query) {
    if ( ! is_admin() && $query->is_main_query() && is_archive() ) {
        $query->set('posts_per_page', 6);
    }
}
add_action('pre_get_posts', 'tuancele_custom_posts_per_page');

function tuancele_highlight_search_results($text){
    if(is_search()) $text = str_ireplace(get_search_query(), '<mark class="search-highlight">'.get_search_query().'</mark>', $text);
    return $text;
}
add_filter('the_excerpt', 'tuancele_highlight_search_results');
add_filter('the_title', 'tuancele_highlight_search_results');

function tuancele_estimated_reading_time() {
    $word_count = str_word_count(strip_tags(get_post_field('post_content', get_the_ID())));
    return esc_html(max(1, ceil($word_count / 200))) . ' phút đọc';
}

function tuancele_wrap_tables_in_div($content) {
    return preg_replace('/<table(.*?)>(.*?)<\/table>/is', '<div class="table-wrapper"><table$1>$2</table></div>', $content);
}
add_filter('the_content', 'tuancele_wrap_tables_in_div', 20);

/**
 * [OPTIMIZED] Theo dõi lượt xem bài viết bằng Transients để giảm DB writes.
 * Cập nhật vào database sau mỗi 15 lượt xem tạm thời.
 */
function tuancele_track_post_views() {
    // Vẫn giữ nguyên các điều kiện không đếm
    if ( !is_singular() || (is_user_logged_in() && current_user_can('manage_options')) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])) ) {
        return;
    }

    $post_id = get_the_ID();
    $transient_key = 'tuancele_post_views_buffer_' . $post_id;
    $count_key = '_post_view_count';

    // 1. Lấy bộ đếm tạm thời từ transient (ngăn kéo)
    $buffer_count = get_transient($transient_key);

    // Nếu chưa có bộ đếm tạm, gán bằng 1. Nếu có rồi, tăng lên 1.
    $buffer_count = $buffer_count ? $buffer_count + 1 : 1;

    // 2. Cập nhật lại bộ đếm tạm thời trong transient
    // Transient sẽ tự hết hạn sau 30 phút để tránh bị kẹt
    set_transient($transient_key, $buffer_count, 30 * MINUTE_IN_SECONDS);

    // 3. Điều kiện "Gửi tiền vào ngân hàng"
    // Nếu bộ đếm tạm chia hết cho 15 (tức là sau mỗi 15 lượt xem)
    if ($buffer_count % 15 === 0) {
        // Lấy tổng số lượt xem thật đang được lưu trong database
        $total_views = (int) get_post_meta($post_id, $count_key, true);
        if (empty($total_views)) {
            // Khởi tạo nếu chưa có
            $total_views = rand(5, 25);
        }

        // Cộng dồn 15 lượt xem mới vào tổng
        $new_total_views = $total_views + 15;

        // Cập nhật vào database
        update_post_meta($post_id, $count_key, $new_total_views);

        // Xóa bộ đếm tạm thời (dọn dẹp ngăn kéo)
        delete_transient($transient_key);
    }
}
add_action( 'wp_head', 'tuancele_track_post_views' );

function tuancele_append_auto_rating_box( $content ) {
    if ( is_singular() && in_the_loop() && is_main_query() ) {
        $post_id = get_the_ID();
        $rating_count = get_post_meta( $post_id, '_post_view_count', true ) ?: 1;
        $rating_value = 5.0; $percentage = ($rating_value / 5) * 100;
        ob_start();
        ?>
        <div class="rating-box">
            <div class="star-rating" title="Đánh giá: <?php echo esc_attr($rating_value); ?> trên 5 sao">
                <div class="star-rating-background"><?php for ($i=0; $i<5; $i++) echo '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?></div>
                <div class="star-rating-foreground" style="width: <?php echo esc_attr($percentage); ?>%;"><?php for ($i=0; $i<5; $i++) echo '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?></div>
            </div>
            <div class="rating-text"><strong><?php echo esc_html($rating_value); ?></strong>/5 sao (<?php echo esc_html( number_format_i18n($rating_count) ); ?> đánh giá)</div>
        </div>
        <script type="application/ld+json">{"@context":"https://schema.org/","@type":"Product","name":"<?php echo esc_js(get_the_title($post_id)); ?>","aggregateRating":{"@type":"AggregateRating","ratingValue":"<?php echo esc_js($rating_value); ?>","ratingCount":"<?php echo esc_js($rating_count); ?>","bestRating":"5","worstRating":"1"}}</script>
        <?php
        // [SỬA LỖI] Di chuyển return ra ngoài để đảm bảo hàm luôn trả về giá trị
        $rating_html = ob_get_clean();
        return $content . $rating_html;
    }
    return $content;
}
add_filter( 'the_content', 'tuancele_append_auto_rating_box' );

function tuancele_display_related_posts() {
    if ( ! is_single() ) return;
    $categories = get_the_category( get_the_ID() ); if ( empty( $categories ) ) return;
    $query_args = ['post_type' => 'post', 'category__in' => wp_list_pluck( $categories, 'term_id' ), 'post__not_in' => [get_the_ID()], 'posts_per_page' => 3, 'ignore_sticky_posts' => 1, 'orderby' => 'rand'];
    $related_query = new WP_Query( $query_args );
    if ( $related_query->have_posts() ) : ?>
        <section class="related-posts-section">
            <h2 class="related-posts-title">Bài viết liên quan</h2>
            <div class="posts-grid-container">
                <?php while ( $related_query->have_posts() ) : $related_query->the_post(); get_template_part('template-parts/content-card'); endwhile; ?>
            </div>
        </section>
    <?php endif; wp_reset_postdata();
}

function tuancele_stable_toc_handler($content) {
    if (!is_single() || is_admin() || !in_the_loop() || !is_main_query()) return $content;
    preg_match_all('/<h([2-3])(.*?)>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);
    if (count($matches) < 2) return $content;
    $toc_items = []; $new_content = $content;
    foreach ($matches as $match) {
        $level = $match[1]; $text = strip_tags($match[3]); $id = sanitize_title($text); $temp_id = $id; $counter = 2;
        while (strpos($new_content, 'id="' . $temp_id . '"') !== false) { $temp_id = $id . '-' . $counter++; }
        $id = $temp_id;
        $new_heading = sprintf('<h%s id="%s"%s>%s</h%s>', $level, esc_attr($id), $match[2], $match[3], $level);
        $new_content = str_replace($match[0], $new_heading, $new_content);
        $toc_items[] = ['id' => $id, 'text' => $text, 'level' => (int)$level];
    }
    $toc_html = tuancele_build_stable_toc_html($toc_items);
    $insertion_point = strpos($new_content, '</p>');
    return ($insertion_point !== false) ? substr_replace($new_content, '</p>' . $toc_html, $insertion_point, 4) : $toc_html . $new_content;
}
add_filter('the_content', 'tuancele_stable_toc_handler', 25);

function tuancele_build_stable_toc_html($items) {
    ob_start(); ?>
    <div class="smart-toc-container with-progress-bar">
        <div class="toc-progress-bar-background"></div>
        <amp-accordion expand-single-section id="tocAccordion" on="expand:toc-overlay.show; collapse:toc-overlay.hide">
            <section>
                <header class="toc-header"><span class="toc-header-title"><strong>Mục lục bài viết</strong></span></header>
                <div class="toc-full-list">
                    <ul>
                    <?php
                    $last_level = 2;
                    foreach ($items as $item) {
                        $current_level = $item['level'];
                        $actions = esc_attr($item['id']) . ".scrollTo(duration=300), tocAccordion.toggle(section='[expanded]')";
                        while ($current_level < $last_level) { echo '</li></ul>'; $last_level--; }
                        if ($current_level > $last_level) echo '<ul>';
                        if ($current_level === $last_level && $item !== $items[0]) echo '</li>';
                        echo '<li><a role="button" tabindex="0" on="tap:' . $actions . '">' . esc_html($item['text']) . '</a>';
                        $last_level = $current_level;
                    }
                    while ($last_level >= 2) { echo '</li>'; if ($last_level > 2) echo '</ul>'; $last_level--; }
                    ?>
                    </ul>
                </div>
            </section>
        </amp-accordion>
    </div>
    <?php return ob_get_clean();
}

/**
 * =========================================================================
 * LIVE STATUS CHECKER HELPERS
 * =========================================================================
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
/**
 * Tạo nội dung manifest.json động từ cài đặt WordPress (Cho PWA).
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
            "theme_color" => "#17a2b8",
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
