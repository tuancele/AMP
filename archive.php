<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * archive.php - Giao diện chung cho các trang lưu trữ.
 * ĐÃ NÂNG CẤP: Có khả năng hiển thị layout Silo tùy chỉnh cho chuyên mục.
 */
get_header();

// Lấy thông tin của chuyên mục hiện tại
$queried_object = get_queried_object();
$main_post_id = null;

if ( $queried_object instanceof WP_Term && $queried_object->taxonomy === 'category' ) {
    // Chỉ kiểm tra meta nếu đây là trang chuyên mục
    $main_post_id = get_term_meta($queried_object->term_id, '_silo_main_post_id', true);
}
?>

<header class="page-header">
    <?php
    the_archive_title('<h1 class="page-title">', '</h1>');
    the_archive_description('<div class="archive-description" style="margin-top: 15px;">', '</div>');
    ?>
</header>

<?php
if (function_exists('tuancele_amp_display_breadcrumbs')) {
    tuancele_amp_display_breadcrumbs();
}
?>

<?php
// ======================================================
// [LOGIC MỚI] KIỂM TRA VÀ CHỌN LAYOUT
// ======================================================

if ( ! empty($main_post_id) ) :
    
    // --- HIỂN THỊ LAYOUT SILO TÙY CHỈNH ---
    
    // Lấy meta và truyền vào template part
    $cat_id = $queried_object->term_id;
    set_query_var('silo_main_post_id', $main_post_id);
    set_query_var('silo_child_cat_1', get_term_meta($cat_id, '_silo_child_cat_1', true));
    set_query_var('silo_child_cat_2', get_term_meta($cat_id, '_silo_child_cat_2', true));
    set_query_var('silo_banner_id', get_term_meta($cat_id, '_silo_banner_id', true));
    set_query_var('silo_banner_url', get_term_meta($cat_id, '_silo_banner_url', true));
    
    get_template_part('template-parts/layout-silo');

else :
    
    // --- HIỂN THỊ LAYOUT ARCHIVE MẶC ĐỊNH (Code cũ của bạn) ---
    
    if (have_posts()) : ?>
        <div class="posts-grid-container">
            <?php 
            $post_counter = 0;
            while (have_posts()) : the_post(); 
            ?>
                <div class="post-card">
                    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                        <div class="post-card-image-wrapper">
                            <?php if (has_post_thumbnail()) : 
                                $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                                $image_src = wp_get_attachment_image_src($thumbnail_id, 'archive-thumb'); 
                                if ($image_src) :
                            ?>
                                <amp-img src="<?php echo esc_url($image_src[0]); ?>"
                                         width="<?php echo esc_attr($image_src[1]); ?>"
                                         height="<?php echo esc_attr($image_src[2]); ?>"
                                         layout="responsive"
                                         alt="<?php the_title_attribute(); ?>"
                                         <?php if ($post_counter === 0 && !is_paged()) echo 'data-fetchpriority="high"'; ?>>
                                </amp-img>
                            <?php endif; else: ?>
                                <amp-img src="https://placehold.co/400x229/f0f4f8/48525c?text=<?php echo urlencode(get_the_title()); ?>" layout="responsive" width="400" height="229" alt="<?php the_title_attribute(); ?>" <?php if ($post_counter === 0 && !is_paged()) echo 'data-fetchpriority="high"'; ?>></amp-img>
                            <?php endif; ?>
                        </div>
                        <div class="post-card-content">
                            <h4 class="post-card-title"><?php the_title(); ?></h4>
                            <span class="post-card-cta">
                                <span>Đọc thêm</span>
                                <svg width="18" height="18" viewBox="0 0 18 18"><path d="M9 3L7.94 4.06l4.19 4.19H3v1.5h9.13l-4.19 4.19L9 15l6-6z" fill="#005af0"/></svg>
                            </span>
                        </div>
                    </a>
                </div>
            <?php 
                $post_counter++;
            endwhile; 
            ?>
        </div>
    <?php else : ?>
        <article><p>Xin lỗi, không có bài viết nào được tìm thấy trong mục này.</p></article>
    <?php endif; ?>

    <?php
    // Phân trang cho layout mặc định
    the_posts_pagination([
        'prev_text'          => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M12.45 15.55l-5-5 5-5L11.05 4.1 6 9.1l5.05 5.05 1.4-1.4z" fill="currentColor"/></svg><span>Trang trước</span>',
        'next_text'          => '<span>Trang sau</span><svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M7.55 4.45l5 5-5 5L8.95 15.9 14 10.9 8.95 5.85 7.55 4.45z" fill="currentColor"/></svg>',
        'screen_reader_text' => ' ', 
        'type'               => 'list',
        'mid_size'           => 1,
        'end_size'           => 1,
    ]);

endif; // Kết thúc logic if/else chọn layout
?>

<?php get_footer(); ?>