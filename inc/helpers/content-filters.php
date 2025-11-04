<?php
/**
 * inc/helpers/content-filters.php
 *
 * Chứa các hàm lọc (filter) để tự động sửa đổi hoặc chèn nội dung
 * vào (ví dụ: qua hook 'the_content').
 * Tệp này là một phần của quá trình tái cấu trúc từ template-helpers.php.
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

/**
 * Xử lý, tạo và chèn Mục lục (TOC) vào nội dung.
 */
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

/**
 * Hàm trợ giúp, xây dựng HTML cho Mục lục (TOC).
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
    <?php return ob_get_clean();
}