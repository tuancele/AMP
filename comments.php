<?php
/**
 * comments.php - Phiên bản sửa lỗi dứt điểm.
 * Gọi trực tiếp hàm tuancele_amp_comment_form() thay vì comment_form() mặc định.
 */

if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            printf(
                esc_html( _nx( '%1$s Bình luận', '%1$s Bình luận', $comment_count, 'comments title', 'amp-custom-theme' ) ),
                number_format_i18n( $comment_count )
            );
            ?>
        </h2>
        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 50,
                'callback'    => 'tuancele_amp_comment_callback',
            ) );
            ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments"><?php esc_html_e( 'Bình luận đã được đóng.', 'amp-custom-theme' ); ?></p>
    <?php endif; ?>

    <?php 
    // GỌI HÀM TÙY CHỈNH CỦA CHÚNG TA.
    // Hàm này (trong inc/comments-handler.php) chứa logic form AMP và CAPTCHA.
    if ( function_exists('tuancele_amp_comment_form') ) {
        tuancele_amp_comment_form();
    }
    ?>

</div>