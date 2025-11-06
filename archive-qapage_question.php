<?php
/**
 * archive-qapage_question.php
 *
 * Template Archive cho CPT 'qapage_question' (Module QAPage).
 * URL: /qapage/
 * Hiển thị danh sách tất cả các câu hỏi.
 *
 * [FIX] Thêm nút "Đặt câu hỏi" để điều hướng về trang /qa-page/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<?php // Tái sử dụng .page-header từ archive.php ?>
<header class="page-header">
    <?php
    // Tự động hiển thị "Hỏi & Đáp (Q&A)"
    the_archive_title( '<h1 class="page-title">', '</h1>' );
    the_archive_description( '<div class="archive-description" style="margin-top: 15px;">', '</div>' );
    ?>
</header>

<?php
// Tái sử dụng breadcrumbs của theme
if ( function_exists( 'tuancele_amp_display_breadcrumbs' ) ) {
    tuancele_amp_display_breadcrumbs();
}
?>

<?php
// [THÊM MỚI] Thêm nút Đặt Câu Hỏi Mới
$ask_page_url = home_url('/qa-page/'); 
?>
<a href="<?php echo esc_url($ask_page_url); ?>" class="qapage-ask-button">
    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2.5 11.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm-5 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm-5 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
    <span>Đặt câu hỏi của bạn</span>
</a>

<?php if ( have_posts() ) : ?>
    
    <?php // Tái sử dụng .posts-grid-container từ archive.php ?>
    <div class="posts-grid-container">
        <?php 
        $post_counter = 0;
        while ( have_posts() ) : the_post(); 
            
            // Tối ưu LCP: Đặt cờ cho ảnh đầu tiên
            // content-card.php sẽ đọc biến này
            set_query_var( 'is_lcp_image', ( $post_counter === 0 && ! is_paged() ) );
            
            // Tái sử dụng template card
            get_template_part( 'template-parts/content-card' );
            
            $post_counter++;
        endwhile; 
        ?>
    </div>

    <?php
    // Tái sử dụng pagination của theme
    the_posts_pagination( [
        'prev_text'          => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M12.45 15.55l-5-5 5-5L11.05 4.1 6 9.1l5.05 5.05 1.4-1.4z" fill="currentColor"/></svg><span>Trang trước</span>',
        'next_text'          => '<span>Trang sau</span><svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M7.55 4.45l5 5-5 5L8.95 15.9 14 10.9 8.95 5.85 7.55 4.45z" fill="currentColor"/></svg>',
        'screen_reader_text' => ' ', 
        'type'               => 'list',
        'mid_size'           => 1,
        'end_size'           => 1,
    ] );
    ?>

<?php else : ?>
    
    <?php // Tái sử dụng class từ search.php ?>
    <div class="no-search-results">
        <p><?php _e( 'Chưa có câu hỏi nào được đăng.', 'tuancele-amp' ); ?></p>
    </div>

<?php endif; ?>

<?php get_footer(); ?>