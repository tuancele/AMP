<?php
/**
 * qapage-comments.php
 *
 * Template bình luận (Answers/Comments) tùy chỉnh cho Module QAPage.
 * File này được tải bởi class-qapage-templates.php (File 3)
 * thay vì file comments.php gốc.
 *
 * [FIX] Sửa lỗi AMP "Only XHR based... submissions are support for POST":
 * Thay thế hàm comment_form() (gốc của WP) bằng hàm tuancele_amp_comment_form()
 * (hàm AMP-ready đã có sẵn trong theme, tại inc/comments-module.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Không hiển thị bất cứ thứ gì nếu bài viết bị đặt mật khẩu
if ( post_password_required() ) {
    return;
}
?>

<?php // Thêm class CSS tùy chỉnh 'qapage-comments-area' (từ File 11) ?>
<div id="comments" class="comments-area qapage-comments-area">

    <?php if ( have_comments() ) : ?>
        
        <?php // Đổi tiêu đề từ "Bình luận" thành "Câu trả lời" ?>
        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            printf(
                esc_html( _nx(
                    '%1$s Câu trả lời',
                    '%1$s Câu trả lời',
                    $comment_count,
                    'comments title',
                    'tuancele-amp'
                ) ),
                number_format_i18n( $comment_count )
            );
            ?>
        </h2>
        
        <?php // Vòng lặp hiển thị danh sách Answer/Comment ?>
        <ol class="comment-list">
            <?php
            // Gọi Walker tùy chỉnh (từ File 10)
            wp_list_comments( [
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 42, // Kích thước avatar cho Answer
                'walker'      => new AMP_QAPage_Walker_Comment(),
            ] );
            ?>
        </ol>

        <?php 
        // Phân trang bình luận (nếu có)
        the_comments_navigation(); 
        ?>

    <?php endif; // check for have_comments() ?>

    <?php 
    // Nếu bình luận đã đóng
    if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : 
    ?>
        <p class="no-comments"><?php _e( 'Phần trả lời đã bị đóng.', 'tuancele-amp' ); ?></p>
    <?php endif; ?>

    <?php
    // --- [SỬA LỖI] ---
    // Xóa toàn bộ khối comment_form() gốc của WordPress
    // và thay thế bằng hàm AMP-ready của theme.
    
    if ( function_exists('tuancele_amp_comment_form') ) {
        // Đổi tiêu đề của form cho phù hợp với trang Q&A
        // Hàm tuancele_amp_comment_form() không có tham số,
        // chúng ta sẽ phải tùy chỉnh nó nếu muốn đổi tiêu đề,
        // nhưng hiện tại nó sẽ fix được lỗi AMP.
        tuancele_amp_comment_form();
    }
    
    ?>
</div>