<?php
/**
 * inc/qapage/class-qapage-shortcodes.php
 *
 * Đăng ký các shortcode cho module QAPage.
 * 1. [qapage_related_list]: Hiển thị danh sách các câu hỏi liên quan đến bài viết hiện tại.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Shortcodes {

    // Key này PHẢI KHỚP 100% với key trong class-qapage-metabox.php
    private $meta_key = '_qapage_related_context_url';

    /**
     * Khởi tạo, đăng ký shortcode.
     */
    public function __construct() {
        add_shortcode( 'qapage_related_list', [ $this, 'render_related_list_shortcode' ] );
    }

    /**
     * Hàm render shortcode [qapage_related_list]
     *
     * @param array $atts Các thuộc tính shortcode (ví dụ: title="...")
     * @return string HTML của danh sách câu hỏi liên quan.
     */
    public function render_related_list_shortcode( $atts ) {
        // Chỉ chạy trên trang single (post, page, v.v.)
        if ( ! is_singular() ) {
            return '';
        }

        global $post;
        $current_post_id = $post->ID;
        
        // Lấy URL chuẩn (canonical) của bài viết/trang hiện tại.
        // Đây là "chìa khóa" để so khớp với meta box.
        $current_url = get_permalink( $current_post_id );

        // 1. Tùy chỉnh các thuộc tính shortcode
        $atts = shortcode_atts(
            [
                'title' => __( 'Các câu hỏi liên quan:', 'tuancele-amp' ),
                'limit' => 5,
            ],
            $atts,
            'qapage_related_list'
        );

        // 2. Xây dựng truy vấn (Query)
        $query_args = [
            'post_type'      => 'qapage_question', // Chỉ tìm CPT 'qapage_question'
            'post_status'    => 'publish',
            'posts_per_page' => absint( $atts['limit'] ),
            'meta_query'     => [
                [
                    'key'     => $this->meta_key,
                    'value'   => $current_url, // Tìm các câu hỏi có meta_value khớp với URL hiện tại
                    'compare' => '=',
                ],
            ],
        ];

        $related_questions_query = new WP_Query( $query_args );

        // 3. Render HTML
        if ( ! $related_questions_query->have_posts() ) {
            return ''; // Không tìm thấy gì, không hiển thị gì cả
        }

        ob_start();
        ?>
        <div class="qapage-related-list-widget">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="qapage-related-list-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>
            
            <ul class="qapage-related-list">
                <?php while ( $related_questions_query->have_posts() ) : $related_questions_query->the_post(); ?>
                    <li>
                        <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <?php
        
        wp_reset_postdata(); // Khôi phục lại query gốc của trang
        
        return ob_get_clean();
    }
}