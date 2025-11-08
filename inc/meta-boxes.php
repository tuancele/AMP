<?php
/**
 * inc/meta-boxes.php
 * Chứa logic để tạo các Meta Box tùy chỉnh cho theme.
 *
 * [NÂNG CẤP BĐS - PHIÊN BẢN HOÀN THIỆN 5.0 - SỬA LỖI 400 AJAX]
 * - (GĐ 1) Đã XÓA trường meta box "_property_map_id" khỏi CPT 'property'.
 * - (GĐ 3) Thêm dropdown "_property_hotspot_name" cho CPT 'property'.
 * - (GĐ 3.1) Thêm hàm AJAX `tuancele_ajax_load_project_hotspots`.
 * - (GĐ 3.1) Sửa lỗi 400 (Bad Request) bằng cách:
 * 1. Dùng `wp_localize_script` để truyền (Nonce, ajax_url, saved_name) một cách an toàn,
 * tránh bị plugin caching/gộp file làm hỏng.
 * 2. Đảm bảo tên 'action' trong JS ('load_project_hotspots') khớp 100%
 * với hook 'wp_ajax_load_project_hotspots'.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * =========================================================================
 * META BOX CHO GIAO DIỆN TRANG CHỦ BĐS (template-homepage-bds.php)
 * (Không thay đổi)
 * =========================================================================
 */

// 1. Đăng ký Meta Box
add_action('add_meta_boxes', 'bds_homepage_register_meta_box');
function bds_homepage_register_meta_box() {
    global $post;
    if ( ! empty( $post ) ) {
        $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
        if ( $page_template == 'template-homepage-bds.php' ) {
            add_meta_box(
                'bds_homepage_settings_box',
                'Cấu hình Giao diện Trang chủ BĐS',
                'bds_homepage_meta_box_callback',
                'page',
                'advanced',
                'high'
            );
        }
    }
}

// 2. Hàm render nội dung HTML cho Meta Box
function bds_homepage_meta_box_callback($post) {
    wp_nonce_field('bds_homepage_save_meta_data', 'bds_homepage_meta_nonce');
    $main_post_id   = get_post_meta($post->ID, '_silo_main_post_id', true);
    $project_cat_id = get_post_meta($post->ID, '_bds_project_category', true);
    $news_cat_id    = get_post_meta($post->ID, '_bds_news_category', true);
    $banner_img_id  = get_post_meta($post->ID, '_bds_banner_image_id', true);
    $banner_url     = get_post_meta($post->ID, '_bds_banner_url', true);
    ?>
    <style>
        .bds-meta-box-field { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .bds-meta-box-field:last-child { border-bottom: none; }
        .bds-meta-box-field label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 1.1em; }
        .bds-meta-box-field select, .bds-meta-box-field input[type="url"], .bds-meta-box-field input[type="text"] { width: 100%; max-width: 600px; }
        .bds-meta-box-field .description { font-style: italic; color: #666; margin-top: 5px; }
    </style>
    <div class="bds-meta-box-field">
        <label for="silo_main_post_id">1. Bài viết chính (Main Post/Page ID):</label>
        <input type="text" id="silo_main_post_id" name="_silo_main_post_id" value="<?php echo esc_attr($main_post_id); ?>" placeholder="Nhập ID của Page hoặc Post..."/>
        <p class="description">Nội dung của bài viết/trang này sẽ được hiển thị ở đầu trang.</p>
    </div>
    <div class="bds-meta-box-field">
        <label for="project_category">2. Chuyên mục "Dự án":</label>
        <?php wp_dropdown_categories([
            'show_option_none'  => '— Chọn một chuyên mục —',
            'option_none_value' => '0',
            'name'              => '_bds_project_category',
            'id'                => 'project_category',
            'selected'          => $project_cat_id,
            'hide_empty'        => 0,
            'class'             => ''
        ]); ?>
    </div>
    <div class="bds-meta-box-field">
        <label for="news_category">3. Chuyên mục "Tin tức":</label>
        <?php wp_dropdown_categories([
            'show_option_none'  => '— Chọn một chuyên mục —',
            'option_none_value' => '0',
            'name'              => '_bds_news_category',
            'id'                => 'news_category',
            'selected'          => $news_cat_id,
            'hide_empty'        => 0,
            'class'             => ''
        ]); ?>
    </div>
    <div class="bds-meta-box-field">
        <label>4. Banner Quảng cáo:</label>
        <?php $image_url = $banner_img_id ? wp_get_attachment_image_url($banner_img_id, 'medium') : ''; ?>
        <div class="homepage-image-uploader">
            <input type="hidden" name="_bds_banner_image_id" value="<?php echo esc_attr($banner_img_id); ?>">
            <div class="image-preview" style="margin-bottom: 10px; max-width: 300px; border: 1px solid #ddd; padding: 5px; background: #f0f0f1; <?php if (!$banner_img_id) echo 'display: none;'; ?>">
                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100%; height: auto;">
            </div>
            <button type="button" class="button button-primary upload-image-button">Chọn hoặc Tải lên Ảnh</button>
            <button type="button" class="button button-secondary remove-image-button" style="<?php if (!$banner_img_id) echo 'display: none;'; ?>">Xóa Ảnh</button>
        </div>
    </div>
    <div class="bds-meta-box-field">
        <label for="banner_url">5. URL liên kết của Banner:</label>
        <input type="url" id="banner_url" name="_bds_banner_url" value="<?php echo esc_attr($banner_url); ?>" placeholder="https://..." />
    </div>
    <?php
}

// 3. Hàm lưu dữ liệu từ Meta Box
add_action('save_post', 'bds_homepage_save_meta_data');
function bds_homepage_save_meta_data($post_id) {
    if (!isset($_POST['bds_homepage_meta_nonce']) || !wp_verify_nonce($_POST['bds_homepage_meta_nonce'], 'bds_homepage_save_meta_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'page' || !current_user_can('edit_page', $post_id)) return;
    $fields = ['_silo_main_post_id', '_bds_project_category', '_bds_news_category', '_bds_banner_image_id', '_bds_banner_url'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

// 4. Tải script cho Media Uploader (chỉ khi cần)
add_action('admin_enqueue_scripts', 'bds_homepage_enqueue_meta_box_scripts');
function bds_homepage_enqueue_meta_box_scripts($hook) {
    global $post;
    if ( ($hook == 'post-new.php' || $hook == 'post.php') && isset($post->post_type) && $post->post_type == 'page' ) {
        wp_enqueue_media();
        $script = <<<'JS'
        jQuery(document).ready(function($) {
            'use strict';
            function toggleMetaBox() {
                const template = $('#page_template').val();
                if (template === 'template-homepage-bds.php') {
                    $('#bds_homepage_settings_box').show();
                } else {
                    $('#bds_homepage_settings_box').hide();
                }
            }
            toggleMetaBox();
            $('#page_template').on('change', toggleMetaBox);
            $('.homepage-image-uploader').each(function() {
                const uploader = $(this);
                let mediaUploader;
                uploader.on('click', '.upload-image-button', function(e) {
                    e.preventDefault();
                    if (mediaUploader) { mediaUploader.open(); return; }
                    mediaUploader = wp.media({ title: 'Chọn ảnh banner', button: { text: 'Sử dụng ảnh này' }, multiple: false });
                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        uploader.find('input[type=hidden]').val(attachment.id);
                        const previewUrl = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                        uploader.find('.image-preview img').attr('src', previewUrl);
                        uploader.find('.image-preview, .remove-image-button').show();
                    });
                    mediaUploader.open();
                });
                uploader.on('click', '.remove-image-button', function(e) {
                    e.preventDefault();
                    uploader.find('input[type=hidden]').val('');
                    uploader.find('.image-preview img').attr('src', '');
                    uploader.find('.image-preview, .remove-image-button').hide();
                });
            });
        });
JS;
        wp_add_inline_script('jquery-core', $script);
    }
}


/**
 * =========================================================================
 * CÁC META BOX KHÁC (GIỮ NGUYÊN)
 * =========================================================================
 */
// (Code Meta Box 'Schema Entities' ... giữ nguyên)
add_action('add_meta_boxes', 'tuancele_add_entities_meta_box');
function tuancele_add_entities_meta_box() { add_meta_box( 'tuancele_schema_entities', 'Schema Entities (Mentions & About)', 'tuancele_render_entities_meta_box', ['post', 'page'], 'side', 'low' ); }
function tuancele_render_entities_meta_box($post) { wp_nonce_field('tuancele_save_entities_meta', 'tuancele_entities_nonce'); $entities = get_post_meta($post->ID, '_schema_entities', true); ?> <p>Nhập mỗi thực thể trên một dòng theo định dạng:</p> <p><code>Loại | Tên Thực thể | Mô tả ngắn</code></p> <textarea name="_schema_entities" style="width:100%; height: 200px;" placeholder="Ví dụ:&#10;Organization | Vinhomes | Chủ đầu tư..."><?php echo esc_textarea($entities); ?></textarea> <p class="description">Các loại hợp lệ: <strong>Organization, Place, Thing...</strong></p> <?php }
add_action('save_post', 'tuancele_save_entities_meta_data');
function tuancele_save_entities_meta_data($post_id) { if (!isset($_POST['tuancele_entities_nonce']) || !wp_verify_nonce($_POST['tuancele_entities_nonce'], 'tuancele_save_entities_meta')) return; if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post', $post_id)) return; if (isset($_POST['_schema_entities'])) { update_post_meta($post_id, '_schema_entities', sanitize_textarea_field($_POST['_schema_entities'])); } else { delete_post_meta($post_id, '_schema_entities'); } }

// (Code Meta Box 'Schema Service' ... giữ nguyên)
add_action('add_meta_boxes', 'tuancele_add_service_schema_meta_box');
function tuancele_add_service_schema_meta_box() { add_meta_box('tuancele_service_schema_details', 'Chi tiết Schema Dịch vụ', 'tuancele_render_service_schema_meta_box', 'service', 'advanced', 'high'); }
function tuancele_render_service_schema_meta_box($post) { wp_nonce_field('tuancele_save_service_schema_meta', 'tuancele_service_schema_nonce'); $service_type = get_post_meta($post->ID, '_service_type', true); $area_served = get_post_meta($post->ID, '_area_served', true); $low_price = get_post_meta($post->ID, '_low_price', true); $high_price = get_post_meta($post->ID, '_high_price', true); ?> <table class="form-table"> <tr valign="top"> <th scope="row"><label for="service_type">Loại hình Dịch vụ (serviceType)</label></th> <td><input type="text" id="service_type" name="_service_type" value="<?php echo esc_attr($service_type); ?>" class="regular-text" placeholder="Ví dụ: Dịch vụ cho thuê VPN"/></td> </tr> <tr valign="top"> <th scope="row"><label for="area_served">Khu vực Phục vụ (areaServed)</label></th> <td><input type="text" id="area_served" name="_area_served" value="<?php echo esc_attr($area_served ?: 'Vietnam'); ?>" class="regular-text" placeholder="Ví dụ: Vietnam, Hà Nội"/></td> </tr> <tr valign="top"> <th scope="row"><label for="low_price">Giá thấp nhất (lowPrice)</label></th> <td><input type="number" id="low_price" name="_low_price" value="<?php echo esc_attr($low_price); ?>" class="regular-text" placeholder="Ví dụ: 150000"/></td> </tr> <tr valign="top"> <th scope="row"><label for="high_price">Giá cao nhất (highPrice)</label></th> <td><input type="number" id="high_price" name="_high_price" value="<?php echo esc_attr($high_price); ?>" class="regular-text" placeholder="Ví dụ: 500000"/></td> </tr> </table> <?php }
add_action('save_post_service', 'tuancele_save_service_schema_meta_data');
function tuancele_save_service_schema_meta_data($post_id) { if (!isset($_POST['tuancele_service_schema_nonce']) || !wp_verify_nonce($_POST['tuancele_service_schema_nonce'], 'tuancele_save_service_schema_meta')) return; if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post', $post_id)) return; $fields = ['_service_type', '_area_served', '_low_price', '_high_price']; foreach ($fields as $field) { if (isset($_POST[$field])) { update_post_meta($post_id, $field, sanitize_text_field($_POST[$field])); } } }

// (Code Meta Box 'Category Silo' ... giữ nguyên)
add_action('category_edit_form_fields', 'tuancele_category_edit_form_fields', 10, 2);
function tuancele_category_edit_form_fields($term, $taxonomy) { $main_post_id = get_term_meta($term->term_id, '_silo_main_post_id', true); $child_cat_1 = get_term_meta($term->term_id, '_silo_child_cat_1', true); $child_cat_2 = get_term_meta($term->term_id, '_silo_child_cat_2', true); $banner_img_id = get_term_meta($term->term_id, '_silo_banner_id', true); $banner_url = get_term_meta($term->term_id, '_silo_banner_url', true); ?> <tr class="form-field"> <th colspan="2"> <h2 style="margin-top: 20px; margin-bottom: 0;">Cấu hình Layout Silo cho Chuyên mục</h2> <p class="description">Các cài đặt này sẽ thay thế giao diện danh sách bài viết mặc định của chuyên mục bằng một layout Silo tùy chỉnh.</p> </th> </tr> <tr class="form-field"> <th scope="row"><label for="_silo_main_post_id">Bài viết chính (Main Post/Page ID)</label></th> <td> <input type="text" name="_silo_main_post_id" id="_silo_main_post_id" value="<?php echo esc_attr($main_post_id); ?>" placeholder="Nhập ID của Page hoặc Post..."/> <p class="description">Bài viết/trang này sẽ hiển thị ở đầu chuyên mục (dưới dạng thẻ tóm tắt).</p> </td> </tr> <tr class="form-field"> <th scope="row"><label for="_silo_child_cat_1">Chuyên mục con cấp 1</label></th> <td> <?php wp_dropdown_categories(['show_option_none' => '— Chọn một chuyên mục —', 'option_none_value' => '0', 'name' => '_silo_child_cat_1', 'id' => '_silo_child_cat_1', 'selected' => $child_cat_1, 'hide_empty' => 0, 'class' => '']); ?> <p class="description">Hiển thị 6 bài viết từ chuyên mục này.</p> </td> </tr> <tr class="form-field"> <th scope="row"><label for="_silo_child_cat_2">Chuyên mục con cấp 2</label></th> <td> <?php wp_dropdown_categories(['show_option_none' => '— Chọn một chuyên mục —', 'option_none_value' => '0', 'name' => '_silo_child_cat_2', 'id' => '_silo_child_cat_2', 'selected' => $child_cat_2, 'hide_empty' => 0, 'class' => '']); ?> <p class="description">Hiển thị 3 bài viết từ chuyên mục này.</p> </td> </tr> <tr class="form-field"> <th scope="row"><label for="_silo_banner_id">Banner Quảng cáo (ID Ảnh)</label></th> <td> <input type="text" name="_silo_banner_id" id="_silo_banner_id" value="<?php echo esc_attr($banner_img_id); ?>" placeholder="Nhập ID của ảnh từ Media Library..."/> <p class="description">Để trống để không hiển thị banner.</p> </td> </tr> <tr class="form-field"> <th scope="row"><label for="_silo_banner_url">URL liên kết của Banner</label></th> <td> <input type="url" name="_silo_banner_url" id="_silo_banner_url" value="<?php echo esc_attr($banner_url); ?>" placeholder="https://..."/> </td> </tr> <?php }
add_action('edited_category', 'tuancele_save_category_custom_meta', 10, 2);
function tuancele_save_category_custom_meta($term_id, $tt_id) { $fields = ['_silo_main_post_id', '_silo_child_cat_1', '_silo_child_cat_2', '_silo_banner_id', '_silo_banner_url']; foreach ($fields as $field) { if (isset($_POST[$field])) { update_term_meta($term_id, $field, sanitize_text_field($_POST[$field])); } } }


/**
 * =========================================================================
 * META BOX CHO CPT BẤT ĐỘNG SẢN (PROPERTY)
 * [CẬP NHẬT GIAI ĐOẠN 1 & 3]
 * =========================================================================
 */
$integration_options_mb = get_option('tuancele_integrations_settings', []);
$is_property_enabled_mb = isset($integration_options_mb['enable_property_cpt']) && $integration_options_mb['enable_property_cpt'] === 'on';

if ($is_property_enabled_mb) {

// 1. Đăng ký Meta Box (Không đổi)
add_action('add_meta_boxes', 'property_register_meta_box');
function property_register_meta_box() {
    add_meta_box(
        'property_details_box',
        'Chi tiết Bất động sản (Tự động hiển thị)',
        'property_meta_box_callback',
        'property', 
        'advanced',
        'high'
    );
}

// 2. Hàm render nội dung HTML cho Meta Box (Đã xóa _property_map_id)
function property_meta_box_callback($post) {
    wp_nonce_field('property_save_meta_data', 'property_meta_nonce');
    $meta_values = get_post_meta($post->ID);
    $get_val = function($key) use ($meta_values) {
        return $meta_values[$key][0] ?? '';
    };
    ?>
    <style>
        .property-meta-box-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .property-meta-box-field { margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px; }
        .property-meta-box-field.full-width { grid-column: 1 / -1; }
        .property-meta-box-field label { font-weight: bold; display: block; margin-bottom: 5px; }
        .property-meta-box-field input, .property-meta-box-field select { width: 100%; }
        .property-meta-box-field .description { font-style: italic; color: #666; font-size: 12px; margin-top: 5px; }
        .section-title { font-size: 1.2em; color: #2271b1; border-bottom: 1px solid #c3c4c7; padding-bottom: 5px; margin: 15px 0 10px; grid-column: 1 / -1; }
        .image-gallery-preview img { cursor: move; }
        #hotspot-selector-wrapper { 
            background: #f0f4f8; 
            padding: 10px 15px; 
            border: 1px solid #c3c4c7; 
            border-radius: 4px; 
            margin-top: 15px; 
        }
    </style>
    
    <div class="property-meta-box-grid">
        <h3 class="section-title">Thông tin Hiển thị Cơ bản</h3>
        <div class="property-meta-box-field property-image-gallery-uploader full-width">
            <label>Ảnh Slider (Chọn nhiều ảnh)</label>
            <input type="hidden" id="_property_slider_ids" name="_property_slider_ids" value="<?php echo esc_attr($get_val('_property_slider_ids')); ?>"/>
            <div class="image-gallery-preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; background: #eee; padding: 10px; border-radius: 4px; min-height: 75px;">
                <?php
                $slider_ids_val = $get_val('_property_slider_ids');
                if (!empty($slider_ids_val)) {
                    $slider_ids_array = explode(',', $slider_ids_val);
                    foreach ($slider_ids_array as $image_id) {
                        $img_url = wp_get_attachment_image_url(intval($image_id), 'thumbnail');
                        if ($img_url) {
                            echo '<img src="' . esc_url($img_url) . '" style="width: 75px; height: 75px; object-fit: cover; border-radius: 3px;" data-id="' . esc_attr($image_id) . '">';
                        }
                    }
                }
                ?>
            </div>
            <p class="description" style="margin-top: 5px;">Kéo thả ảnh trong khung xem trước để sắp xếp lại thứ tự. Tỷ lệ kích thước 16:9 </p>
            <button type="button" class="button button-primary upload-gallery-button" style="margin-top: 10px;">Chọn/Chỉnh sửa Thư viện ảnh</button>
            <button type="button" class="button button-secondary remove-gallery-button" style="margin-top: 10px; <?php if (empty($slider_ids_val)) echo 'display: none;'; ?>">Xóa tất cả</button>
        </div>
        
        <?php // [XÓA GIAI ĐOẠN 1] Trường _property_map_id đã bị xóa ?>

        <div class="property-meta-box-field">
            <label for="_property_price_text">Giá hiển thị (Text)</label>
            <input type="text" id="_property_price_text" name="_property_price_text" value="<?php echo esc_attr($get_val('_property_price_text')); ?>" placeholder="Ví dụ: 2.5 Tỷ (hoặc Thỏa thuận)"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_area">Diện tích (m²)</label>
            <input type="number" step="0.1" id="_property_area" name="_property_area" value="<?php echo esc_attr($get_val('_property_area')); ?>" placeholder="Ví dụ: 60"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_bedrooms">Số phòng ngủ</label>
            <input type="number" step="1" id="_property_bedrooms" name="_property_bedrooms" value="<?php echo esc_attr($get_val('_property_bedrooms')); ?>" placeholder="Ví dụ: 2"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_bathrooms">Số phòng tắm</label>
            <input type="number" step="1" id="_property_bathrooms" name="_property_bathrooms" value="<?php echo esc_attr($get_val('_property_bathrooms')); ?>" placeholder="Ví dụ: 2"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_direction">Hướng</label>
            <input type="text" id="_property_direction" name="_property_direction" value="<?php echo esc_attr($get_val('_property_direction')); ?>" placeholder="Ví dụ: Đông Nam"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_legal">Pháp lý</label>
            <input type="text" id="_property_legal" name="_property_legal" value="<?php echo esc_attr($get_val('_property_legal')); ?>" placeholder="Ví dụ: Sổ hồng"/>
        </div>
        <h3 class="section-title">Thông tin dành cho Schema (Quan trọng cho SEO)</h3>
        <div class="property-meta-box-field">
            <label for="_property_price_value">Giá trị (Chỉ điền số)</label>
            <input type="number" step="0.01" id="_property_price_value" name="_property_price_value" value="<?php echo esc_attr($get_val('_property_price_value')); ?>" placeholder="Ví dụ: 2.5"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_price_unit">Đơn vị giá</label>
            <select id="_property_price_unit" name="_property_price_unit">
                <option value="Tỷ" <?php selected($get_val('_property_price_unit'), 'Tỷ'); ?>>Tỷ VNĐ</option>
                <option value="Triệu" <?php selected($get_val('_property_price_unit'), 'Triệu'); ?>>Triệu VNĐ</option>
            </select>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_street_address">Địa chỉ (Số nhà, Tên đường)</label>
            <input type="text" id="_property_street_address" name="_property_street_address" value="<?php echo esc_attr($get_val('_property_street_address')); ?>" placeholder="Ví dụ: 123 Nguyễn Lương Bằng"/>
        </div>
        <div class="property-meta-box-field">
            <label for="_property_address_locality">Quận / Huyện</label>
            <input type="text" id="_property_address_locality" name="_property_address_locality" value="<?php echo esc_attr($get_val('_property_address_locality')); ?>" placeholder="Ví dụ: Quận 9"/>
        </div>
        <div class="property-meta-box-field full-width">
            <label for="_property_address_region">Tỉnh / Thành phố</label>
            <input type="text" id="_property_address_region" name="_property_address_region" value="<?php echo esc_attr($get_val('_property_address_region')); ?>" placeholder="Ví dụ: TP. Hồ Chí Minh"/>
        </div>
    </div>
    <?php
}

// 3. Hàm lưu dữ liệu từ Meta Box (Cập nhật GĐ 3)
add_action('save_post_property', 'property_save_meta_data'); 
function property_save_meta_data($post_id) {
    if (!isset($_POST['property_meta_nonce']) || !wp_verify_nonce($_POST['property_meta_nonce'], 'property_save_meta_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'property' || !current_user_can('edit_post', $post_id)) return;

    $fields = [
        '_property_slider_ids',
        // [XÓA GIAI ĐOẠN 1] Đã xóa '_property_map_id'
        '_property_price_text',
        '_property_area',
        '_property_bedrooms',
        '_property_bathrooms',
        '_property_direction',
        '_property_legal',
        '_property_price_value',
        '_property_price_unit',
        '_property_street_address',
        '_property_address_locality',
        '_property_address_region',
        '_property_hotspot_name' // <-- [THÊM MỚI GIAI ĐOẠN 3]
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        } else {
            delete_post_meta($post_id, $field);
        }
    }
}

/**
 * =========================================================================
 * [SỬA LỖI 400 - TRIỆT ĐỂ]
 * Tải file JS ngoài và localize script để chống cache.
 * =========================================================================
 */
function property_admin_scripts($hook) {
    global $post;
    if ( ($hook == 'post-new.php' || $hook == 'post.php') && isset($post->post_type) && $post->post_type == 'property' ) {
        
        // 1. Tải các thư viện phụ thuộc
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');

        // 2. Đăng ký handle cho file JS mới của chúng ta
        $handle = 'tuancele-property-admin-js';
        
        // [SỬA LỖI] Đảm bảo file JS tồn tại trước khi enqueue
        // Tên file này phải khớp với file bạn đã tạo ở bước trước
        $js_file_path = get_template_directory() . '/assets/js/admin-property.js';
        $js_file_url = get_template_directory_uri() . '/assets/js/admin-property.js';
        
        if (file_exists($js_file_path)) {
            wp_enqueue_script(
                $handle,
                $js_file_url,
                ['jquery', 'jquery-ui-sortable'], // Phụ thuộc
                filemtime($js_file_path), // Tự động đổi version khi file thay đổi
                true  // Tải ở footer
            );

            // 3. Lấy dữ liệu động (Nonce và giá trị đã lưu)
            $saved_hotspot_name = get_post_meta( $post->ID, '_property_hotspot_name', true );
            $hotspot_nonce = wp_create_nonce( 'load_project_hotspots_nonce' ); // Tên Nonce
            
            // 4. Gắn dữ liệu động vào handle
            // Đây là cách an toàn nhất để truyền Nonce, chống cache
            wp_localize_script( $handle, 'tuancele_hotspot_data', [
                'saved_name' => $saved_hotspot_name,
                'nonce'      => $hotspot_nonce,
                'ajax_url'   => admin_url( 'admin-ajax.php' )
            ] );

        } else {
            // Fallback: Nếu file /assets/js/admin-property.js không tồn tại
            $debug_script = "console.error('Lỗi: Không tìm thấy file /assets/js/admin-property.js');";
            wp_add_inline_script('jquery-core', $debug_script, 'after');
        }
    }
}
// Kích hoạt hàm script
add_action('admin_enqueue_scripts', 'property_admin_scripts');
}

/**
 * =========================================================================
 * (MỚI) META BOX ĐÁNH DẤU "DỰ ÁN" CHO POST VÀ PAGE
 * (Không thay đổi)
 * =========================================================================
 */
// (Code Meta Box '_is_project' ... giữ nguyên)
add_action('add_meta_boxes', 'tuancele_add_is_project_metabox');
function tuancele_add_is_project_metabox() { add_meta_box( 'tuancele_is_project_box', 'Cấu hình Dự án', 'tuancele_render_is_project_metabox', ['post', 'page'], 'side', 'low' ); }
function tuancele_render_is_project_metabox($post) { wp_nonce_field('tuancele_save_is_project_meta', 'tuancele_is_project_nonce'); $is_project = get_post_meta($post->ID, '_is_project', true); ?> <label> <input type="checkbox" name="_is_project" value="1" <?php checked($is_project, '1'); ?>> <strong>Đây là một Dự án BĐS</strong> </label> <p class="description"> Tích vào đây nếu bài viết/trang này là trang chính của một dự án. </p> <?php }
add_action('save_post', 'tuancele_save_is_project_metabox');
function tuancele_save_is_project_metabox($post_id) { if (!isset($_POST['tuancele_is_project_nonce']) || !wp_verify_nonce($_POST['tuancele_is_project_nonce'], 'tuancele_save_is_project_meta')) return; if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post', $post_id)) return; if (wp_is_post_revision($post_id)) return; if (isset($_POST['_is_project'])) { update_post_meta($post_id, '_is_project', '1'); } else { delete_post_meta($post_id, '_is_project'); } }

/**
 * =========================================================================
 * (MỚI) META BOX CHỌN DỰ ÁN CHO CPT "PROPERTY"
 * (CẬP NHẬT GIAI ĐOẠN 3)
 * =========================================================================
 */
// (Code Meta Box '_project_id' ... giữ nguyên)
add_action('add_meta_boxes_property', 'tuancele_add_project_selection_metabox');
function tuancele_add_project_selection_metabox() { add_meta_box( 'tuancele_project_selection_box', 'Liên kết Dự án & Vị trí Căn', 'tuancele_render_project_selection_metabox', 'property', 'side', 'high' ); }
function tuancele_render_project_selection_metabox($post) { wp_nonce_field('tuancele_save_project_id_meta', 'tuancele_project_id_nonce'); $saved_project_id = get_post_meta($post->ID, '_project_id', true); $project_query_args = [ 'post_type' => ['post', 'page'], 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_is_project', 'meta_value' => '1', 'orderby' => 'title', 'order' => 'ASC', ]; $projects = get_posts($project_query_args); ?> <p>Chọn dự án mà tin rao này thuộc về:</p> <select name="_project_id" id="_project_id" style="width: 100%;"> <option value="">— Không thuộc dự án nào —</option> <?php if (!empty($projects)) { foreach ($projects as $project) { printf( '<option value="%s" %s>%s (%s)</option>', esc_attr($project->ID), selected($saved_project_id, $project->ID, false), esc_html($project->post_title), esc_html(ucfirst($project->post_type)) ); } } ?> </select> <?php $saved_hotspot_name = get_post_meta($post->ID, '_property_hotspot_name', true); ?> <div id="hotspot-selector-wrapper" style="margin-top: 15px; <?php echo empty($saved_project_id) ? 'display: none;' : ''; ?>"> <label for="_property_hotspot_name" style="font-weight: bold; display: block; margin-bottom: 5px;">Chọn Vị trí Căn (Hotspot):</label> <select id="_property_hotspot_name" name="_property_hotspot_name" style="width: 100%;"> <option value="">— Vui lòng chờ... —</option> <?php if (!empty($saved_hotspot_name)) { echo '<option value="' . esc_attr($saved_hotspot_name) . '" selected>' . esc_html($saved_hotspot_name) . ' (Đã lưu)</option>'; } ?> </select> <p class="description" style="margin-top: 5px;">Chọn căn hộ tương ứng trên mặt bằng.</p> </div> <?php }
add_action('save_post_property', 'tuancele_save_project_id_metabox');
function tuancele_save_project_id_metabox($post_id) { if (!isset($_POST['tuancele_project_id_nonce']) || !wp_verify_nonce($_POST['tuancele_project_id_nonce'], 'tuancele_save_project_id_meta')) return; if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post', $post_id)) return; if (isset($_POST['_project_id']) && !empty($_POST['_project_id'])) { update_post_meta($post_id, '_project_id', absint($_POST['_project_id'])); } else { delete_post_meta($post_id, '_project_id'); } }

/**
 * =========================================================================
 * [SỬA LỖI 400] HÀM AJAX (Không đổi)
 * =========================================================================
 */
add_action('wp_ajax_load_project_hotspots', 'tuancele_ajax_load_project_hotspots');

function tuancele_ajax_load_project_hotspots() {
    // 1. Kiểm tra Nonce (tên action, tên key)
    check_ajax_referer('load_project_hotspots_nonce', 'security'); 
    
    // 2. Kiểm tra Quyền
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Lỗi bảo mật (Quyền).'], 403);
    }

    $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;
    
    if ($project_id === 0) {
        wp_send_json_success(['hotspots' => [], 'map_id' => 0]);
    }

    // 3. Truy vấn tìm Image Map
    $image_map_query = new WP_Query([
        'post_type' => 'image_map',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_key' => '_im_project_id', // Field ta đã tạo ở Giai đoạn 2
        'meta_value' => $project_id,
        'fields' => 'ids' 
    ]);

    if (!$image_map_query->have_posts()) {
        wp_send_json_success(['hotspots' => [], 'map_id' => 0]); 
    }

    $map_id = $image_map_query->posts[0];
    $raw_data = get_post_meta($map_id, '_im_hotspot_data', true);
    
    // 4. Phân tích (parse) TỌA ĐỘ CSS (CSS Coordinates)
    // Điều này đảm bảo tên trong dropdown khớp với tên mà frontend shortcode dùng để so sánh
    preg_match_all(
        '/^(.+?):\s*left:\s*([\d.]+)\%;\s*top:\s*([\d.]+)\%;/im', 
        $raw_data,
        $matches
    );

    $hotspots = [];
    if (!empty($matches[1])) {
        foreach ($matches[1] as $name) {
            $hotspots[] = trim($name); // Lấy ra tên key từ CSS (ví dụ: can-A1)
        }
        $hotspots = array_unique($hotspots); 
        sort($hotspots); 
    }
    
    // 5. Trả về JSON
    wp_send_json_success(['hotspots' => $hotspots, 'map_id' => $map_id]);
}