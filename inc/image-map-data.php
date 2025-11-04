<?php
/**
 * inc/image-map-data.php
 * Chứa logic Custom Post Type và Meta Box cho Image Map.
 *
 * [TỐI ƯU V8.3 - FIX LỖI INVALID POST TYPE]
 * - Thay đổi priority của hook 'init' thành 5 (chạy sớm hơn)
 * để đảm bảo CPT được đăng ký trước khi admin menu cần đến nó.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// 1. ĐĂNG KÝ CUSTOM POST TYPE: IMAGE MAP
// =========================================================================

/**
 * Đăng ký Custom Post Type 'image_map'.
 */
function tuancele_register_image_map_cpt() {
    $labels = [
        'name'                  => _x('Image Maps', 'Post Type General Name', 'tuancele-amp'),
        'singular_name'         => _x('Image Map', 'Post Type Singular Name', 'tuancele-amp'),
        'menu_name'             => __('Image Maps', 'tuancele-amp'),
        'name_admin_bar'        => __('Image Map', 'tuancele-amp'),
        'add_new'               => __('Thêm mới', 'tuancele-amp'),
        'add_new_item'          => __('Thêm Image Map mới', 'tuancele-amp'),
        'new_item'              => __('Image Map mới', 'tuancele-amp'),
        'edit_item'             => __('Chỉnh sửa Image Map', 'tuancele-amp'),
        'view_item'             => __('Xem Image Map', 'tuancele-amp'),
        'all_items'             => __('Tất cả Image Maps', 'tuancele-amp'),
        'search_items'          => __('Tìm Image Map', 'tuancele-amp'),
        'not_found'             => __('Không tìm thấy Image Map nào.', 'tuancele-amp'),
        'not_found_in_trash'    => __('Không tìm thấy Image Map nào trong thùng rác.', 'tuancele-amp'),
    ];
    $args = [
        'labels'                => $labels,
        'public'                => false, // Không hiển thị công khai trên website
        'show_ui'               => true, // Hiển thị trong admin
        // [FIX V8.2] Đặt thành false để thêm menu thủ công sau
        'show_in_menu'          => false, 
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'supports'              => ['title'], // Chỉ cần trường tiêu đề
        'rewrite'               => false, // Không cần rewrite rules
        'query_var'             => false, // Không cần query var
        'menu_icon'             => 'dashicons-location-alt', // Icon phù hợp
        'show_in_rest'          => false, // Không cần API REST
    ];
    register_post_type('image_map', $args);
}
// [FIX V8.3] Thay đổi priority từ 10 (mặc định) thành 5
add_action('init', 'tuancele_register_image_map_cpt', 5);

// =========================================================================
// 2. META BOX: CẤU HÌNH IMAGE MAP
// =========================================================================

/**
 * Thêm Meta Box vào CPT 'image_map'.
 */
function tuancele_add_image_map_meta_box() {
    add_meta_box(
        'tuancele_image_map_config', // ID của meta box
        __('Cấu hình Image Map', 'tuancele-amp'), // Tiêu đề meta box
        'tuancele_render_image_map_meta_box', // Hàm callback để render nội dung
        'image_map', // Tên CPT
        'normal', // Vị trí (normal, side, advanced)
        'high' // Ưu tiên (high, core, default, low)
    );
}
add_action('add_meta_boxes', 'tuancele_add_image_map_meta_box');

/**
 * Render nội dung cho Meta Box cấu hình Image Map.
 *
 * @param WP_Post $post Đối tượng bài viết hiện tại.
 */
function tuancele_render_image_map_meta_box($post) {
    // Thêm nonce field để bảo mật khi lưu
    wp_nonce_field('tuancele_image_map_save', 'tuancele_image_map_nonce');

    // Lấy dữ liệu meta đã lưu (nếu có)
    $image_id = get_post_meta($post->ID, '_im_image_id', true);
    $hotspot_data = get_post_meta($post->ID, '_im_hotspot_data', true);
    $mode = get_post_meta($post->ID, '_im_mode', true) ?: 'url'; // Mặc định là 'url'
    $hotspot_size = get_post_meta($post->ID, '_im_hotspot_size', true) ?: '25px'; // Mặc định 25px

    // Lấy URL ảnh để preview
    $img_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    $hide_class = $image_id ? '' : 'hidden'; // Class để ẩn/hiện nút xóa ảnh
    ?>
    <style>
        /* CSS cho giao diện Meta Box */
        .tuancele-im-meta-box .im-field { margin-bottom: 25px; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .tuancele-im-meta-box .im-field label { font-weight: 600; display: block; margin-bottom: 8px; color: #2c3338; }
        .tuancele-im-meta-box .im-field input[type=text],
        .tuancele-im-meta-box .im-field select,
        .tuancele-im-meta-box .im-field textarea { width: 100%; padding: 10px; border: 1px solid #8c8f94; border-radius: 3px; box-sizing: border-box; font-size: 14px; }
        .tuancele-im-meta-box .im-field textarea { min-height: 250px; font-family: monospace; white-space: pre; }
        .tuancele-im-meta-box .im-image-preview { margin-bottom: 10px; max-width: 300px; border: 1px solid #ddd; padding: 5px; background: #f0f0f1; }
        .tuancele-im-meta-box .im-image-preview img { max-width: 100%; height: auto; display: block; }
        .tuancele-im-meta-box .im-field-image-meta p { font-size: 12px; margin: 8px 0 0; color: #50575e; }
        .tuancele-im-meta-box .im-field-image-meta code { background: #e0e0e0; padding: 3px 5px; border-radius: 3px; font-family: monospace; }
        .tuancele-im-meta-box .hidden { display: none; }
        .tuancele-im-meta-box .button { margin-right: 5px; }
        .tuancele-im-meta-box .description { font-size: 13px; color: #50575e; font-style: italic; margin-top: 5px; }
        .tuancele-im-meta-box pre { background: #f7f7f7; padding: 15px; border-radius: 4px; border: 1px solid #ddd; overflow-x: auto; font-size: 12px; line-height: 1.6; }
    </style>

    <div class="tuancele-im-meta-box">
        <div class="im-field">
            <label for="_im_image_id">1. <?php _e('Chọn Ảnh Nền:', 'tuancele-amp'); ?></label>
            <input type="hidden" id="_im_image_id" name="_im_image_id" value="<?php echo esc_attr($image_id); ?>">

            <div class="im-image-preview">
                <img id="image-map-preview" src="<?php echo esc_url($img_url); ?>" class="<?php echo $hide_class; ?>" alt="<?php _e('Image Preview', 'tuancele-amp'); ?>">
            </div>

            <button type="button" class="button button-primary" id="upload-image-button"><?php _e('Chọn hoặc Tải lên Ảnh', 'tuancele-amp'); ?></button>
            <button type="button" class="button button-secondary <?php echo $hide_class; ?>" id="remove-image-button"><?php _e('Xóa Ảnh', 'tuancele-amp'); ?></button>

            <div class="im-field-image-meta">
                <p><?php _e('ID Ảnh hiện tại:', 'tuancele-amp'); ?> <code id="image-map-id-display"><?php echo $image_id ? esc_html($image_id) : 'N/A'; ?></code></p>
                <p><?php _e('Shortcode sử dụng:', 'tuancele-amp'); ?> <code>[amp_imagemap id="<?php echo $post->ID; ?>"]</code></p>
            </div>
        </div>

        <div class="im-field">
            <label for="_im_mode">2. <?php _e('Chế độ Tương tác:', 'tuancele-amp'); ?></label>
            <select id="_im_mode" name="_im_mode">
                <option value="url" <?php selected($mode, 'url'); ?>><?php _e('Chuyển hướng URL (URL Link)', 'tuancele-amp'); ?></option>
                <option value="popup" <?php selected($mode, 'popup'); ?>><?php _e('Hiển thị Popup (Lightbox)', 'tuancele-amp'); ?></option>
            </select>
            <p class="description"><?php _e("Chọn 'Popup' yêu cầu bạn điền Nội dung Popup cho từng Hotspot bên dưới.", 'tuancele-amp'); ?></p>
        </div>

        <div class="im-field">
            <label for="_im_hotspot_size">3. <?php _e('Kích thước Hotspot (Ví dụ: 25px, 3%):', 'tuancele-amp'); ?></label>
            <input type="text" id="_im_hotspot_size" name="_im_hotspot_size" value="<?php echo esc_attr($hotspot_size); ?>">
            <p class="description"><?php _e('Kích thước của các điểm tròn hiển thị trên ảnh.', 'tuancele-amp'); ?></p>
        </div>

        <div class="im-field">
            <label for="_im_hotspot_data">4. <?php _e('Dữ liệu Hotspot (Tọa độ CSS & Nội dung Shortcode):', 'tuancele-amp'); ?></label>
            
            <p class="description">
                <?php 
                // Lấy đường dẫn thư mục theme để tạo URL tuyệt đối
                $tool_url = get_template_directory_uri() . '/lay-toa-do.html';
                printf(
                    // Dùng printf để chèn link một cách an toàn và dễ đọc
                    wp_kses(
                        /* translators: %s is the URL to the coordinate tool. */
                        __('Sử dụng <a href="%s" target="_blank">Công cụ lấy tọa độ</a> để lấy tọa độ CSS, sau đó dán vào đây cùng với các thẻ <code>[hotspot]</code>. Tọa độ CSS phải luôn đặt trước nội dung <code>[hotspot]</code>.', 'tuancele-amp'),
                        [
                            'a' => ['href' => [], 'target' => []],
                            'code' => [],
                        ]
                    ),
                    esc_url($tool_url)
                );
                ?>
            </p>
            
            <textarea id="_im_hotspot_data" name="_im_hotspot_data"><?php echo esc_textarea($hotspot_data); ?></textarea>

            <p style="margin-top: 10px;">
                <button type="button" id="generate-hotspots-button" class="button button-secondary">
                    <span class="dashicons dashicons-admin-generic" style="vertical-align: text-top;"></span>
                    <?php _e('Tự động tạo Hotspot từ Tọa độ', 'tuancele-amp'); ?>
                </button>
            </p>

            <p><strong><?php _e('Cú pháp mẫu:', 'tuancele-amp'); ?></strong></p>
            <pre>
unit-01: left: 18.65%; top: 12.26%;
unit-02: left: 18.51%; top: 32.72%;
unit-vip: left: 50.00%; top: 50.00%;

[hotspot name="unit-01" url="/link-unit-01/"]
    &lt;p&gt;Nội dung HTML cho Unit 01 (Nếu Mode là Popup)&lt;/p&gt;
    &lt;strong&gt;Trạng thái:&lt;/strong&gt; Còn trống
[/hotspot]
[hotspot name="unit-02" url="/link-unit-02/"]
    &lt;p&gt;Nội dung HTML cho Unit 02&lt;/p&gt;
    &lt;img src="/path/to/image.jpg" alt="Ảnh Unit 02"&gt;
[/hotspot]
[hotspot name="unit-vip" url="#"]
    &lt;h4&gt;Căn hộ VIP&lt;/h4&gt;
    &lt;p&gt;Thông tin đặc biệt.&lt;/p&gt;
[/hotspot]
</pre>
            <p class="description"><?php _e('<strong>Quan trọng:</strong> Tên <code>unit-XX</code> trong tọa độ phải khớp chính xác với thuộc tính <code>name="..."</code> trong thẻ <code>[hotspot]</code>.', 'tuancele-amp'); ?></p>
        </div>
    </div>
    <?php
}

// =========================================================================
// 3. LƯU TRỮ DỮ LIỆU META BOX
// =========================================================================

/**
 * Lưu dữ liệu từ Meta Box khi bài viết 'image_map' được lưu.
 *
 * @param int $post_id ID của bài viết đang được lưu.
 */
function tuancele_save_image_map_meta_box($post_id) {
    // Kiểm tra nonce
    if (!isset($_POST['tuancele_image_map_nonce']) || !wp_verify_nonce($_POST['tuancele_image_map_nonce'], 'tuancele_image_map_save')) {
        return;
    }

    // Không lưu khi đang autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Kiểm tra quyền của user
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Chỉ lưu cho CPT 'image_map'
    if ('image_map' !== get_post_type($post_id)) {
        return;
    }

    // --- Lưu các trường ---

    // Lưu Image ID
    if (isset($_POST['_im_image_id'])) {
        update_post_meta($post_id, '_im_image_id', sanitize_text_field($_POST['_im_image_id']));
    } else {
        delete_post_meta($post_id, '_im_image_id');
    }

    // Lưu Hotspot Data
    if (isset($_POST['_im_hotspot_data'])) {
        $hotspot_content = wp_unslash($_POST['_im_hotspot_data']);
        // [FIX] Sử dụng sanitize_textarea_field để làm sạch dữ liệu, vô hiệu hóa HTML nguy hiểm
        update_post_meta($post_id, '_im_hotspot_data', sanitize_textarea_field($hotspot_content));
    } else {
        delete_post_meta($post_id, '_im_hotspot_data');
    }

    // Lưu Mode
    if (isset($_POST['_im_mode'])) {
        update_post_meta($post_id, '_im_mode', sanitize_key($_POST['_im_mode']));
    } else {
        delete_post_meta($post_id, '_im_mode');
    }

    // Lưu Hotspot Size
    if (isset($_POST['_im_hotspot_size'])) {
        update_post_meta($post_id, '_im_hotspot_size', sanitize_text_field($_POST['_im_hotspot_size']));
    } else {
        delete_post_meta($post_id, '_im_hotspot_size');
    }
}
add_action('save_post_image_map', 'tuancele_save_image_map_meta_box');

// =========================================================================
// 4. SCRIPTS MEDIA UPLOADER & HOTSPOT GENERATOR
// =========================================================================

/**
 * Tải các script cần thiết cho Meta Box.
 */
function tuancele_enqueue_image_map_scripts($hook) {
    // Chỉ tải script trên màn hình thêm mới hoặc chỉnh sửa CPT 'image_map'
    if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
        global $post;
        if ( is_object($post) && 'image_map' === $post->post_type ) {
            // Đảm bảo thư viện media của WordPress được tải
            wp_enqueue_media();

            // Chuẩn bị mã JavaScript
            $script_code = "
            jQuery(document).ready(function($) {
                'use strict';
                let mediaUploader; // Biến lưu trữ đối tượng media frame

                // Xử lý khi nhấn nút 'Chọn hoặc Tải lên Ảnh'
                $('#upload-image-button').on('click', function(e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media({
                        title: '" . esc_js(__('Chọn Ảnh Nền cho Image Map', 'tuancele-amp')) . "',
                        button: { text: '" . esc_js(__('Sử dụng ảnh này', 'tuancele-amp')) . "' },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#_im_image_id').val(attachment.id);
                        const previewUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                        $('#image-map-preview').attr('src', previewUrl).removeClass('hidden');
                        $('#remove-image-button').removeClass('hidden');
                        $('#image-map-id-display').text(attachment.id);
                    });
                    mediaUploader.open();
                });

                // Xử lý khi nhấn nút 'Xóa Ảnh'
                $('#remove-image-button').on('click', function(e) {
                    e.preventDefault();
                    $('#_im_image_id').val('');
                    $('#image-map-preview').attr('src', '').addClass('hidden');
                    $(this).addClass('hidden');
                    $('#image-map-id-display').text('N/A');
                });

                // Xử lý khi nhấn nút 'Tự động tạo Hotspot'
                $('#generate-hotspots-button').on('click', function(e) {
                    e.preventDefault();
                    
                    const textarea = $('#_im_hotspot_data');
                    let content = textarea.val();
                    
                    // Regex để tìm tất cả các dòng định nghĩa tọa độ (ví dụ: 'unit-01: left: ...')
                    const coordRegex = /^(unit-\d+|[a-z0-9-]+)\s*:/gim;
                    const matches = content.match(coordRegex);
                    
                    if (!matches || matches.length === 0) {
                        alert('" . esc_js(__('Không tìm thấy dòng tọa độ nào để tạo hotspot.', 'tuancele-amp')) . "');
                        return;
                    }
                    
                    let generatedShortcodes = '';
                    let newHotspotsCount = 0;

                    matches.forEach(function(match) {
                        // Lấy ra tên của hotspot (ví dụ: 'unit-01')
                        const name = match.replace(':', '').trim();
                        
                        // Kiểm tra xem hotspot này đã tồn tại hay chưa để tránh tạo trùng lặp
                        const shortcodeExistsRegex = new RegExp('\\\\[hotspot\\\\s+name=\\\"' + name + '\\\"', 'i');
                        if (!shortcodeExistsRegex.test(content)) {
                            generatedShortcodes += `[hotspot name=\"\${name}\" url=\"#\"]\\n    <p>Nội dung cho \${name}...</p>\\n[/hotspot]\\n`;
                            newHotspotsCount++;
                        }
                    });
                    
                    if (newHotspotsCount > 0) {
                        // Thêm hai dòng mới nếu nội dung đã có để phân tách rõ ràng
                        if (content.trim() !== '' && !content.endsWith('\\n\\n')) {
                            content += '\\n\\n';
                        }
                        textarea.val(content + generatedShortcodes);
                        alert(newHotspotsCount + ' " . esc_js(__('hotspot mới đã được tạo. Vui lòng điền nội dung và URL.', 'tuancele-amp')) . "');
                    } else {
                        alert('" . esc_js(__('Tất cả các tọa độ đã có hotspot tương ứng.', 'tuancele-amp')) . "');
                    }
                });
            });
            ";

            // Gắn script vào handle 'jquery-core' để đảm bảo jQuery đã được tải
            wp_add_inline_script('jquery-core', $script_code);
        }
    }
}
add_action('admin_enqueue_scripts', 'tuancele_enqueue_image_map_scripts');

?>