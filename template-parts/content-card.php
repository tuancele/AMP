<?php
/**
 * template-parts/content-card.php - Mẫu hiển thị nội dung dạng thẻ (card).
 * ĐÃ TỐI ƯU: Thêm fetchpriority cho LCP image.
 */

// Lấy biến cờ từ template cha, mặc định là false nếu không được truyền vào.
$is_lcp_image = get_query_var( 'is_lcp_image', false );
?>
<div class="post-card">
    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <div class="post-card-image-wrapper">
            <?php if (has_post_thumbnail()) : 
                $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                // Sử dụng kích thước ảnh tối ưu 'archive-thumb'
                $image_src = wp_get_attachment_image_src($thumbnail_id, 'archive-thumb'); 
                if ($image_src) :
            ?>
                <amp-img src="<?php echo esc_url($image_src[0]); ?>"
                         width="<?php echo esc_attr($image_src[1]); ?>"
                         height="<?php echo esc_attr($image_src[2]); ?>"
                         layout="responsive"
                         alt="<?php the_title_attribute(); ?>"
                         <?php 
                         // Thêm thuộc tính data-fetchpriority="high" nếu đây là ảnh LCP.
                         // Hàm is_paged() đảm bảo nó chỉ áp dụng cho trang đầu tiên của archive.
                         if ($is_lcp_image && !is_paged()) {
                             echo 'data-fetchpriority="high"';
                         } 
                         ?>>
                </amp-img>
            <?php endif; else: ?>
                <amp-img src="https://placehold.co/400x229/f0f4f8/48525c?text=<?php echo urlencode(get_the_title()); ?>" 
                         layout="responsive" 
                         width="400" 
                         height="229" 
                         alt="<?php the_title_attribute(); ?>"
                         <?php 
                         if ($is_lcp_image && !is_paged()) {
                             echo 'data-fetchpriority="high"';
                         } 
                         ?>>
                </amp-img>
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