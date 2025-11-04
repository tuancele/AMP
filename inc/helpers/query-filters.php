<?php
/**
 * inc/helpers/query-filters.php
 *
 * Chứa các hàm tinh chỉnh truy vấn (query) chính của WordPress,
 * các bộ lọc liên quan đến tìm kiếm, và các trình theo dõi hiệu suất.
 * Tệp này là một phần của quá trình tái cấu trúc từ template-helpers.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// QUERY/PERFORMANCE FILTERS & HOOKS
// =========================================================================

/**
 * Tùy chỉnh số lượng bài viết hiển thị trên mỗi trang (posts_per_page)
 * cho các trang lưu trữ (archive).
 */
function tuancele_custom_posts_per_page($query) {
    if ( ! is_admin() && $query->is_main_query() && is_archive() ) {
        $query->set('posts_per_page', 6);
    }
}
add_action('pre_get_posts', 'tuancele_custom_posts_per_page');

/**
 * Đánh dấu (highlight) từ khóa tìm kiếm trên trang kết quả.
 */
function tuancele_highlight_search_results($text){
    if(is_search()) $text = str_ireplace(get_search_query(), '<mark class="search-highlight">'.get_search_query().'</mark>', $text);
    return $text;
}
add_filter('the_excerpt', 'tuancele_highlight_search_results');
add_filter('the_title', 'tuancele_highlight_search_results');

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