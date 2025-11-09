<?php
/**
 * inc/helpers/content-filters.php
 *
 * Chứa các hàm lọc (filter) để tự động sửa đổi hoặc chèn nội dung
 * vào (ví dụ: qua hook 'the_content').
 * [FIX LỖI ANIMATION]: Đặt biến $GLOBALS['has_toc'] để ngăn lỗi animation
 * khi trang không có mục lục.
 *
 * [KHÔI PHỤC V11 GỐC]
 * - Khôi phục logic tuancele_stable_toc_handler (chèn H2/H3).
 * - Khôi phục logic tuancele_build_stable_toc_html (tạo accordion + progress bar).
 * - Đã XÓA logic V12/V13.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// CONTENT FILTERS (Auto-modify post content)
// =========================================================================

/**
 * Tự động bọc các thẻ <table> trong một <div> để hỗ trợ cuộn ngang (responsive).
 */
function tuancele_wrap_tables_in_div($content) {
    return preg_replace('/<table(.*?)>(.*?)<\/table>/is', '<div class="table-wrapper"><table$1>$2</table></div>', $content);
}
add_filter('the_content', 'tuancele_wrap_tables_in_div', 20);

/**
 * Tự động chèn hộp đánh giá (rating box) vào cuối nội dung bài viết.
 */
function tuancele_append_auto_rating_box( $content ) {
    // [ĐÃ SỬA] Thêm điều kiện để không tự động chạy trên CPT 'property'
    if ( is_singular() && in_the_loop() && is_main_query() && get_post_type() !== 'property' ) {
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
        $rating_html = ob_get_clean();
        return $content . $rating_html;
    }
    return $content;
}
add_filter( 'the_content', 'tuancele_append_auto_rating_box' );

/**
 * Xử lý, tạo và chèn Mục lục (TOC).
 * [FIX V3] Sửa lỗi trùng lặp ID (khi heading đã có ID)
 * và mở rộng để hỗ trợ H4.
 */
function tuancele_stable_toc_handler($content) {
    // [ĐÃ SỬA] Khởi tạo biến global, mặc định là false
    $GLOBALS['has_toc'] = false; 

    if (!is_singular() || is_admin() || !in_the_loop() || !is_main_query()) return $content;

    // [FIX 1] Mở rộng regex để bao gồm H2, H3, và H4
    preg_match_all('/<h([2-4])(.*?)>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);

    // Nếu không đủ heading (ít hơn 2), không tạo TOC và trả về nội dung gốc
    if (count($matches) < 2) return $content;

    // [ĐÃ SỬA] Đặt biến global là TRUE vì TOC chắc chắn sẽ được tạo
    $GLOBALS['has_toc'] = true; 

    $toc_items = []; $new_content = $content;
    foreach ($matches as $match) {
        $level = $match[1]; $text = strip_tags($match[3]); $id = sanitize_title($text); $temp_id = $id; $counter = 2;
        
        // [FIX 2] Xóa bỏ bất kỳ thuộc tính 'id' nào đã tồn tại trong $match[2]
        $attributes = preg_replace('/\s*id\s*=\s*["\'][^"\']*["\']/i', '', $match[2]);

        // Kiểm tra ID trùng lặp trong nội dung *đã được sửa đổi*
        while (strpos($new_content, 'id="' . $temp_id . '"') !== false) { 
            $temp_id = $id . '-' . $counter++; 
        }
        $id = $temp_id;
        
        // [FIX 3] Sử dụng $attributes đã được làm sạch (thay vì $match[2])
        $new_heading = sprintf('<h%s id="%s"%s>%s</h%s>', $level, esc_attr($id), $attributes, $match[3], $level);
        
        // [FIX LỖI] Sử dụng preg_replace với giới hạn 1 lần
        // Điều này đảm bảo chỉ thay thế 1 tiêu đề mỗi lần, ngay cả khi chúng giống hệt nhau
        $new_content = preg_replace('/' . preg_quote($match[0], '/') . '/', $new_heading, $new_content, 1);
        
        $toc_items[] = ['id' => $id, 'text' => $text, 'level' => (int)$level];
    }
    
    // Phần chèn TOC vào bài viết (giữ nguyên)
    $toc_html = tuancele_build_stable_toc_html($toc_items);
    $insertion_point = strpos($new_content, '</p>');
    return ($insertion_point !== false) ? substr_replace($new_content, '</p>' . $toc_html, $insertion_point, 4) : $toc_html . $new_content;
}

/**
 * Hàm trợ giúp, xây dựng HTML cho Mục lục (TOC).
 * [FIX V13] Xóa ký tự xuống dòng khỏi HTML output
 * để ngăn wpautop làm vỡ cấu trúc <amp-accordion>.
 */
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
    <?php 
    $html = ob_get_clean();
    
    // [SỬA LỖI QUAN TRỌNG] Biến toàn bộ HTML thành 1 DÒNG DUY NHẤT
    $html = str_replace(["\r", "\n", "\t"], '', $html);
    
    return $html;
}
// [KHÔI PHỤC V11 GỐC] KÍCH HOẠT HÀM TẠO MỤC LỤC (TOC)
add_filter('the_content', 'tuancele_stable_toc_handler', 12);