<?php
/**
 * qapage-comments.php
 *
 * Template bình luận (Answers/Comments) tùy chỉnh cho Module QAPage.
 * File này được tải bởi class-qapage-templates.php (File 3)
 * thay vì file comments.php gốc.
 *
 * Nó KHÔNG sử dụng tuancele_amp_comment_form()
 * mà sử dụng comment_form() chuẩn để module bảo mật (File 5) có thể hook vào.
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
    // --- FORM GỬI CÂU TRẢ LỜI ---
    // Chúng ta gọi hàm comment_form() chuẩn của WordPress.
    // class-qapage-security.php (File 5) sẽ tự động:
    // 1. Hook vào 'comment_form_defaults' để đổi tiêu đề.
    // 2. Chèn reCaptcha vào form.
    // 3. Hook vào 'preprocess_comment' để xác thực reCaptcha.
    
    comment_form( [
        // Tùy chỉnh các nhãn (label) cho phù hợp với logic QAPage
        'title_reply'         => __( 'Gửi câu trả lời của bạn', 'tuancele-amp' ),
        'title_reply_to'      => __( 'Trả lời cho %s', 'tuancele-amp' ), // Dùng cho bình luận nhỏ (cấp 2+)
        'label_submit'        => __( 'Gửi câu trả lời', 'tuancele-amp' ),
        'comment_field'       => '<p class="comment-form-comment"><label for="comment">' . _x( 'Nội dung trả lời', 'noun', 'tuancele-amp' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea></p>',
        'comment_notes_after' => '', // Xóa ghi chú mặc định, vì File 5 sẽ chèn reCaptcha vào đây
        'fields'              => [
            // Tái sử dụng các trường mặc định của WordPress (đã được style bởi theme)
            'author' => '<p class="comment-form-author"><label for="author">' . __( 'Tên', 'tuancele-amp' ) . ' <span class="required">*</span></label> ' .
                        '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" maxlength="245" required="required" /></p>',
            'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'tuancele-amp' ) . ' <span class="required">*</span></label> ' .
                        '<input id="email" name="email" type="email" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" maxlength="100" aria-describedby="email-notes" required="required" /></p>',
        ],
    ] );
    ?>
</div>