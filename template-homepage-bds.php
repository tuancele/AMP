<?php
/**
 * Template Name: Trang chủ Bất động sản
 * ĐÃ SỬA LỖI: Loại bỏ thẻ <main class="container"> bị lặp, khắc phục lỗi double padding trên mobile.
 */

get_header();

// Lấy tất cả các giá trị meta từ trang cài đặt
$page_id = get_the_ID();
$main_post_id = get_post_meta($page_id, '_silo_main_post_id', true);
$project_cat_id = get_post_meta($page_id, '_bds_project_category', true);
$news_cat_id = get_post_meta($page_id, '_bds_news_category', true);
$banner_img_id = get_post_meta($page_id, '_bds_banner_image_id', true);
$banner_url = get_post_meta($page_id, '_bds_banner_url', true);

?>
<?php // [SỬA LỖI] Xóa thẻ <main> bị lặp, chỉ giữ lại class cho mục đích CSS ?>
<div class="homepage-bds-container">

    <?php
    // Hiển thị Bài viết chính dưới dạng thẻ "content-card"
    if ( ! empty($main_post_id) && is_numeric($main_post_id) ) :
        $main_post_args = [
            'p' => $main_post_id,
            'post_type' => 'any',
            'posts_per_page' => 1,
        ];
        $main_post_query = new WP_Query($main_post_args);

        if ($main_post_query->have_posts()) :
            ?>
            <section class="homepage-section main-post-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title">Bài viết chính</h2>
                </div>
                <div class="main-post-wrapper">
                    <?php
                    while ($main_post_query->have_posts()) : $main_post_query->the_post();
                        get_template_part('template-parts/content-card');
                    endwhile;
                    ?>
                </div>
            </section>
            <?php
            wp_reset_postdata();
        endif;
    endif;
    ?>

    <?php
    // Phần "Dự án Nổi bật"
    if ($project_cat_id > 0) :
        $project_args = [
            'cat' => $project_cat_id,
            'posts_per_page' => 6,
            'ignore_sticky_posts' => 1
        ];
        $project_query = new WP_Query($project_args);

        if ($project_query->have_posts()) : ?>
            <section class="homepage-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title"><?php echo esc_html(get_cat_name($project_cat_id) ?: 'Dự án Nổi bật'); ?></h2>
                    <a href="<?php echo esc_url(get_category_link($project_cat_id)); ?>" class="homepage-view-all-btn">Xem tất cả <span>→</span></a>
                </div>
                <div class="posts-grid-container">
                    <?php 
                    $post_counter = 0; 
                    while ($project_query->have_posts()) : $project_query->the_post();
                        set_query_var( 'is_lcp_image', (empty($main_post_id) && $post_counter === 0 && !is_paged()) );
                        get_template_part('template-parts/content-card');
                        $post_counter++;
                    endwhile; 
                    ?>
                </div>
            </section>
        <?php endif;
        wp_reset_postdata();
    endif;
    ?>

<?php
    // Phần Banner (ĐÃ TỐI ƯU LCP)
    if ( $banner_img_id > 0 ) :
        // Lấy ảnh gốc
        $banner_img_data = wp_get_attachment_image_src($banner_img_id, 'banner-main');
        // [MỚI] Lấy srcset (các kích thước ảnh con)
        $banner_srcset = wp_get_attachment_image_srcset($banner_img_id, 'banner-main');
        // [MỚI] Lấy sizes (quy tắc chọn ảnh)
        $banner_sizes = wp_get_attachment_image_sizes($banner_img_id, 'banner-main');

        if ($banner_img_data) : 
            $is_clickable = !empty($banner_url);
            $tag = $is_clickable ? 'a' : 'div';
            $tag_attributes = $is_clickable ? 'href="' . esc_url($banner_url) . '" target="_blank" rel="noopener"' : '';
    ?>
            <section class="homepage-section">
                <<?php echo $tag; ?> <?php echo $tag_attributes; ?> class="homepage-banner-ad">
                    <amp-img src="<?php echo esc_url($banner_img_data[0]); ?>"
                             width="<?php echo esc_attr($banner_img_data[1]); ?>"
                             height="<?php echo esc_attr($banner_img_data[2]); ?>"
                             /* [MỚI] Thêm srcset và sizes để mobile tải ảnh nhỏ hơn */
                             srcset="<?php echo esc_attr($banner_srcset); ?>"
                             sizes="<?php echo esc_attr($banner_sizes); ?>"
                             layout="responsive"
                             alt="Banner quảng cáo"
                             /* [MỚI] Báo cho trình duyệt ưu tiên tải ảnh này ngay lập tức */
                             data-fetchpriority="high">
                    </amp-img>
                </<?php echo $tag; ?>>
            </section>
        <?php endif;
    endif;
    ?>

    <?php
    // Phần "Tin tức"
    if ($news_cat_id > 0) :
        $news_args = [
            'cat' => $news_cat_id,
            'posts_per_page' => 3,
            'ignore_sticky_posts' => 1
        ];
        $news_query = new WP_Query($news_args);

        if ($news_query->have_posts()) : ?>
            <section class="homepage-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title"><?php echo esc_html(get_cat_name($news_cat_id) ?: 'Tin tức & Phân tích'); ?></h2>
                    <a href="<?php echo esc_url(get_category_link($news_cat_id)); ?>" class="homepage-view-all-btn">Xem tất cả <span>→</span></a>
                </div>
                <div class="posts-grid-container">
                    <?php while ($news_query->have_posts()) : $news_query->the_post();
                        set_query_var( 'is_lcp_image', false );
                        get_template_part('template-parts/content-card');
                    endwhile; ?>
                </div>
            </section>
        <?php endif;
        wp_reset_postdata();
    endif;
    ?>

</div> <?php // Đóng thẻ div.homepage-bds-container ?>
<?php
get_footer();