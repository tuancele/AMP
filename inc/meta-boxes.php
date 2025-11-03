<?php
/**
 * inc/meta-boxes.php
 * Chứa logic để tạo các Meta Box tùy chỉnh cho theme.
 * ĐÃ NÂNG CẤP: Bổ sung trường "Bài viết chính" cho template Trang chủ BĐS mà không làm mất các tính năng cũ.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * =========================================================================
 * META BOX CHO GIAO DIỆN TRANG CHỦ BĐS
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

    // Lấy các giá trị đã lưu
    $main_post_id   = get_post_meta($post->ID, '_silo_main_post_id', true); // [THÊM MỚI]
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
    
    <?php // [THÊM MỚI] Trường nhập ID cho bài viết chính ?>
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

    // [THAY ĐỔI] Cập nhật danh sách các trường cần lưu
    $fields = [
        '_silo_main_post_id', // Thêm trường mới
        '_bds_project_category',
        '_bds_news_category',
        '_bds_banner_image_id',
        '_bds_banner_url'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        } else {
            // Chỉ xóa meta nếu trường đó không tồn tại trong POST request (ví dụ: checkbox không được chọn)
            // Đối với các trường text/select, nếu rỗng thì sẽ lưu giá trị rỗng, không xóa.
            // Điều này an toàn hơn.
        }
    }
}

// 4. Tải script cho Media Uploader (chỉ khi cần)
add_action('admin_enqueue_scripts', 'bds_homepage_enqueue_meta_box_scripts');
function bds_homepage_enqueue_meta_box_scripts($hook) {
    global $post;
    if ( ($hook == 'post-new.php' || $hook == 'post.php') && isset($post->post_type) && $post->post_type == 'page' ) {
        wp_enqueue_media();
        
        $script = "
        jQuery(document).ready(function($) {
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
        ";
        wp_add_inline_script('jquery-core', $script);
    }
}


/**
 * =========================================================================
 * CÁC META BOX KHÁC (GIỮ NGUYÊN)
 * =========================================================================
 */

// META BOX CHO SCHEMA ENTITIES (MENTIONS & ABOUT)
add_action('add_meta_boxes', 'tuancele_add_entities_meta_box');
function tuancele_add_entities_meta_box() {
    add_meta_box( 'tuancele_schema_entities', 'Schema Entities (Mentions & About)', 'tuancele_render_entities_meta_box', ['post', 'page'], 'side', 'low' );
}
function tuancele_render_entities_meta_box($post) {
    wp_nonce_field('tuancele_save_entities_meta', 'tuancele_entities_nonce');
    $entities = get_post_meta($post->ID, '_schema_entities', true);
    ?>
    <p>Nhập mỗi thực thể trên một dòng theo định dạng:</p>
    <p><code>Loại | Tên Thực thể | Mô tả ngắn</code></p>
    <textarea name="_schema_entities" style="width:100%; height: 200px;" placeholder="Ví dụ:&#10;Organization | Vinhomes | Chủ đầu tư..."><?php echo esc_textarea($entities); ?></textarea>
    <p class="description">Các loại hợp lệ: <strong>Organization, Place, Thing...</strong></p>
    <?php
}
add_action('save_post', 'tuancele_save_entities_meta_data');
function tuancele_save_entities_meta_data($post_id) {
    if (!isset($_POST['tuancele_entities_nonce']) || !wp_verify_nonce($_POST['tuancele_entities_nonce'], 'tuancele_save_entities_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['_schema_entities'])) {
        update_post_meta($post_id, '_schema_entities', sanitize_textarea_field($_POST['_schema_entities']));
    } else {
        delete_post_meta($post_id, '_schema_entities');
    }
}

// META BOX CHO SCHEMA SERVICE
add_action('add_meta_boxes', 'tuancele_add_service_schema_meta_box');
function tuancele_add_service_schema_meta_box() {
    add_meta_box('tuancele_service_schema_details', 'Chi tiết Schema Dịch vụ', 'tuancele_render_service_schema_meta_box', 'service', 'advanced', 'high');
}
function tuancele_render_service_schema_meta_box($post) {
    wp_nonce_field('tuancele_save_service_schema_meta', 'tuancele_service_schema_nonce');
    $service_type = get_post_meta($post->ID, '_service_type', true);
    $area_served = get_post_meta($post->ID, '_area_served', true);
    $low_price = get_post_meta($post->ID, '_low_price', true);
    $high_price = get_post_meta($post->ID, '_high_price', true);
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="service_type">Loại hình Dịch vụ (serviceType)</label></th>
            <td><input type="text" id="service_type" name="_service_type" value="<?php echo esc_attr($service_type); ?>" class="regular-text" placeholder="Ví dụ: Dịch vụ cho thuê VPN"/></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="area_served">Khu vực Phục vụ (areaServed)</label></th>
            <td><input type="text" id="area_served" name="_area_served" value="<?php echo esc_attr($area_served ?: 'Vietnam'); ?>" class="regular-text" placeholder="Ví dụ: Vietnam, Hà Nội"/></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="low_price">Giá thấp nhất (lowPrice)</label></th>
            <td><input type="number" id="low_price" name="_low_price" value="<?php echo esc_attr($low_price); ?>" class="regular-text" placeholder="Ví dụ: 150000"/></td>
        </tr>
         <tr valign="top">
            <th scope="row"><label for="high_price">Giá cao nhất (highPrice)</label></th>
            <td><input type="number" id="high_price" name="_high_price" value="<?php echo esc_attr($high_price); ?>" class="regular-text" placeholder="Ví dụ: 500000"/></td>
        </tr>
    </table>
    <?php
}
add_action('save_post_service', 'tuancele_save_service_schema_meta_data');
function tuancele_save_service_schema_meta_data($post_id) {
    if (!isset($_POST['tuancele_service_schema_nonce']) || !wp_verify_nonce($_POST['tuancele_service_schema_nonce'], 'tuancele_save_service_schema_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $fields = ['_service_type', '_area_served', '_low_price', '_high_price'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
/**
 * =========================================================================
 * META BOX TÙY CHỈNH CHO TRANG CHUYÊN MỤC (CATEGORY)
 * Biến trang Chuyên mục thành Layout Silo
 * =========================================================================
 */

// 1. Thêm các trường vào trang "Sửa Chuyên mục"
add_action('category_edit_form_fields', 'tuancele_category_edit_form_fields', 10, 2);
function tuancele_category_edit_form_fields($term, $taxonomy) {
    // Lấy dữ liệu đã lưu
    $main_post_id   = get_term_meta($term->term_id, '_silo_main_post_id', true);
    $child_cat_1    = get_term_meta($term->term_id, '_silo_child_cat_1', true);
    $child_cat_2    = get_term_meta($term->term_id, '_silo_child_cat_2', true);
    $banner_img_id  = get_term_meta($term->term_id, '_silo_banner_id', true);
    $banner_url     = get_term_meta($term->term_id, '_silo_banner_url', true);
    ?>
    <tr class="form-field">
        <th colspan="2">
            <h2 style="margin-top: 20px; margin-bottom: 0;">Cấu hình Layout Silo cho Chuyên mục</h2>
            <p class="description">Các cài đặt này sẽ thay thế giao diện danh sách bài viết mặc định của chuyên mục bằng một layout Silo tùy chỉnh.</p>
        </th>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="_silo_main_post_id">Bài viết chính (Main Post/Page ID)</label></th>
        <td>
            <input type="text" name="_silo_main_post_id" id="_silo_main_post_id" value="<?php echo esc_attr($main_post_id); ?>" placeholder="Nhập ID của Page hoặc Post..."/>
            <p class="description">Bài viết/trang này sẽ hiển thị ở đầu chuyên mục (dưới dạng thẻ tóm tắt).</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="_silo_child_cat_1">Chuyên mục con cấp 1</label></th>
        <td>
            <?php wp_dropdown_categories([
                'show_option_none'  => '— Chọn một chuyên mục —',
                'option_none_value' => '0',
                'name'              => '_silo_child_cat_1',
                'id'                => '_silo_child_cat_1',
                'selected'          => $child_cat_1,
                'hide_empty'        => 0,
                'class'             => ''
            ]); ?>
            <p class="description">Hiển thị 6 bài viết từ chuyên mục này.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="_silo_child_cat_2">Chuyên mục con cấp 2</label></th>
        <td>
            <?php wp_dropdown_categories([
                'show_option_none'  => '— Chọn một chuyên mục —',
                'option_none_value' => '0',
                'name'              => '_silo_child_cat_2',
                'id'                => '_silo_child_cat_2',
                'selected'          => $child_cat_2,
                'hide_empty'        => 0,
                'class'             => ''
            ]); ?>
            <p class="description">Hiển thị 3 bài viết từ chuyên mục này.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="_silo_banner_id">Banner Quảng cáo (ID Ảnh)</label></th>
        <td>
            <input type="text" name="_silo_banner_id" id="_silo_banner_id" value="<?php echo esc_attr($banner_img_id); ?>" placeholder="Nhập ID của ảnh từ Media Library..."/>
            <p class="description">Để trống để không hiển thị banner.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="_silo_banner_url">URL liên kết của Banner</label></th>
        <td>
            <input type="url" name="_silo_banner_url" id="_silo_banner_url" value="<?php echo esc_attr($banner_url); ?>" placeholder="https://..."/>
        </td>
    </tr>
    <?php
}

// 2. Lưu dữ liệu meta khi Sửa Chuyên mục
add_action('edited_category', 'tuancele_save_category_custom_meta', 10, 2);
function tuancele_save_category_custom_meta($term_id, $tt_id) {
    $fields = [
        '_silo_main_post_id',
        '_silo_child_cat_1',
        '_silo_child_cat_2',
        '_silo_banner_id',
        '_silo_banner_url'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_term_meta($term_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
?>

