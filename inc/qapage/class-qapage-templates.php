<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * inc/qapage/class-qapage-templates.php
 *
 * Ép WordPress sử dụng các file template QAPage tùy chỉnh (archive, single, comments)
 * thay vì các template mặc định của theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Templates {

    /**
     * Khởi tạo class, đăng ký các bộ lọc (filter) template.
     */
    public function __construct() {
        // 1. Ghi đè template trang Archive (ví dụ: /qapage/)
        add_filter( 'archive_template', [ $this, 'override_archive_template' ] );

        // 2. Ghi đè template trang Single (ví dụ: /qapage/tieu-de-cau-hoi/)
        add_filter( 'single_template', [ $this, 'override_single_template' ] );

        // 3. Ghi đè template bình luận (CHỈ cho CPT 'qapage_question')
        // Priority 99 để đảm bảo nó chạy sau các filter khác
        add_filter( 'comments_template', [ $this, 'override_comments_template' ], 99 );
    }

    /**
     * Tải archive-qapage_question.php cho trang archive /qapage/
     *
     * @param string $template Đường dẫn template WordPress tìm thấy.
     * @return string Đường dẫn template mới.
     */
    public function override_archive_template( $template ) {
        // Chỉ can thiệp nếu đây là trang archive của CPT 'qapage_question'
        if ( is_post_type_archive( 'qapage_question' ) ) {
            $new_template = get_template_directory() . '/archive-qapage_question.php';
            
            // Kiểm tra xem file template mới của chúng ta có tồn tại không
            if ( file_exists( $new_template ) ) {
                return $new_template;
            }
        }
        
        // Trả về template gốc nếu không phải
        return $template;
    }

    /**
     * Tải single-qapage_question.php cho một câu hỏi chi tiết.
     *
     * @param string $template Đường dẫn template WordPress tìm thấy.
     * @return string Đường dẫn template mới.
     */
    public function override_single_template( $template ) {
        // Chỉ can thiệp nếu đây là trang single của CPT 'qapage_question'
        if ( is_singular( 'qapage_question' ) ) {
            $new_template = get_template_directory() . '/single-qapage_question.php';
            
            if ( file_exists( $new_template ) ) {
                return $new_template;
            }
        }

        return $template;
    }

    /**
     * Tải qapage-comments.php cho template bình luận (Answers/Comments).
     *
     * @param string $template Đường dẫn template WordPress tìm thấy.
     * @return string Đường dẫn template mới.
     */
    public function override_comments_template( $template ) {
        // Chỉ can thiệp nếu đây là trang single của CPT 'qapage_question'
        if ( is_singular( 'qapage_question' ) ) {
            $new_template = get_template_directory() . '/qapage-comments.php';
            
            if ( file_exists( $new_template ) ) {
                return $new_template;
            }
        }
        
        // Quan trọng: Trả về $template gốc nếu không phải CPT của chúng ta
        // để không làm hỏng bình luận của bài viết 'post' thông thường.
        return $template;
    }
}