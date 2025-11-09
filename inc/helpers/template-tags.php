<?php
/**
 * inc/helpers/template-tags.php
 *
 * Chứa các hàm hiển thị (template tags) được gọi trực tiếp từ các tệp template
 * để render các thành phần giao diện như breadcrumbs, thời gian đọc, v.v.
 * Tệp này là một phần của quá trình tái cấu trúc từ template-helpers.php.
 *
 * [FIX] Thêm logic breadcrumbs cho CPT 'qapage_question'.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// TEMPLATE TAGS (UI HELPERS)
// =========================================================================

/**
 * Hiển thị thanh điều hướng breadcrumbs.
 * ĐÃ NÂNG CẤP: Hỗ trợ CPT 'property' (Bất động sản) và 'qapage_question' (Hỏi & Đáp).
 */
function tuancele_amp_display_breadcrumbs() {
    if ( is_front_page() ) return;

    echo '<nav aria-label="breadcrumb" class="breadcrumb-container"><ol class="breadcrumbs-list">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Trang Chủ</a></li>';

    if ( is_singular( 'post' ) ) {
        // --- Logic cho Bài viết (Post) ---
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            echo '<li><a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></li>';
        }
        echo '<li class="current-item">' . get_the_title() . '</li>';

    } elseif ( is_singular( 'property' ) ) {
        // --- [MỚI] Logic cho CPT Bất động sản (Property) ---
        $post_type = get_post_type_object( 'property' );
        if ( $post_type && $post_type->has_archive ) {
            // Lấy tên CPT (Bất động sản) và link archive (/bat-dong-san/)
            echo '<li><a href="' . esc_url( get_post_type_archive_link( 'property' ) ) . '">' . esc_html( $post_type->labels->name ) . '</a></li>';
        }
        echo '<li class="current-item">' . get_the_title() . '</li>';
    
    } elseif ( is_singular( 'qapage_question' ) ) {
        // --- [FIX] THÊM LOGIC CHO CPT HỎI & ĐÁP ---
        $post_type = get_post_type_object( 'qapage_question' );
        if ( $post_type && $post_type->has_archive ) {
            // Lấy tên CPT (Hỏi & Đáp) và link archive (/qapage/)
            echo '<li><a href="' . esc_url( get_post_type_archive_link( 'qapage_question' ) ) . '">' . esc_html( $post_type->labels->name ) . '</a></li>';
        }
        echo '<li class="current-item">' . get_the_title() . '</li>';
        
    } elseif ( is_page() ) {
        // --- Logic cho Trang (Page) ---
        echo '<li class="current-item">' . get_the_title() . '</li>';

    } elseif ( is_archive() ) { 
        // --- Logic cho Trang Lưu trữ (Archive) ---
        // Logic này đã đúng, tự động lấy tên CPT hoặc Category
        $archive_title = strip_tags( get_the_archive_title() ); 
        echo '<li class="current-item">' . esc_html( $archive_title ) . '</li>'; 
    }

    echo '</ol></nav>';
}

/**
 * Tính toán và trả về thời gian đọc bài viết ước tính.
 */
function tuancele_estimated_reading_time() {
    $word_count = str_word_count(strip_tags(get_post_field('post_content', get_the_ID())));
    return esc_html(max(1, ceil($word_count / 200))) . ' phút đọc';
}

/**
 * Hiển thị phần bài viết liên quan ở cuối bài viết.
 */
function tuancele_display_related_posts() {
    if ( ! is_single() ) return;
    $categories = get_the_category( get_the_ID() ); if ( empty( $categories ) ) return;
    $query_args = ['post_type' => 'post', 'category__in' => wp_list_pluck( $categories, 'term_id' ), 'post__not_in' => [get_the_ID()], 'posts_per_page' => 3, 'ignore_sticky_posts' => 1, 'orderby' => 'rand'];
    $related_query = new WP_Query( $query_args );
    if ( $related_query->have_posts() ) : ?>
        <section class="related-posts-section">
            <h2 class="related-posts-title">Bài viết liên quan</h2>
            <div class="posts-grid-container">
                <?php while ( $related_query->have_posts() ) : $related_query->the_post(); get_template_part('template-parts/content-card'); endwhile; ?>
            </div>
        </section>
    <?php endif; wp_reset_postdata();
}
/**
 * =========================================================================
 * [MỚI] HIỂN THỊ TIN BẤT ĐỘNG SẢN LIÊN QUAN (THEO DỰ ÁN HOẶC KHU VỰC)
 * =========================================================================
 */
function tuancele_display_related_properties() {
    // Chỉ chạy trên trang single 'property'
    if ( ! is_singular('property') ) {
        return;
    }

    global $post;
    $post_id = $post->ID;

    // 1. Lấy các giá trị meta để so sánh
    $project_id = get_post_meta( $post_id, '_project_id', true );
    $locality = get_post_meta( $post_id, '_property_address_locality', true );

    $query_args = [
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => 3, // Hiển thị 3 tin liên quan
        'post__not_in'   => [$post_id], // Loại trừ chính nó
        'orderby'        => 'rand', // Sắp xếp ngẫu nhiên
        'meta_query'     => [
            'relation' => 'OR', // Chỉ cần khớp 1 trong 2 điều kiện
        ],
    ];

    $has_query = false;

    // 2. Ưu tiên 1: Tìm các tin BĐS cùng Dự án
    if ( ! empty( $project_id ) ) {
        $query_args['meta_query'][] = [
            'key'     => '_project_id',
            'value'   => $project_id,
            'compare' => '=',
        ];
        $has_query = true;
    }

    // 3. Ưu tiên 2: Tìm các tin BĐS cùng Quận/Huyện
    if ( ! empty( $locality ) ) {
        $query_args['meta_query'][] = [
            'key'     => '_property_address_locality',
            'value'   => $locality,
            'compare' => '=',
        ];
        $has_query = true;
    }
    
    // 4. Nếu không có Project ID VÀ không có Quận/Huyện, không thể tìm tin liên quan
    if ( ! $has_query ) {
        return;
    }

    $related_query = new WP_Query( $query_args );

    if ( $related_query->have_posts() ) : 
    ?>
        <section class="related-posts-section">
            <h2 class="related-posts-title">Tin Bất động sản Liên quan</h2>
            <div class="posts-grid-container">
                <?php 
                while ( $related_query->have_posts() ) : $related_query->the_post();
                    // Tái sử dụng content card hoàn hảo
                    get_template_part('template-parts/content-card'); 
                endwhile; 
                ?>
            </div>
        </section>
    <?php 
    endif; 
    
    // 5. Khôi phục lại query gốc của trang
    wp_reset_postdata();
}