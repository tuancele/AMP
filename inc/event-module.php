<?php
/**
 * inc/event-module.php
 * Module Class quản lý Custom Post Type 'event' và các meta box liên quan.
 *
 * [TỐI ƯU V8.3 - FIX LỖI INVALID POST TYPE]
 * - Thay đổi priority của hook 'init' thành 5 (chạy sớm hơn)
 * để đảm bảo CPT được đăng ký trước khi admin menu cần đến nó.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AMP_Event_Module {

    /**
     * Khởi tạo module, đăng ký các hook.
     */
    public function __construct() {
        // [FIX V8.3] Thay đổi priority từ 10 (mặc định) thành 5
        add_action('init', [ $this, 'register_event_cpt' ], 5);

        // Đăng ký Meta Box
        add_action('add_meta_boxes', [ $this, 'add_event_meta_box' ]);
        
        // Lưu Meta Box
        add_action('save_post_event', [ $this, 'save_event_meta_data' ]);

        // Tải script admin
        add_action('admin_enqueue_scripts', [ $this, 'admin_scripts' ]);
    }

    /**
     * 1. ĐĂNG KÝ CUSTOM POST TYPE: SỰ KIỆN (EVENT)
     *
     */
    public function register_event_cpt() {
        $labels = [
            'name'                  => _x('Sự kiện', 'Post Type General Name', 'tuancele-amp'),
            'singular_name'         => _x('Sự kiện', 'Post Type Singular Name', 'tuancele-amp'),
            'menu_name'             => __('Sự kiện', 'tuancele-amp'),
            'add_new'               => __('Thêm mới', 'tuancele-amp'),
            'add_new_item'          => __('Thêm Sự kiện mới', 'tuancele-amp'),
            'edit_item'             => __('Chỉnh sửa Sự kiện', 'tuancele-amp'),
            'all_items'             => __('Tất cả Sự kiện', 'tuancele-amp'),
        ];
        $args = [
            'labels'                => $labels, 
            'public'                => false, 
            'show_ui'               => true, 
            // [V8.2] Đặt thành false để thêm menu thủ công sau
            'show_in_menu'          => false, 
            'capability_type'       => 'post', 
            'hierarchical'          => false, 
            'supports'              => ['title'],
            'rewrite'               => false, 
            'query_var'             => false, 
            'menu_icon'             => 'dashicons-calendar-alt', 
            'show_in_rest'          => false,
        ];
        register_post_type('event', $args);
    }

    /**
     * 2. META BOX: CẤU HÌNH CHI TIẾT SỰ KIỆN
     *
     */
    public function add_event_meta_box() {
        add_meta_box('tuancele_event_details', __('Chi tiết Sự kiện', 'tuancele-amp'), [ $this, 'render_event_meta_box_callback' ], 'event', 'normal', 'high');
    }

    public function render_event_meta_box_callback($post) {
        wp_nonce_field('tuancele_event_save_meta', 'tuancele_event_nonce');
        
        $meta = get_post_meta($post->ID);
        $fields = [
            'icon' => $meta['_event_icon'][0] ?? '🚀',
            'description' => $meta['_event_description'][0] ?? '',
            'url' => $meta['_event_url'][0] ?? '',
            'image_id' => $meta['_event_image_id'][0] ?? '',
            'start_date' => $meta['_event_start_date'][0] ?? '',
            'end_date' => $meta['_event_end_date'][0] ?? '',
            'organizer_name' => $meta['_event_organizer_name'][0] ?? '',
            'performer_name' => $meta['_event_performer_name'][0] ?? '',
            'price' => $meta['_event_price'][0] ?? '0',
            'currency' => $meta['_event_currency'][0] ?? 'VND',
            'offer_availability' => $meta['_event_offer_availability'][0] ?? 'https://schema.org/InStock',
            'offer_valid_from' => $meta['_event_offer_valid_from'][0] ?? '',
            'location_type' => $meta['_event_location_type'][0] ?? 'virtual',
            'location_name' => $meta['_event_location_name'][0] ?? '',
            'location_address' => $meta['_event_location_address'][0] ?? '',
        ];
        $image_url = $fields['image_id'] ? wp_get_attachment_image_url($fields['image_id'], 'thumbnail') : '';
        ?>
        <style>
            .event-meta-box .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;}
            .event-meta-box .field { margin-bottom: 10px; }
            .event-meta-box .field.full-width { grid-column: 1 / -1; }
            .event-meta-box label { font-weight: bold; display: block; margin-bottom: 5px; }
            .event-meta-box input, .event-meta-box select { width: 100%; padding: 8px; }
            .event-meta-box .description { font-style: italic; color: #666; font-size: 13px; margin-top: 5px; }
            .image-preview-wrapper img { max-width: 150px; height: auto; border: 1px solid #ddd; padding: 3px; margin-bottom: 5px; }
            .event-meta-box h3 { margin-top: 25px; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        </style>
        <div class="event-meta-box">
            <h3>Thông tin hiển thị trên thanh thông báo</h3>
            <div class="grid">
                <div class="field">
                    <label for="event_icon">Icon Hiển thị</label>
                    <input type="text" id="event_icon" name="_event_icon" value="<?php echo esc_attr($fields['icon']); ?>" placeholder="🚀">
                    <p class="description">Emoji sẽ hiển thị trước thông báo. Ví dụ: 🚀, 🎁, 📣...</p>
                </div>
                <div class="field full-width">
                    <label for="event_description">Nội dung Thông báo Ngắn gọn</label>
                    <input type="text" id="event_description" name="_event_description" value="<?php echo esc_attr($fields['description']); ?>" placeholder="Ví dụ: Chỉ còn 10 chỗ. Đăng ký ngay!">
                    <p class="description">Đây là nội dung sẽ chạy trên thanh thông báo. Nên giữ ngắn gọn.</p>
                </div>
            </div>
            
            <h3>Thông tin Schema & Chi tiết Sự kiện</h3>
            <div class="grid">
                <div class="field">
                    <label for="event_start_date">Ngày Giờ Bắt đầu</label>
                    <input type="text" name="_event_start_date" value="<?php echo esc_attr($fields['start_date']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                    <p class="description">Định dạng chuẩn: <code>YYYY-MM-DD HH:MM:SS</code>. Quan trọng cho Schema.</p>
                </div>
                <div class="field">
                    <label for="event_end_date">Ngày Giờ Kết thúc (Tùy chọn)</label>
                    <input type="text" name="_event_end_date" value="<?php echo esc_attr($fields['end_date']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                    <p class="description">Để trống nếu không có thời gian kết thúc cụ thể.</p>
                </div>
                <div class="field">
                    <label for="event_organizer_name">Tên nhà tổ chức (Tùy chọn)</label>
                    <input type="text" name="_event_organizer_name" value="<?php echo esc_attr($fields['organizer_name']); ?>" placeholder="Mặc định là tên website">
                    <p class="description">Để trống sẽ tự động lấy tên của website này.</p>
                </div>
                <div class="field">
                    <label for="event_performer_name">Tên diễn giả/nghệ sĩ (Tùy chọn)</label>
                    <input type="text" name="_event_performer_name" value="<?php echo esc_attr($fields['performer_name']); ?>">
                    <p class="description">Điền tên diễn giả, nghệ sĩ, hoặc người biểu diễn chính.</p>
                </div>
                <div class="field full-width">
                    <label>Ảnh đại diện Sự kiện (Tùy chọn)</label>
                    <div class="image-preview-wrapper" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
                        <img id="event-image-preview" src="<?php echo esc_url($image_url); ?>">
                    </div>
                    <input type="hidden" id="event_image_id" name="_event_image_id" value="<?php echo esc_attr($fields['image_id']); ?>">
                    <button type="button" class="button" id="upload-event-image">Chọn ảnh</button>
                    <button type="button" class="button" id="remove-event-image" style="<?php echo $image_url ? '' : 'display:none;'; ?>">Xóa ảnh</button>
                    <p class="description">Ảnh này sẽ được dùng trong Schema <code>Event</code> và có thể được hiển thị bởi Google.</p>
                </div>
            </div>
            
            <h3>Thông tin Ưu đãi (Offer)</h3>
            <div class="grid">
                <div class="field">
                    <label for="event_price">Giá vé / Phí tham dự</label>
                    <input type="number" name="_event_price" value="<?php echo esc_attr($fields['price']); ?>">
                    <p class="description">Nhập <code>0</code> nếu là sự kiện miễn phí.</p>
                </div>
                <div class="field">
                    <label for="event_currency">Loại tiền tệ</label>
                    <input type="text" name="_event_currency" value="<?php echo esc_attr($fields['currency']); ?>">
                    <p class="description">Mặc định là <code>VND</code>.</p>
                </div>
                <div class="field">
                    <label for="event_offer_availability">Tình trạng vé (Availability)</label>
                    <select name="_event_offer_availability">
                        <option value="https://schema.org/InStock" <?php selected($fields['offer_availability'], 'https://schema.org/InStock'); ?>>Còn hàng/vé</option>
                        <option value="https://schema.org/SoldOut" <?php selected($fields['offer_availability'], 'https://schema.org/SoldOut'); ?>>Hết hàng/vé</option>
                        <option value="https://schema.org/PreOrder" <?php selected($fields['offer_availability'], 'https://schema.org/PreOrder'); ?>>Đặt trước</option>
                    </select>
                    <p class="description">Trạng thái của vé hoặc ưu đãi.</p>
                </div>
                <div class="field">
                    <label for="event_offer_valid_from">Ưu đãi có hiệu lực từ ngày (Tùy chọn)</label>
                    <input type="date" name="_event_offer_valid_from" value="<?php echo esc_attr($fields['offer_valid_from']); ?>">
                    <p class="description">Để trống nếu ưu đãi có hiệu lực ngay lập tức.</p>
                </div>
                 <div class="field full-width">
                    <label for="event_url">URL Sự kiện / Đặt vé</label>
                    <input type="url" name="_event_url" value="<?php echo esc_url($fields['url']); ?>" placeholder="https://...">
                    <p class="description">Link chi tiết sự kiện hoặc trang đặt vé. Rất quan trọng cho cả Schema và thanh thông báo.</p>
                </div>
            </div>

            <h3>Địa điểm</h3>
            <div class="grid">
                <div class="field">
                    <label for="event_location_type">Loại Địa điểm</label>
                    <select id="event_location_type" name="_event_location_type">
                        <option value="virtual" <?php selected($fields['location_type'], 'virtual'); ?>>Online / Virtual</option>
                        <option value="physical" <?php selected($fields['location_type'], 'physical'); ?>>Địa điểm Cụ thể</option>
                    </select>
                </div>
                <div id="physical_location_fields" style="display:none;" class="full-width">
                    <div class="field">
                        <label for="event_location_name">Tên Địa điểm</label>
                        <input type="text" name="_event_location_name" value="<?php echo esc_attr($fields['location_name']); ?>">
                    </div>
                    <div class="field">
                        <label for="event_location_address">Địa chỉ</label>
                        <input type="text" name="_event_location_address" value="<?php echo esc_attr($fields['location_address']); ?>">
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function toggleLocationFields() {
                    if ($('#event_location_type').val() === 'physical') {
                        $('#physical_location_fields').show();
                    } else {
                        $('#physical_location_fields').hide();
                    }
                }
                toggleLocationFields();
                $('#event_location_type').on('change', toggleLocationFields);

                // Media Uploader
                var mediaUploader;
                $('#upload-event-image').on('click', function(e) {
                    e.preventDefault();
                    if (mediaUploader) { mediaUploader.open(); return; }
                    mediaUploader = wp.media({ title: 'Chọn ảnh sự kiện', button: { text: 'Sử dụng ảnh này' }, multiple: false });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#event_image_id').val(attachment.id);
                        $('#event-image-preview').attr('src', attachment.sizes.thumbnail.url);
                        $('.image-preview-wrapper, #remove-event-image').show();
                    });
                    mediaUploader.open();
                });
                $('#remove-event-image').on('click', function(e) {
                    e.preventDefault();
                    $('#event_image_id').val('');
                    $('.image-preview-wrapper, #remove-event-image').hide();
                });
            });
        </script>
        <?php
    }

    /**
     * 3. LƯU DỮ LIỆU META BOX
     *
     */
    public function save_event_meta_data($post_id) {
        if (!isset($_POST['tuancele_event_nonce']) || !wp_verify_nonce($_POST['tuancele_event_nonce'], 'tuancele_event_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ('event' !== get_post_type($post_id)) return;

        $fields_to_save = [
            '_event_icon', '_event_description', '_event_url', '_event_image_id', '_event_start_date', 
            '_event_end_date', '_event_organizer_name', '_event_performer_name', '_event_price', 
            '_event_currency', '_event_offer_availability', '_event_offer_valid_from', 
            '_event_location_type', '_event_location_name', '_event_location_address'
        ];
        
        foreach ($fields_to_save as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field(wp_unslash($_POST[$field]));
                if (in_array($field, ['_event_url', '_event_offer_availability'])) {
                    $value = esc_url_raw(wp_unslash($_POST[$field]));
                }
                update_post_meta($post_id, $field, $value);
            }
        }
    }

    /**
     * Tải script cho media uploader
     *
     */
    public function admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post;
            if ($post && 'event' === $post->post_type) {
                wp_enqueue_media();
            }
        }
    }

} // Kết thúc Class