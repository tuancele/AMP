<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * single-property.php
 * Template tự động hiển thị chi tiết Bất động sản
 *
 * [NÂNG CẤP BĐS - GIAI ĐOẠN 4]
 * - Đã XÓA logic lấy `_property_map_id` thủ công.
 * - Thêm logic mới: Tự động tìm `map_id` bằng cách truy vấn `image_map`
 * dựa trên `_project_id` của tin BĐS.
 * - Tự động lấy `_property_hotspot_name` (tên căn hộ) đã lưu.
 * - Truyền cả `map_id` và `highlight` (tên căn hộ) vào shortcode [amp_imagemap].
 *
 * [KHÔI PHỤC V11 GỐC]
 * - Khôi phục logic animation và position observer cho thanh tiến trình.
 */

get_header();
?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

    <?php 
    // [FIX] Hiển thị Breadcrumbs 1 LẦN DUY NHẤT
    if (function_exists('tuancele_amp_display_breadcrumbs')) { 
        tuancele_amp_display_breadcrumbs(); 
    } 
    ?>
    
    <?php 
    // Lấy tất cả các giá trị meta từ CPT
    $post_id = get_the_ID();
    $meta_values = get_post_meta($post_id);
    $get_val = function($key) use ($meta_values) {
        return $meta_values[$key][0] ?? '';
    };

    // 1. Dữ liệu cho Slider
    $slider_ids = $get_val('_property_slider_ids');

    // ==================================================
    // [LOGIC MỚI GIAI ĐOẠN 4]
    // ==================================================
    // 2. Lấy Project ID và Hotspot Name từ tin BĐS này
    $project_id = $get_val('_project_id'); // Lấy dự án mà tin này thuộc về
    $highlight_hotspot = $get_val('_property_hotspot_name'); // Lấy tên hotspot đã chọn (vd: "can-01")
    $map_id = 0; // Đặt map_id về 0
    
    // 3. Tìm Image Map tương ứng với Dự án
    if ( ! empty( $project_id ) ) {
        // Truy vấn để tìm Image Map tương ứng với Dự án này (logic từ Giai đoạn 2)
        $map_query = new WP_Query([
            'post_type' => 'image_map',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_key' => '_im_project_id', // Key của Image Map
            'meta_value' => $project_id,    // Phải khớp với Project ID của tin BĐS
            'fields' => 'ids' // Tối ưu, chỉ cần ID
        ]);
        if ( $map_query->have_posts() ) {
            $map_id = $map_query->posts[0]; // Lấy được map_id
        }
        wp_reset_postdata(); // Quan trọng: Reset query
    }
    // ==================================================
    // [KẾT THÚC LOGIC MỚI GIAI ĐOẠN 4]
    // ==================================================
    ?>
    
    <?php // [FIX LỖI ANIMATION] Thêm id vào thẻ article ?>
    <article id="post-content-article">
        <h1><?php the_title(); ?></h1>

        <?php // --- Thông tin Meta (Lấy từ single.php) --- ?>
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
        
        <?php 
        // --- HIỂN THỊ TỰ ĐỘNG ---

        // 1. Hiển thị Slider ảnh (Gọi shortcode)
        if ( ! empty( $slider_ids ) ) {
            echo do_shortcode( '[amp_slider ids="' . esc_attr( $slider_ids ) . '"]' );
        }
        
        // 2. Hiển thị Bảng thông số BĐS (Code HTML trực tiếp)
        $bds_data = [
            'gia'       => $get_val('_property_price_text') ?: 'Thỏa thuận',
            'dientich'  => $get_val('_property_area') ?: 'N/A',
            'phongngu'  => $get_val('_property_bedrooms') ?: 'N/A',
            'phongtam'  => $get_val('_property_bathrooms') ?: 'N/A',
            'huong'     => $get_val('_property_direction') ?: 'N/A',
            'phaply'    => $get_val('_property_legal') ?: 'N/A',
        ];
        ?>
        <div class="bds-details-box">
            <h3 class="bds-details-title">Thông số chi tiết</h3>
            <div class="bds-details-grid">
                <div class="bds-detail-item"><span class="bds-detail-label">Mức giá</span><span class="bds-detail-value price"><?php echo esc_html($bds_data['gia']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Diện tích</span><span class="bds-detail-value"><?php echo esc_html($bds_data['dientich']); ?> m²</span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Phòng ngủ</span><span class="bds-detail-value"><?php echo esc_html($bds_data['phongngu']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Phòng tắm</span><span class="bds-detail-value"><?php echo esc_html($bds_data['phongtam']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Hướng nhà</span><span class="bds-detail-value"><?php echo esc_html($bds_data['huong']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Pháp lý</span><span class="bds-detail-value"><?php echo esc_html($bds_data['phaply']); ?></span></div>
            </div>
        </div>
        <?php

        // 3. TỰ TẠO SCHEMA (Logic đã sửa lỗi)
        $price_val = floatval($get_val('_property_price_value'));
        if ( $price_val > 0 ) {
            $price_unit = $get_val('_property_price_unit') ?: 'Tỷ';
            $price_value = $price_val * ($price_unit === 'Tỷ' ? 1000000000 : 1000000);

            if ( ! isset($GLOBALS['page_specific_schema']) || !is_array($GLOBALS['page_specific_schema']) ) {
                $GLOBALS['page_specific_schema'] = [];
            }

            // [FIX] Thêm schema BĐS vào mảng @graph
            $GLOBALS['page_specific_schema'][] = [ 
                '@type' => 'RealEstateListing', 
                'name' => get_the_title($post_id), 
                'url' => get_permalink($post_id), 
                'description' => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words( get_post_field('post_content', $post_id), 55 ), 
                'image' => get_the_post_thumbnail_url($post_id, 'large') ?: false,
                'floorSize' => ['@type' => 'QuantitativeValue', 'value' => floatval($bds_data['dientich']), 'unitCode' => 'MTK'], 
                'numberOfBedrooms' => intval($bds_data['phongngu']), 
                'numberOfBathroomsTotal' => intval($bds_data['phongtam']), 
                'address' => [
                    '@type' => 'PostalAddress', 
                    'streetAddress' => $get_val('_property_street_address'), 
                    'addressLocality' => $get_val('_property_address_locality'), 
                    'addressRegion' => $get_val('_property_address_region'), 
                    'addressCountry' => 'VN'
                ], 
                'offers' => ['@type' => 'Offer', 'price' => $price_value, 'priceCurrency' => 'VND'] 
            ];
        }
        ?>

        <?php // 4. Hiển thị Nội dung mô tả (do người dùng nhập) ?>
        <div class="content"><?php the_content(); ?></div>
        
        <?php
        // ==================================================
        // [CẬP NHẬT GIAI ĐOẠN 4]
        // ==================================================
        // 5. Hiển thị Mặt bằng (nếu $map_id được tìm thấy)
        if ( $map_id > 0 ) {
            echo '<h2>Mặt bằng chi tiết</h2>';
            // Truyền cả map_id VÀ tên hotspot cần highlight vào shortcode
            echo do_shortcode( 
                '[amp_imagemap id="' . esc_attr( $map_id ) . '" highlight="' . esc_attr( $highlight_hotspot ) . '"]' 
            );
        }
        // ==================================================
        // [KẾT THÚC CẬP NHẬT]
        // ==================================================
        
        // 6. Hiển thị Form đăng ký
        echo '<h2>Đăng ký nhận tư vấn</h2>';
        echo do_shortcode( '[form_dang_ky tieu_de="Nhận báo giá & Ưu đãi mới nhất" nut_gui="Gửi thông tin ngay"]' );
        
        ?>
        
    </article>
<?php endwhile; else : ?>
    <p>No content found.</p>
<?php endif; ?>

<?php 
// [ĐÃ SỬA] Gọi hàm mới cho Tin BĐS liên quan
if (function_exists('tuancele_display_related_properties')) { 
    tuancele_display_related_properties(); 
} 
?>
<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>

<?php
/**
 * =========================================================================
 * [KHÔI PHỤC V11 GỐC] LOGIC CHO THANH TIẾN TRÌNH ĐỌC
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

<?php // Thêm target="post-content-article" để theo dõi thẻ <article> ?>
<amp-position-observer
    on="scroll:readingProgressAnimation.seekTo(percent=event.percent)"
    layout="nodisplay"
    target="post-content-article">
</amp-position-observer>
<?php 
endif; // Kết thúc kiểm tra $GLOBALS['has_toc']

get_footer(); 
?>