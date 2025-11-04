<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * page.php - Đã tắt hiển thị ảnh đại diện
 * ĐÃ TỐI ƯU: Đặt Breadcrumbs trước H1.
 *
 * [TỐI ƯU V7.1 - THÊM PROGRESS BAR]
 * - Thêm thanh tiến trình TOC giống như single.php.
 * - Thêm id="post-content-article" vào thẻ <article>.
 * - Thêm <amp-animation> và <amp-position-observer> vào cuối tệp.
 */
get_header();
?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <?php
    // TỐI ƯU 1: Đặt Breadcrumbs trước H1
    if (function_exists('tuancele_amp_display_breadcrumbs')) {
        tuancele_amp_display_breadcrumbs();
    }
    ?>
    
    <?php // [FIX V7.1] Thêm ID vào thẻ article ?>
    <article id="post-content-article">
        <h1><?php the_title(); ?></h1>

        <?php
        /*
         * ĐÃ VÔ HIỆU HÓA: Đoạn code hiển thị ảnh đại diện
         */
        ?>

        <div class="content"><?php the_content(); ?></div>
    </article>
<?php endwhile; else : ?>
    <p>No content found.</p>
<?php endif; ?>
<?php
// Nếu bình luận được mở hoặc có ít nhất 1 bình luận, hiển thị template.
if ( comments_open() || get_comments_number() ) :
    comments_template();
endif;
?>

<?php
/**
 * =========================================================================
 * [THÊM MỚI V7.1] LOGIC CHO THANH TIẾN TRÌNH ĐỌC (TÍCH HỢP VÀO TOC)
 * =========================================================================
 */
?>
<amp-animation id="readingProgressAnimation" layout="nodisplay">
    <script type="application/json">
        {
            "duration": "1s",
            "fill": "forwards",
            "animations": [{
                "selector": ".toc-progress-bar-background",
                "property": "transform",
                "keyframes": [
                    { "transform": "scaleX(0)", "offset": 0 },
                    { "transform": "scaleX(1)", "offset": 1 }
                ]
            }]
        }
    </script>
</amp-animation>

<?php // Thêm target="post-content-article" để theo dõi thẻ <article> ?>
<amp-position-observer
    on="scroll:readingProgressAnimation.seekTo(percent=event.percent)"
    layout="nodisplay"
    target="post-content-article">
</amp-position-observer>

<?php get_footer(); ?>