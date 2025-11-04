<?php
/**
 * Template Name: Trang Hỗ Trợ AMP
 * Description: Template hiển thị các bài viết hỗ trợ từ chuyên mục 'support' với chức năng tìm kiếm.
 */

get_header(); 

// Lấy từ khóa tìm kiếm (nếu có)
$search_query = isset($_GET['sq']) ? sanitize_text_field($_GET['sq']) : '';
// [THÊM MỚI] Lấy số trang hiện tại
$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

// Cấu hình WP_Query
$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'category_name'  => 'support', // Lấy bài từ category 'support'
    'posts_per_page' => 6, // Hiển thị tất cả bài viết
    'orderby'        => 'title', // Sắp xếp theo tiêu đề
    'order'          => 'ASC',
];

// Thêm điều kiện tìm kiếm nếu có từ khóa
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

$support_query = new WP_Query($args);
?>

<div class="support-page-container">

    <?php /* === HERO SECTION === */ ?>
    <section class="support-hero">
        <div class="support-hero-content">
            <h1 class="support-hero-title">Trung Tâm Hỗ Trợ</h1>
            <p class="support-hero-description">Tìm kiếm giải pháp cho các vấn đề bạn gặp phải khi sử dụng dịch vụ.</p>
        </div>
    </section>

    <?php /* === SEARCH FORM === */ ?>
    <section class="support-search-section">
        <form role="search" method="get" class="support-search-form" target="_top" action="<?php echo esc_url( get_permalink() ); ?>">
             <label for="support-search-input" class="support-search-label">Tìm kiếm hỗ trợ:</label>
             <div class="support-search-wrapper">
                <input type="search"
                       id="support-search-input"
                       class="support-search-input"
                       placeholder="Nhập từ khóa vấn đề..."
                       value="<?php echo esc_attr($search_query); ?>"
                       name="sq" /> <?php // Sử dụng 'sq' để tránh xung đột với query 's' mặc định ?>
                <button type="submit" class="support-search-button">
                    <svg width="24" height="24" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" fill="currentColor"/></svg>
                </button>
             </div>
        </form>
    </section>

    <?php /* === SUPPORT ARTICLES GRID === */ ?>
    <section class="support-articles-grid">
        <?php if ($support_query->have_posts()) : ?>
            <div class="grid-container">
                <?php while ($support_query->have_posts()) : $support_query->the_post(); ?>
                    <div class="grid-item">
                        <a href="<?php the_permalink(); ?>" class="card-link">
                            <div class="card-icon">
                                <?php // Icon mặc định, bạn có thể thay đổi dựa trên post meta hoặc category con nếu muốn ?>
                                <svg width="24" height="24" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" fill="currentColor"/></svg>
                            </div>
                            <div class="card-content">
                                <h4 class="card-title"><?php the_title(); ?></h4>
                                <?php /* <p class="card-description"><?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?></p> */ ?>
                            </div>
                            <span class="card-arrow">
                               <svg width="18" height="18" viewBox="0 0 18 18"><path d="M9 3L7.94 4.06l4.19 4.19H3v1.5h9.13l-4.19 4.19L9 15l6-6z" fill="#005af0"/></svg>
                            </span>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php 
            // [DÁN CODE PHÂN TRANG VÀO ĐÂY]
            $big = 999999999;
            $pagination_links_args = array(
                'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'format'             => '?paged=%#%',
                'current'            => max( 1, $paged ),
                'total'              => $support_query->max_num_pages,
                'prev_text'          => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M12.45 15.55l-5-5 5-5L11.05 4.1 6 9.1l5.05 5.05 1.4-1.4z" fill="currentColor"/></svg><span>Trang trước</span>',
                'next_text'          => '<span>Trang sau</span><svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20"><path d="M7.55 4.45l5 5-5 5L8.95 15.9 14 10.9 8.95 5.85 7.55 4.45z" fill="currentColor"/></svg>',
                'screen_reader_text' => ' ',
                'type'               => 'list',
                'mid_size'           => 1,
                'end_size'           => 1,
            );
            
            // Thêm tham số 'sq' (tìm kiếm) vào link phân trang nếu có
            if ( ! empty( $search_query ) ) {
                $pagination_links_args['add_args'] = array( 'sq' => $search_query );
            }

            $pagination_links = paginate_links( $pagination_links_args );

            if ( $pagination_links ) {
                printf( '<nav class="pagination">%s</nav>', $pagination_links );
            }
        ?>
        <?php else : ?>
            <p class="no-results">Không tìm thấy bài viết hỗ trợ nào<?php echo $search_query ? ' cho từ khóa "' . esc_html($search_query) . '"' : ''; ?>.</p>
        <?php endif; ?>
        <?php wp_reset_postdata(); // Quan trọng: Khôi phục lại query gốc ?>
    </section>

</div> <?php // end .support-page-container ?>

<?php get_footer(); ?>