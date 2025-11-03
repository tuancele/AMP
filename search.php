<?php
/**
 * search.php
 * Template cho trang hiển thị kết quả tìm kiếm.
 * Tái sử dụng layout grid của trang archive để đảm bảo tính nhất quán.
 */

get_header();
?>

<header class="page-header">
    <h1 class="page-title">
        <?php
        /* translators: %s: search query. */
        printf(
            esc_html__( 'Kết quả tìm kiếm cho: %s', 'amp-custom-theme' ),
            '<span class="search-query">' . get_search_query() . '</span>'
        );
        ?>
    </h1>
</header>

<?php // Hiển thị lại form tìm kiếm để người dùng có thể tìm kiếm lại dễ dàng ?>
<div class="search-results-form-container">
    <form role="search" method="get" class="search-results-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" target="_top">
        <input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Nhập từ khóa mới...', 'amp-custom-theme' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
        <button type="submit" class="search-submit">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M17.545 15.467l-3.358-3.358a6.5 6.5 0 10-2.121 2.121l3.358 3.358a1.5 1.5 0 002.121-2.121zM8.5 13a4.5 4.5 0 110-9 4.5 4.5 0 010 9z"/></svg>
        </button>
    </form>
</div>


<?php if ( have_posts() ) : ?>
    
    <?php // Tái sử dụng layout grid từ archive.php ?>
    <div class="posts-grid-container">
        <?php while ( have_posts() ) : the_post(); ?>
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
                                     alt="<?php the_title_attribute(); ?>">
                            </amp-img>
                        <?php endif; else: ?>
                            <amp-img src="https://placehold.co/400x229/f0f4f8/48525c?text=<?php echo urlencode(get_the_title()); ?>" layout="responsive" width="400" height="229" alt="<?php the_title_attribute(); ?>"></amp-img>
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
        <?php endwhile; ?>
    </div>

<?php 
    // Phân trang
    the_posts_pagination([
        'prev_text'          => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M12.45 15.55l-5-5 5-5L11.05 4.1 6 9.1l5.05 5.05 1.4-1.4z" fill="currentColor"/></svg><span>Trang trước</span>',
        'next_text'          => '<span>Trang sau</span><svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M7.55 4.45l5 5-5 5L8.95 15.9 14 10.9 8.95 5.85 7.55 4.45z" fill="currentColor"/></svg>',
        'screen_reader_text' => ' ', 
    ]);

else : ?>
    <div class="no-search-results">
        <p>Rất tiếc, không tìm thấy kết quả nào phù hợp với từ khóa của bạn.</p>
        <p>Vui lòng thử lại với một từ khóa khác.</p>
    </div>
<?php endif; ?>

<?php
get_footer();