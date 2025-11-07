<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * Template Name: Sơ đồ Website
 * Description: Hiển thị sơ đồ website trực quan cho người dùng.
 *
 * [FIX V5] - Thêm truy vấn cho CPT 'qapage_question'.
 * [FIX V4] - Thêm 'post_status' => 'publish' cho CPT 'property'
 * để đảm bảo get_posts() tìm thấy CPT được đăng ký có điều kiện.
 */

get_header();

// Mảng này sẽ chứa các nhóm link (ví dụ: Trang, Bài viết, Chuyên mục)
$sitemap_groups = [];

// === 1. LẤY TRANG (PAGES) ===
$pages = get_pages([
    'sort_column' => 'post_title',
    'sort_order' => 'ASC',
    'exclude' => get_page_by_path('cam-on') ? get_page_by_path('cam-on')->ID : '' 
]);
if (!empty($pages)) {
    $sitemap_groups['Trang Tĩnh'] = $pages;
}

// === 2. LẤY BÀI VIẾT MỚI (POSTS) ===
$posts = get_posts([
    'post_type' => 'post',
    'posts_per_page' => 50,
    'orderby' => 'date',
    'order' => 'DESC',
    'post_status' => 'publish' // Thêm cho chắc chắn
]);
if (!empty($posts)) {
    $sitemap_groups['Bài viết mới'] = $posts;
}

// === 3. LẤY CHUYÊN MỤC (CATEGORIES) ===
$categories = get_categories([
    'orderby' => 'name',
    'order' => 'ASC',
    'hide_empty' => true
]);
if (!empty($categories)) {
    $sitemap_groups['Chuyên mục'] = $categories;
}

// === 4. LẤY CPT DỊCH VỤ (SERVICE) ===
$services = get_posts([
    'post_type' => 'service',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish' // Thêm cho chắc chắn
]);
if (!empty($services)) {
    $sitemap_groups['Dịch vụ'] = $services;
}

// === 5. LẤY CPT BẤT ĐỘNG SẢN (PROPERTY) ===
$integration_options = get_option('tuancele_integrations_settings', []);
$is_property_enabled = isset($integration_options['enable_property_cpt']) && $integration_options['enable_property_cpt'] === 'on';

if ($is_property_enabled) {
    $properties = get_posts([
        'post_type' => 'property',
        'posts_per_page' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish' // <-- [SỬA LỖI V4] Thêm dòng này
    ]);
    if (!empty($properties)) {
        $sitemap_groups['Bất động sản'] = $properties;
    }
}

// === 6. [THÊM MỚI] LẤY CPT HỎI & ĐÁP (QAPAGE) ===
$qapage_posts = get_posts([
    'post_type' => 'qapage_question',
    'posts_per_page' => 50, // Lấy 50 câu hỏi mới nhất
    'orderby' => 'date',
    'order' => 'DESC',
    'post_status' => 'publish'
]);
if (!empty($qapage_posts)) {
    $sitemap_groups['Hỏi & Đáp'] = $qapage_posts;
}

?>

<header class="page-header">
    <h1 class="page-title"><?php the_title(); ?></h1>
</header>

<?php
if (function_exists('tuancele_amp_display_breadcrumbs')) {
    tuancele_amp_display_breadcrumbs();
}
?>

<div class="sitemap-container">
    <?php if (empty($sitemap_groups)): ?>
        <p>Không tìm thấy nội dung để hiển thị sơ đồ website.</p>
    <?php else: ?>
        <div class="sitemap-grid">
            
            <?php foreach ($sitemap_groups as $title => $items): ?>
                <div class="sitemap-group">
                    <h2 class="sitemap-group-title"><?php echo esc_html($title); ?></h2>
                    <ul class="sitemap-list">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $url = '';
                            $name = '';
                            
                            if (isset($item->post_title)) {
                                $url = get_permalink($item->ID);
                                $name = $item->post_title;
                            } elseif (isset($item->name)) {
                                $url = get_term_link($item->term_id);
                                $name = $item->name . ' (' . $item->count . ' bài)';
                            }
                            ?>
                            
                            <li>
                                <h3>
                                    <a href="<?php echo esc_url($url); ?>" title="<?php echo esc_attr($name); ?>">
                                        <?php echo esc_html($name); ?>
                                    </a>
                                </h3>
                            </li>
                            
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();