<?php
/**
 * template-parts/layout-silo.php
 *
 * Giao diện Silo tùy chỉnh cho các trang chuyên mục.
 * Được gọi bởi archive.php
 */

// Lấy các biến được truyền từ archive.php
$main_post_id   = get_query_var('silo_main_post_id');
$project_cat_id = get_query_var('silo_child_cat_1');
$news_cat_id    = get_query_var('silo_child_cat_2');
$banner_img_id  = get_query_var('silo_banner_id');
$banner_url     = get_query_var('silo_banner_url');
?>

<div class="homepage-bds-container"> <?php // Tái sử dụng class CSS của template BĐS ?>

    <?php
    // 1. Hiển thị Bài viết chính
    if ( ! empty($main_post_id) && is_numeric($main_post_id) ) :
        $main_post_args = ['p' => $main_post_id, 'post_type' => 'any', 'posts_per_page' => 1];
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
    // 2. Hiển thị Chuyên mục con cấp 1
    if ($project_cat_id > 0) :
        $project_args = ['cat' => $project_cat_id, 'posts_per_page' => 6, 'ignore_sticky_posts' => 1];
        $project_query = new WP_Query($project_args);

        if ($project_query->have_posts()) : ?>
            <section class="homepage-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title"><?php echo esc_html(get_cat_name($project_cat_id) ?: 'Chủ đề Phụ 1'); ?></h2>
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


// 3. Hiển thị Banner
    if ( $banner_img_id > 0 ) :
        $banner_img_data = wp_get_attachment_image_src($banner_img_id, 'banner-main');
        if ($banner_img_data) : 
            $is_clickable = !empty($banner_url);
            $tag = 'div'; // Vẫn giữ là div để tránh lỗi AMP Validation
            $url_wrap_start = $is_clickable ? '<a href="' . esc_url($banner_url) . '" target="_blank" rel="noopener" class="custom-banner-link">' : '';
            $url_wrap_end = $is_clickable ? '</a>' : '';
    ?>
            <section id="silo-banner-wrapper">
                <?php echo $url_wrap_start; ?>
                <div class="custom-banner-container">
                    <amp-img src="<?php echo esc_url($banner_img_data[0]); ?>"
                             width="<?php echo esc_attr($banner_img_data[1]); ?>"
                             height="<?php echo esc_attr($banner_img_data[2]); ?>"
                             layout="responsive"
                             alt="Banner quảng cáo">
                    </amp-img>
                </div>
                <?php echo $url_wrap_end; ?>
            </section>
        <?php endif;
    endif;
    ?>

    <?php
    // 4. Hiển thị Chuyên mục con cấp 2
    if ($news_cat_id > 0) :
        $news_args = ['cat' => $news_cat_id, 'posts_per_page' => 3, 'ignore_sticky_posts' => 1];
        $news_query = new WP_Query($news_args);

        if ($news_query->have_posts()) : ?>
            <section class="homepage-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title"><?php echo esc_html(get_cat_name($news_cat_id) ?: 'Chủ đề Phụ 2'); ?></h2>
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

</div>