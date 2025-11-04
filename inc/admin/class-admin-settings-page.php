<?php
/**
 * Lớp trừu tượng (Base Class) cho một trang cài đặt.
 * Đây là khung sườn chung mà tất cả các trang cài đặt con sẽ kế thừa.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

abstract class AMP_Admin_Settings_Page_Base {

    /**
     * ID của trang (ví dụ: 'tuancele-amp-smtp')
     * @var string
     */
    protected $id;

    /**
     * Slug của menu cha (ví dụ: 'tuancele-amp-settings')
     * @var string
     */
    protected $parent_slug;

    /**
     * Tên hiển thị trên menu (ví dụ: 'Cài đặt SMTP')
     * @var string
     */
    protected $menu_title;

    /**
     * Tên hiển thị trên tiêu đề H1 của trang (ví dụ: 'Cấu hình gửi mail (SMTP)')
     * @var string
     */
    protected $page_title;

    /**
     * Tên group cho register_setting (ví dụ: 'tuancele_amp_smtp_group')
     * @var string
     */
    protected $option_group;

    /**
     * Tên option lưu trong CSDL (ví dụ: 'tuancele_smtp_settings')
     * @var string
     */
    protected $option_name;

    /**
     * Khởi tạo class, gán slug cha và gọi hàm init
     */
    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;
        $this->init_properties(); // Gọi hàm init của class con
    }

    // =========================================================================
    // HÀM TRỪU TƯỢNG (BẮT BUỘC CÁC CLASS CON PHẢI CÓ)
    // =========================================================================

    /**
     * Định nghĩa các thuộc tính (properties) cho trang.
     * Class con BẮT BUỘC phải định nghĩa hàm này.
     */
    abstract protected function init_properties();

    /**
     * Đăng ký các sections và fields cho trang.
     * Class con BẮT BUỘC phải định nghĩa hàm này.
     */
    abstract public function register_sections_and_fields();

    // =========================================================================
    // CÁC HÀM CHUNG (DÙNG CHO TẤT CẢ CÁC TRANG)
    // =========================================================================

    /**
     * Gắn trang vào menu admin.
     */
    public function add_menu_page() {
        add_submenu_page(
            $this->parent_slug,       // Slug của menu cha
            $this->page_title,        // Tiêu đề trang
            $this->menu_title,        // Tiêu đề menu
            'manage_options',         // Quyền truy cập
            $this->id,                // Slug của trang này
            [ $this, 'render_page' ] // Hàm callback để render HTML
        );
    }

    /**
     * Đăng ký cài đặt với WordPress.
     * Trỏ đến hàm sanitize của class.
     */
    public function register_settings() {
        register_setting(
            $this->option_group,    // Tên group
            $this->option_name,     // Tên option
            [ $this, 'sanitize' ]  // Hàm callback sanitize
        );
        
        // Gọi hàm của class con để đăng ký sections & fields
        $this->register_sections_and_fields();
    }

    /**
     * Hàm Sanitize (làm sạch) dữ liệu mặc định.
     * Class con có thể "override" (ghi đè) hàm này nếu cần logic phức tạp.
     */
    public function sanitize( $input ) {
        // Mặc định, chúng ta chỉ trả về input
        // Các class con (như Schema) sẽ tự xử lý việc làm sạch
        return $input;
    }

    /**
     * Render HTML cho trang cài đặt (mẫu chung).
     * Class con có thể "override" (ghi đè) hàm này nếu cần giao diện đặc biệt.
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $this->page_title ); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Bảo mật và các trường ẩn
                settings_fields( $this->option_group );
                
                // Hiển thị tất cả sections và fields đã đăng ký cho trang này
                do_settings_sections( $this->id );
                
                // Hiển thị nút "Lưu thay đổi"
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * [TỐI ƯU] Hàm callback đa năng để render tất cả các loại trường.
     * Hàm này thay thế cho 5+ hàm callback bị trùng lặp trong file cũ.
     */
    public function render_field_callback($args) {
        // Lấy các tham số bắt buộc
        $id = $args['id']; // Ví dụ: 'smtp_host'
        
        // Lấy các tham số tùy chọn
        $options = get_option($this->option_name, []);
        $value   = $options[$id] ?? ($args['default'] ?? '');
        $type    = $args['type'] ?? 'text';
        $class   = $args['class'] ?? 'regular-text';
        $desc    = $args['desc'] ?? '';

        // Render HTML dựa trên loại trường
        switch ($type) {
            case 'checkbox':
                echo '<label><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
                break;
            
            case 'password':
                echo '<input type="password" id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" autocomplete="new-password" />';
                break;

            case 'number':
                $class = $args['class'] ?? 'small-text'; // Mặc định cho số là small-text
                echo '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" />';
                break;

            case 'select':
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']">';
                if (!empty($args['options']) && is_array($args['options'])) {
                    foreach ($args['options'] as $val => $label) {
                        echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>' . esc_html($label) . '</option>';
                    }
                }
                echo '</select>';
                break;
            
            case 'textarea':
                 $class = $args['class'] ?? 'large-text code'; // Mặc định cho textarea
                 echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']" rows="5" class="' . esc_attr($class) . '">' . esc_textarea($value) . '</textarea>';
                 break;

            case 'text':
            case 'email':
            case 'url':
            default:
                echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->option_name) . '[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" />';
                break;
        }
        
        // Hiển thị mô tả nếu có
        if ($desc) {
            echo '<p class="description">' . wp_kses_post($desc) . '</p>';
        }
    }
}