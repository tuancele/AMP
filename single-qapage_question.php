<?php
/**
 * single-qapage_question.php
 *
 * Template Single cho CPT 'qapage_question' (Module QAPage).
 * URL: /qapage/tieu-de-cau-hoi/
 * Hiển thị Câu hỏi (Post), và gọi template 'qapage-comments.php' để hiển thị
 * các Câu trả lời (Answers) và Bình luận (Comments).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    
    <?php
    // Tái sử dụng breadcrumbs của theme
    if ( function_exists( 'tuancele_amp_display_breadcrumbs' ) ) { 
        tuancele_amp_display_breadcrumbs(); 
    } 
    ?>
    
    <?php
    // Lấy link nội dung liên quan (từ File 6 / class-qapage-metabox.php)
    $related_url = get_post_meta( get_the_ID(), '_qapage_related_context_url', true );
    ?>

    <article id="question-<?php the_ID(); ?>" 
             itemscope 
             itemtype="https://schema.org/Question" 
             itemprop="mainEntity"
             data-post-id="<?php the_ID(); ?>"
             data-author-id="<?php echo get_the_author_meta('ID'); ?>">
        
        <h1 itemprop="name"><?php the_title(); ?></h1>

        <?php // Tái sử dụng class 'post-meta' từ single.php ?>
        <div class="post-meta">
            <span class="post-meta-author" itemprop="author" itemscope itemtype="https://schema.org/Person">
                <?php // Icon (tái sử dụng từ single.php) ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                
                <?php // Schema cho Tác giả (đã đăng nhập) ?>
                <meta itemprop="url" content="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                <span itemprop="name"><?php echo get_the_author_posts_link(); ?></span>
            </span>
            
            <span class="post-meta-date">
                <?php // Icon (tái sử dụng từ single.php) ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                
                <?php // Schema cho Ngày đăng ?>
                <time itemprop="dateCreated" datetime="<?php echo get_the_date('c'); ?>">
                    <?php echo get_the_date('d \t\h\á\n\g m, Y'); ?>
                </time>
            </span>
            
            <?php // Schema cho Số lượng câu trả lời ?>
            <span class="post-meta-answers" itemprop="answerCount" content="<?php echo esc_attr( get_comments_number() ); ?>">
                <?php // Icon (tùy chỉnh) ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg>
                <?php echo esc_html( get_comments_number() ); ?> câu trả lời
            </span>
        </div>

        <?php // Nội dung câu hỏi ?>
        <div class="content" itemprop="text"><?php the_content(); ?></div>
        
        <?php 
        // Hiển thị khối "Nội dung liên quan" (từ File 6)
        if ( ! empty( $related_url ) ) : 
            $post_id_from_url = url_to_postid( $related_url );
            $related_title = $post_id_from_url ? get_the_title( $post_id_from_url ) : 'Nội dung liên quan';
        ?>
            <div class="qapage-related-context">
                <strong><?php _e( 'Chủ đề liên quan:', 'tuancele-amp' ); ?></strong>
                <a href="<?php echo esc_url( $related_url ); ?>"><?php echo esc_html( $related_title ); ?></a>
            </div>
            
            <?php 
            // Tự động chèn shortcode (từ File 8) để hiển thị các câu hỏi KHÁC 
            // cũng liên quan đến chủ đề này (nếu có)
            echo do_shortcode( '[qapage_related_list post_id="' . $post_id_from_url . '" title="Các câu hỏi khác về chủ đề này:"]' ); 
            ?>
            
        <?php endif; ?>
        
    </article>
    
<?php endwhile; endif; ?>

<?php
// Tải hệ thống bình luận (Answer/Comment)
// Hàm này sẽ tự động tải file 'qapage-comments.php'
// nhờ logic trong class-qapage-templates.php (File 3)
comments_template();
?>

<?php get_footer(); ?>