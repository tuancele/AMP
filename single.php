<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * single.php - Phiên bản tối ưu UX với progress bar tích hợp trong TOC.
 *
 * [TỐI ƯU V7 - FIX PROGRESS BAR]
 * - Thêm id="post-content-article" vào thẻ <article>.
 * - Thêm target="post-content-article" vào thẻ <amp-position-observer>
 * để thanh tiến trình theo dõi đúng nội dung bài viết thay vì cả trang.
 * [FIX LỖI ANIMATION]: Chỉ in animation script nếu $GLOBALS['has_toc'] là true.
 *
 * [KHÔI PHỤC V11 GỐC]
 * - Khôi phục logic animation và position observer cho thanh tiến trình.
 */
get_header();
?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <?php if (function_exists('tuancele_amp_display_breadcrumbs')) { tuancele_amp_display_breadcrumbs(); } ?>
    
    <?php // [FIX V7] Thêm ID vào thẻ article ?>
    <article id="post-content-article">
        <h1><?php the_title(); ?></h1>

        <?php // --- Thông tin Meta bài viết --- ?>
        <div class="post-meta">
            <span class="post-meta-author">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <?php echo get_the_author_posts_link(); ?>
            </span>
            <span class="post-meta-date">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                <?php echo get_the_date('d \t\h\á\n\g m, Y'); ?>
            </span>
            <span class="post-meta-reading-time">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <?php if (function_exists('tuancele_estimated_reading_time')) { echo tuancele_estimated_reading_time(); } ?>
            </span>
        </div>
        <?php // --- Kết thúc Thông tin Meta bài viết --- ?>

        <?php // --- Nội dung chính của bài viết --- ?>
        <div class="content"><?php the_content(); ?></div>        
    </article>
<?php endwhile; else : ?>
    <p>No content found.</p>
<?php endif; ?>

<?php
// ======================================================
// [MỚI] HIỂN THỊ DANH SÁCH TIN BĐS NẾU ĐÂY LÀ TRANG DỰ ÁN
// ======================================================
global $post;
// 1. Kiểm tra xem trang/bài viết này có phải là dự án không
$is_project = get_post_meta( $post->ID, '_is_project', true );

if ( $is_project == '1' ) {
    $project_id = $post->ID;
    
    // 2. Truy vấn tất cả CPT 'property' liên kết với Project ID này
    $listings_args = [
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Hiển thị tất cả
        'meta_key'       => '_project_id', // Key liên kết từ meta box
        'meta_value'     => $project_id
    ];
    $project_listings = new WP_Query( $listings_args );

    if ( $project_listings->have_posts() ) {
        ?>
        <section class="related-posts-section project-listings-section">
            <h2 class="related-posts-title">Các BĐS đang bán tại Dự án</h2>
            
            <?php // 3. Sử dụng HTML wrapper (đã tối ưu mobile-first) ?>
            <div class="project-listing-wrapper">
                <?php 
                while ( $project_listings->have_posts() ) : $project_listings->the_post(); 
                    // Lấy các meta field tùy chỉnh của tin BĐS
                    $prop_id = get_the_ID();
                    $price = get_post_meta($prop_id, '_property_price_text', true) ?: 'Thỏa thuận';
                    $area = get_post_meta($prop_id, '_property_area', true);
                    $direction = get_post_meta($prop_id, '_property_direction', true);
                ?>
                    <div class="project-listing-item">
                        <div class="listing-item-content">
                            <h4 class="listing-item-title">
                                <?php // 4. Thêm rel="nofollow" và target="_blank" ?>
                                <a href="<?php the_permalink(); ?>" rel="nofollow" target="_blank">
                                    <?php the_title(); ?>
                                </a>
                            </h4>
                            <div class="listing-item-meta">
                                <?php // 5. Hiển thị các trường tùy chỉnh ?>
                                <span class="meta-item price"><?php echo esc_html($price); ?></span>
                                <?php if ( $area ) : ?>
                                <span class="meta-item area"><strong>DT:</strong> <?php echo esc_html($area); ?> m²</span>
                                <?php endif; ?>
                                <?php if ( $direction ) : ?>
                                <span class="meta-item direction"><strong>Hướng:</strong> <?php echo esc_html($direction); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="listing-item-action">
                            <a href="<?php the_permalink(); ?>" rel="nofollow" target="_blank" class="cta-link">
                                <span>Xem chi tiết</span>
                            </a>
                        </div>
                    </div>
                <?php 
                endwhile; 
                ?>
            </div>
        </section>
        <?php
        // 6. Khôi phục lại query gốc của trang Dự án
        wp_reset_postdata();
    }
}
// ======================================================
// KẾT THÚC KHỐI MỚI
// ======================================================
?>

<?php if (function_exists('tuancele_display_related_posts')) { tuancele_display_related_posts(); } ?>
<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
<?php
/**
 * =========================================================================
 * [KHÔI PHỤC V11 GỐC] LOGIC CHO THANH TIẾN TRÌNH ĐỌC
 * [FIX LỖI ANIMATION] Thêm kiểm tra $GLOBALS['has_toc']
 * =========================================================================
 */
// Chỉ in các script này nếu Mục lục (TOC) đã được tạo
if ( ! empty( $GLOBALS['has_toc'] ) && $GLOBALS['has_toc'] === true ) : 
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

<?php // [FIX V7] Thêm target="post-content-article" ?>
<amp-position-observer
    on="scroll:readingProgressAnimation.seekTo(percent=event.percent)"
    layout="nodisplay"
    target="post-content-article">
</amp-position-observer>
<?php 
endif; // Kết thúc kiểm tra $GLOBALS['has_toc']

get_footer(); 
?>