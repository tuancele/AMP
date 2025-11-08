<?php
/**
 * category-silo.php
 * Template tùy chỉnh cho các chuyên mục được kích hoạt Silo.
 * PHIÊN BẢN 3.0: Giao diện tương tự Trang chủ Bất động sản.
 */

get_header();

// 1. Lấy thông tin Chuyên mục và dữ liệu Silo
$category = get_queried_object();
$pillar_id = get_term_meta( $category->term_id, 'silo_pillar_id', true );

// Thêm CSS tùy chỉnh cho trang Hub, tái sử dụng và định nghĩa các class cần thiết
?>
<style>
    .silo-hub-container { padding-top: 20px; }
    .homepage-section { margin-bottom: 40px; }
    .homepage-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
    .homepage-section-title { font-size: 1.8rem; margin: 0; color: var(--mau-chu); font-family: 'Poppins', sans-serif; }
    .homepage-view-all-btn { text-decoration: none; font-weight: 700; color: var(--mau-chinh); }
    .homepage-view-all-btn span { transition: transform 0.2s; display: inline-block; }
    .homepage-view-all-btn:hover span { transform: translateX(5px); }

    /* --- CSS Riêng cho Khối Pillar --- */
    .silo-pillar-card { display: flex; flex-direction: column; gap: 20px; align-items: center; text-align: center; background-color: #f8f9fa; padding: 30px 25px; border-radius: 12px; border: 1px solid #e9ecef; }
    .silo-pillar-card .image-wrapper { flex-shrink: 0; width: 100%; max-width: 300px; }
    .silo-pillar-card .image-wrapper a { display: block; line-height: 0; }
    .silo-pillar-card amp-img { border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .silo-pillar-card .content-wrapper { flex-grow: 1; }
    .silo-pillar-card h3 { margin: 0 0 10px 0; font-size: 1.6rem; }
    .silo-pillar-card h3 a { text-decoration: none; color: var(--mau-chu); }
    .silo-pillar-card .excerpt { margin: 0 0 20px 0; color: #333; font-size: 1rem; line-height: 1.7; }
    .silo-pillar-card .cta-button { display: inline-block; background-color: var(--mau-chu); color: #fff; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 700; transition: transform 0.2s; }
    /* Layout Desktop cho Pillar */
    @media (min-width: 768px) {
        .silo-pillar-card { flex-direction: row; text-align: left; gap: 30px; }
        .silo-pillar-card .image-wrapper { max-width: 250px; }
    }
</style>

<main class="container silo-hub-container">

    <?php 
    // 2. Hiển thị Trang Trụ cột (Pillar)
    if ( ! empty( $pillar_id ) ) :
        $pillar_post = get_post( $pillar_id );
        if ( $pillar_post ) :
            global $post;
            $post = $pillar_post;
            setup_postdata( $post );
            ?>
            <section class="homepage-section">
                <div class="homepage-section-header">
                    <h2 class="homepage-section-title">Nội dung Trụ cột (Pillar)</h2>
                </div>
                <div class="silo-pillar-card">
                    <?php if ( has_post_thumbnail() ) : ?>
                    <div class="image-wrapper">
                        <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                            <?php the_post_thumbnail( 'archive-thumb' ); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="content-wrapper">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="excerpt"><?php the_excerpt(); ?></div>
                        <a href="<?php the_permalink(); ?>" class="cta-button">Xem chi tiết →</a>
                    </div>
                </div>
            </section>
            <?php
            wp_reset_postdata();
        endif;
    endif; 
    ?>

    <?php
    // 3. Hiển thị các bài viết Mentor
    $mentor_query = new WP_Query([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'cat'            => $category->term_id,
        'meta_query'     => [ [ 'key' => '_silo_content_type', 'value' => 'mentor' ] ],
    ]);

    if ( $mentor_query->have_posts() ) : ?>
        <section class="homepage-section">
            <div class="homepage-section-header">
                <h2 class="homepage-section-title">Các Bài viết Hỗ trợ chính (Mentor)</h2>
                <?php // Nút "Xem tất cả" có thể được thêm ở đây nếu muốn ?>
            </div>
            <div class="posts-grid-container">
                <?php while ( $mentor_query->have_posts() ) : $mentor_query->the_post(); ?>
                    <?php get_template_part( 'template-parts/content-card' ); ?>
                <?php endwhile; ?>
            </div>
        </section>
        <?php
        wp_reset_postdata();
    endif;
    ?>

    <?php
    // 4. Hiển thị các bài viết Standard
    $standard_query = new WP_Query([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 6, // Hiển thị 6 bài mới nhất, giống layout BĐS
        'cat'            => $category->term_id,
        'meta_query'     => [
            'relation' => 'OR',
            [ 'key' => '_silo_content_type', 'value' => '' ],
            [ 'key' => '_silo_content_type', 'compare' => 'NOT EXISTS' ]
        ],
    ]);

    if ( $standard_query->have_posts() ) : ?>
        <section class="homepage-section">
            <div class="homepage-section-header">
                <h2 class="homepage-section-title">Các Bài viết Con (Standard)</h2>
                <?php // Nút xem tất cả các bài standard có thể là link tới trang archive của category ?>
                 <a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>" class="homepage-view-all-btn">Xem tất cả <span>→</span></a>
            </div>
            <div class="posts-grid-container">
                <?php while ( $standard_query->have_posts() ) : $standard_query->the_post(); ?>
                     <?php get_template_part( 'template-parts/content-card' ); ?>
                <?php endwhile; ?>
            </div>
        </section>
        <?php
        wp_reset_postdata();
    endif;
    ?>

</main>

<?php get_footer(); ?>