<?php
/**
 * inc/qapage/class-qapage-metabox.php
 *
 * Đăng ký Meta Box "Ngữ cảnh Liên quan" cho CPT 'qapage_question'.
 * Cho phép liên kết một Câu hỏi với một Post, Page, hoặc Category.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Metabox {

    private $meta_key = '_qapage_related_context_url';
    private $nonce_action = 'qapage_save_metabox_nonce';
    private $nonce_name = 'qapage_metabox_nonce';

    /**
     * Khởi tạo, đăng ký các hook cho meta box.
     */
    public function __construct() {
        // Hook để thêm meta box
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        
        // Hook để lưu dữ liệu meta box
        add_action( 'save_post', [ $this, 'save_metabox' ] );
    }

    /**
     * Đăng ký meta box với WordPress.
     */
    public function add_metabox() {
        add_meta_box(
            'qapage_context_metabox',                 // ID của meta box
            __( 'Ngữ cảnh Liên quan (Context)', 'tuancele-amp' ), // Tiêu đề meta box
            [ $this, 'render_metabox_html' ],         // Hàm callback để render HTML
            'qapage_question',                        // Chỉ hiển thị trên CPT 'qapage_question'
            'side',                                   // Vị trí (side, normal, advanced)
            'low'                                     // Ưu tiên (high, default, low)
        );
    }

    /**
     * Render HTML cho nội dung meta box.
     *
     * @param WP_Post $post Đối tượng bài viết hiện tại.
     */
    public function render_metabox_html( $post ) {
        // Lấy giá trị đã lưu (nếu có)
        $related_url = get_post_meta( $post->ID, $this->meta_key, true );
        
        // Thêm trường nonce để bảo mật
        wp_nonce_field( $this->nonce_action, $this->nonce_name );
        ?>
        
        <p>
            <label for="qapage_related_url">
                <strong><?php _e( 'Link nội dung liên quan (Tùy chọn):', 'tuancele-amp' ); ?></strong>
            </label>
        </p>
        <p>
            <input type="url" 
                   id="qapage_related_url" 
                   name="<?php echo esc_attr( $this->meta_key ); ?>" 
                   value="<?php echo esc_url( $related_url ); ?>" 
                   placeholder="https://domain.com/post-lien-quan/"
                   style="width: 100%;">
        </p>
        <p class="description">
            <?php _e( 'Dán URL của bài viết, trang, hoặc chuyên mục mà câu hỏi này liên quan đến. Điều này giúp shortcode [qapage_related_list] tìm thấy câu hỏi này.', 'tuancele-amp' ); ?>
        </p>
        
        <?php
    }

    /**
     * Lưu dữ liệu từ meta box khi bài viết được lưu.
     *
     * @param int $post_id ID của bài viết đang được lưu.
     */
    public function save_metabox( $post_id ) {
        // 1. Kiểm tra Nonce
        if ( ! isset( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
            return;
        }

        // 2. Không lưu khi đang autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Kiểm tra quyền của user
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 4. Chỉ lưu cho CPT 'qapage_question'
        if ( ! isset( $_POST['post_type'] ) || $_POST['post_type'] !== 'qapage_question' ) {
            return;
        }

        // 5. Lưu dữ liệu
        if ( isset( $_POST[ $this->meta_key ] ) ) {
            $url = esc_url_raw( $_POST[ $this->meta_key ] );
            if ( ! empty( $url ) ) {
                update_post_meta( $post_id, $this->meta_key, $url );
            } else {
                // Xóa meta nếu trường bị bỏ trống
                delete_post_meta( $post_id, $this->meta_key );
            }
        }
    }
}