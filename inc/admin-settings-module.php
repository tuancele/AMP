<?php
/**
 * inc/admin-settings-module.php
 * Module Class tạo tất cả các trang Cài đặt Theme trong khu vực Admin WP.
 * [UPDATE]: Đã tách trang Hướng dẫn Shortcode ra file riêng (inc/admin-shortcode-guide.php).
 * [FIX]: Đã XÓA script accordion bị trùng lặp cho trang Hướng dẫn Shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

final class AMP_Admin_Settings_Module {

    /**
     * [SỬA LỖI] Hàm sanitize tùy chỉnh cho Cài đặt SMTP
     *
     * Hàm này cho phép lưu trường Mật khẩu (smtp_pass)
     * mà không bị WordPress xóa các ký tự đặc biệt (ví dụ: Secret Key của SES).
     *
     * @param array $input Dữ liệu thô từ form.
     * @return array Dữ liệu đã được làm sạch.
     */
    public function sanitize_smtp_settings( $input ) {
        $new_input = [];
        if ( ! is_array( $input ) ) {
            return $new_input;
        }

        // Lặp qua từng trường được gửi lên
        foreach ( $input as $key => $value ) {
            
            if ( $key === 'smtp_pass' ) {
                // --- TRƯỜNG HỢP ĐẶC BIỆT: MẬT KHẨU ---
                // Chỉ trim khoảng trắng, KHÔNG sanitize
                // để giữ lại các ký tự đặc biệt của Secret Key
                $new_input[ $key ] = trim( $value );
            
            } elseif ( in_array( $key, ['notification_email', 'smtp_from_email'] ) ) {
                // Làm sạch các trường email
                $new_input[ $key ] = sanitize_email( $value );

            } else {
                // Làm sạch tất cả các trường còn lại (như host, port, user...)
                $new_input[ $key ] = sanitize_text_field( $value );
            }
        }
        
        return $new_input;
    }

    /**
     * Khởi tạo module, đăng ký các hook cho khu vực admin.
     */
    public function __construct() {
        // Hooks chính
        add_action('admin_menu', [ $this, 'create_settings_pages' ]);
        add_action('admin_init', [ $this, 'register_all_settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'settings_admin_scripts' ]);
    }

    /**
     * 1. TẠO CÁC MENU TRONG ADMIN
     *
     */
    public function create_settings_pages() {
        // Chỉ chạy hàm này nếu user là admin (có quyền 'manage_options')
        if ( ! current_user_can('manage_options') ) {
            return;
        }
        // [ĐÃ SỬA] Thay đổi hàm callback mặc định từ 'shortcode_guide_page' thành 'integrations_settings_page'
        add_menu_page('Cài đặt Theme AMP', 'Cài đặt AMP', 'manage_options', 'tuancele-amp-settings', [ $this, 'integrations_settings_page' ], 'dashicons-superhero-alt', 60);
        
        // [ĐÃ SỬA] Đổi slug của trang "Tích hợp" thành slug menu cha để làm trang mặc định
        add_submenu_page('tuancele-amp-settings', 'Cài đặt Tích hợp', 'Tích hợp Dịch vụ', 'manage_options', 'tuancele-amp-settings', [ $this, 'integrations_settings_page' ]);
        
        // [THÊM MỚI] Trang A/B Testing
        add_submenu_page('tuancele-amp-settings', 'A/B Testing', 'A/B Testing', 'manage_options', 'tuancele-amp-ab-testing', [ $this, 'ab_testing_page' ]);

        add_submenu_page('tuancele-amp-settings', 'Cấu hình Schema Doanh nghiệp', 'Cấu hình Schema', 'manage_options', 'tuancele-amp-schema', [ $this, 'schema_settings_page' ]);
        add_submenu_page('tuancele-amp-settings', 'Cài đặt gửi mail (SMTP)', 'Cài đặt SMTP', 'manage_options', 'tuancele-amp-smtp', [ $this, 'smtp_settings_page' ]);
        add_submenu_page('tuancele-amp-settings', 'Cài đặt Cloudflare R2', 'Cài đặt R2', 'manage_options', 'tuancele-amp-r2', [ $this, 'r2_settings_page' ]);
        add_submenu_page('tuancele-amp-settings', 'Google reCAPTCHA v3', 'Cấu hình Captcha', 'manage_options', 'tuancele-amp-recaptcha', [ $this, 'recaptcha_settings_page' ]);
        add_submenu_page('tuancele-amp-settings', 'Cài đặt các Nút Nổi', 'Các Nút Nổi', 'manage_options', 'tuancele-amp-floating-buttons', [ $this, 'floating_buttons_page' ]);

        // Thêm CPTs vào menu
        add_submenu_page('tuancele-amp-settings', __('Image Maps', 'tuancele-amp'), __('Image Maps', 'tuancele-amp'), 'manage_options', 'edit.php?post_type=image_map', false);
        add_submenu_page('tuancele-amp-settings', __('Sự kiện', 'tuancele-amp'), __('Sự kiện', 'tuancele-amp'), 'manage_options', 'edit.php?post_type=event', false);
    }

    /**
     * 2. CÁC HÀM RENDER GIAO DIỆN HTML CHO TỪNG TRANG
     *
     */
    
    // --- [THÊM MỚI] Trang A/B Testing ---
    public function ab_testing_page() {
        ?>
        <div class="wrap">
            <h1>Cài đặt A/B Testing (AMP Experiment)</h1>
            <p>Sử dụng tính năng này để thử nghiệm các biến thể khác nhau của nội dung (nút bấm, tiêu đề, form...) và theo dõi hiệu quả qua Google Analytics.</p>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_ab_testing_group'); do_settings_sections('tuancele-amp-ab-testing'); submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function schema_settings_page() {
        ?>
        <div class="wrap">
            <h1>Cấu hình Schema Doanh nghiệp & Local SEO</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_schema_group'); do_settings_sections('tuancele-amp-schema'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function smtp_settings_page() {
        ?>
        <div class="wrap">
            <h1>Cài đặt gửi mail (SMTP)</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_smtp_group'); do_settings_sections('tuancele-amp-smtp'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function r2_settings_page() {
        ?>
        <div class="wrap">
            <h1>Cài đặt lưu trữ Cloudflare R2</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_r2_group'); do_settings_sections('tuancele-amp-r2'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function recaptcha_settings_page() {
        ?>
        <div class="wrap">
            <h1>Cấu hình Google reCAPTCHA v3</h1>
            <p>Đăng ký và lấy key tại đây: <a href="https://www.google.com/recaptcha/admin/create" target="_blank">Google reCAPTCHA Admin</a>. Chọn loại **reCAPTCHA v3**.</p>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_recaptcha_group'); do_settings_sections('tuancele-amp-recaptcha'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function floating_buttons_page() {
        ?>
        <div class="wrap">
            <h1>Cài đặt các Nút Nổi (Floating Buttons)</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_floating_buttons_group'); do_settings_sections('tuancele-amp-floating-buttons'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function integrations_settings_page() {
        ?>
        <div class="wrap">
            <h1>Cài đặt Tích hợp Dịch vụ Bên ngoài</h1>
            <form method="post" action="options.php">
                <?php settings_fields('tuancele_amp_integrations_group'); do_settings_sections('tuancele-amp-integrations'); submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * 3. HÀM TỔNG HỢP ĐỂ ĐĂNG KÝ TẤT CẢ CÁC TRƯỜNG CÀI ĐẶT
     *
     */
    public function register_all_settings() {
        
        register_setting('tuancele_amp_integrations_group', 'tuancele_integrations_settings');
        add_settings_section('tuancele_integrations_zoho_section', 'Tích hợp Zoho CRM', null, 'tuancele-amp-integrations');
        add_settings_field('zoho_xnqsjsdp', 'Zoho Key (xnQsjsdp)', [ $this, 'integrations_field_callback' ], 'tuancele-amp-integrations', 'tuancele_integrations_zoho_section', ['id' => 'zoho_xnqsjsdp']);
        add_settings_field('zoho_xmiwtld', 'Zoho Key (xmIwtLD)', [ $this, 'integrations_field_callback' ], 'tuancele-amp-integrations', 'tuancele_integrations_zoho_section', ['id' => 'zoho_xmiwtld']);
        
        add_settings_section('tuancele_integrations_ga4_section', 'Tích hợp Google Analytics (GA4)', null, 'tuancele-amp-integrations');
        add_settings_field(
            'ga4_measurement_id', 
            'Mã theo dõi (Measurement ID)', 
            [ $this, 'integrations_field_callback' ], 
            'tuancele-amp-integrations', 
            'tuancele_integrations_ga4_section', 
            [
                'id' => 'ga4_measurement_id', 
                'type' => 'text', 
                'placeholder' => 'G-XXXXXXXXXX',
                'default' => 'G-KJEEPYVTBR', 
                'desc' => 'Nhập mã GA4 của bạn (ví dụ: G-KJEEPYVTBR). Mã này sẽ được chèn vào <head>.'
            ]
        );

        add_settings_section('tuancele_integrations_modules_section', 'Kích hoạt Module', null, 'tuancele-amp-integrations');
        add_settings_field('enable_property_cpt', 'Kích hoạt Module BĐS', [ $this, 'integrations_field_callback' ], 'tuancele-amp-integrations', 'tuancele_integrations_modules_section', ['id' => 'enable_property_cpt', 'type' => 'checkbox']);

        // === [MỚI] THÊM KHỐI TƯỜNG LỬA IP ===
        
        add_settings_section(
            'tuancele_ip_blocker_section', // ID Section
            'Tường lửa IP Tự động (Gatekeeper)', // Tiêu đề Section
            [ $this, 'ip_blocker_section_callback' ], // Hàm Callback
            'tuancele-amp-integrations' // Tên trang
        );

        add_settings_field(
            'enable_ip_blocker', 
            'Kích hoạt Tường lửa', 
            [ $this, 'integrations_field_callback' ], 
            'tuancele-amp-integrations', 
            'tuancele_ip_blocker_section', 
            [
                'id' => 'enable_ip_blocker', 
                'type' => 'checkbox', 
                'desc' => 'Tự động chặn IP có dấu hiệu tấn công (DDoS) dựa trên số lượng request.'
            ]
        );
        
        add_settings_field(
            'blocking_threshold', 
            'Ngưỡng chặn (request/giờ)', 
            [ $this, 'integrations_field_callback' ], 
            'tuancele-amp-integrations', 
            'tuancele_ip_blocker_section', 
            [
                'id' => 'blocking_threshold', 
                'type' => 'number', 
                'default' => 500, 
                'desc' => 'Số lượng request trong 1 giờ. Nếu vượt quá, IP sẽ bị chặn trong 1 giờ. (Nên đặt > 300)'
            ]
        );
        
        add_settings_field(
            'ip_manual_blacklist', 
            'Danh sách Cấm (thủ công)', 
            [ $this, 'integrations_field_callback' ], 
            'tuancele-amp-integrations', 
            'tuancele_ip_blocker_section', 
            [
                'id' => 'ip_manual_blacklist', 
                'type' => 'textarea', 
                'desc' => 'Mỗi IP một dòng. Các IP này sẽ bị cấm vĩnh viễn.'
            ]
        );

        add_settings_field(
            'ip_manual_whitelist', 
            'Danh sách Trắng (luôn cho phép)', 
            [ $this, 'integrations_field_callback' ], 
            'tuancele-amp-integrations', 
            'tuancele_ip_blocker_section', 
            [
                'id' => 'ip_manual_whitelist', 
                'type' => 'textarea', 
                'desc' => 'Mỗi IP một dòng. Các IP này (ví dụ: IP văn phòng, Googlebot 66.249...) sẽ không bao giờ bị chặn.'
            ]
        );
        
        // === KẾT THÚC KHỐI MỚI ===

        // --- [THÊM MỚI] Đăng ký Cài đặt A/B Testing ---
        register_setting(
            'tuancele_amp_ab_testing_group', 
            'tuancele_ab_testing_settings',
            ['sanitize_callback' => 'sanitize_textarea_field'] // Dùng hàm sanitize mặc định cho textarea
        );
        add_settings_section('tuancele_ab_testing_main_section', 'Thiết lập Thử nghiệm', null, 'tuancele-amp-ab-testing');
        add_settings_field(
            'experiments_config', 
            'Cấu hình Thử nghiệm', 
            [ $this, 'ab_testing_field_callback' ], 
            'tuancele-amp-ab-testing', 
            'tuancele_ab_testing_main_section'
        );
        // --- KẾT THÚC THÊM MỚI ---

        register_setting('tuancele_amp_schema_group', 'tuancele_amp_schema_options', [ $this, 'sanitize_callback' ]);
        add_settings_section('tuancele_schema_main_section', 'Thông tin chung', null, 'tuancele-amp-schema');
        $schema_fields_main = [
            'name'              => 'Tên Doanh nghiệp',
            'logo'              => 'URL Logo',
            'organization_type' => ['label' => 'Loại hình Doanh nghiệp', 'type' => 'select', 'options' => [
                'Corporation'     => 'Doanh nghiệp (Mặc định)',
                'RealEstateAgent' => 'Đại lý Bất động sản',
                'LocalBusiness'   => 'Doanh nghiệp Địa phương',
            ]],
            'price_range'       => ['label' => 'Khoảng giá (Price Range)', 'desc' => 'Ví dụ: $100000-$500000. Dùng cho Đại lý BĐS.'],
            'telephone'         => 'Số điện thoại',
            'hotline_number'    => 'Hotline Hỗ trợ',
            'email'             => 'Email liên hệ',
            'description'       => 'Mô tả ngắn'
        ];
        foreach ($schema_fields_main as $id => $field_data) {
            $args = is_array($field_data) ? array_merge($field_data, ['id' => $id]) : ['id' => $id];
            $label = is_array($field_data) ? $field_data['label'] : $field_data;
            add_settings_field($id, $label, [ $this, 'schema_field_callback' ], 'tuancele-amp-schema', 'tuancele_schema_main_section', $args);
        }
        add_settings_section('tuancele_schema_local_seo_section', 'Địa chỉ & Local SEO', null, 'tuancele-amp-schema');
        $schema_fields_local = ['streetAddress' => 'Địa chỉ', 'addressLocality' => 'Quận / Huyện', 'addressRegion' => 'Tỉnh / Thành phố', 'postalCode' => 'Mã bưu chính', 'latitude' => 'Vĩ độ', 'longitude' => 'Kinh độ', 'openingHours' => 'Giờ mở cửa'];
        foreach ($schema_fields_local as $id => $title) add_settings_field($id, $title, [ $this, 'schema_field_callback' ], 'tuancele-amp-schema', 'tuancele_schema_local_seo_section', ['id' => $id]);
        add_settings_section('tuancele_schema_social_section', 'Mạng xã hội', null, 'tuancele-amp-schema');
        add_settings_field('sameAs', 'Các trang MXH', [ $this, 'schema_field_callback' ], 'tuancele-amp-schema', 'tuancele_schema_social_section', ['id' => 'sameAs']);

        register_setting(
            'tuancele_amp_smtp_group', 
            'tuancele_smtp_settings',
            [ $this, 'sanitize_smtp_settings' ] 
        );
        add_settings_section('tuancele_smtp_settings_section', 'Cấu hình gửi Mail (SMTP)', [ $this, 'smtp_section_callback' ], 'tuancele-amp-smtp');
        $smtp_fields = [
            'notification_email' => [
                'label' => 'Email nhận thông báo', 
                'type' => 'email',
                'desc' => 'Email của admin để nhận thông báo khi khách đăng ký.'
            ], 
            'enable_smtp' => [
                'label' => 'Kích hoạt SMTP', 
                'type' => 'checkbox'
            ],
            'smtp_provider' => [
                'label' => 'Loại Dịch vụ SMTP', 
                'type' => 'select', 
                'options' => [
                    'default' => 'Gmail / SMTP Thường (Tài khoản là Email)',
                    'ses'     => 'Amazon SES (Tài khoản là Key)'
                ],
                'default' => 'default'
            ],
            'smtp_from_email' => [
                'label' => 'Email gửi (From)', 
                'type' => 'email', 
                'desc' => 'Bắt buộc với SES. Phải là email đã xác thực (Verified Identity).'
            ],
            'smtp_user' => [
                'label' => 'Tài khoản SMTP',
                'desc' => 'Ví dụ: (Gmail: <code>example@gmail.com</code>) hoặc (SES: <code>AKIA...</code>)'
            ], 
            'smtp_pass' => [
                'label' => 'Mật khẩu SMTP', 
                'type' => 'password',
                'desc' => 'Ví dụ: (Gmail: <code>Mật khẩu ứng dụng</code>) hoặc (SES: <code>Mật khẩu SMTP</code>)'
            ], 
            'smtp_host' => [
                'label' => 'Máy chủ SMTP', 
                'default' => 'smtp.gmail.com'
            ], 
            'smtp_port' => [
                'label' => 'Cổng SMTP', 
                'type' => 'number', 
                'default' => '587'
            ], 
            'smtp_secure' => [
                'label' => 'Mã hóa', 
                'type' => 'select', 
                'options' => ['' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL']
            ]
        ];
        foreach ($smtp_fields as $id => $field) add_settings_field('tuancele_' . $id, $field['label'], [ $this, 'smtp_field_callback' ], 'tuancele-amp-smtp', 'tuancele_smtp_settings_section', array_merge($field, ['id' => $id]));

        register_setting('tuancele_amp_r2_group', 'tuancele_r2_settings');
        add_settings_section('tuancele_r2_settings_section', 'Thông tin kết nối Cloudflare R2', [ $this, 'r2_section_callback' ], 'tuancele-amp-r2');
        $r2_fields = ['enable_r2' => ['label' => 'Kích hoạt R2', 'type' => 'checkbox'], 'access_key_id' => ['label' => 'Access Key ID'], 'secret_access_key' => ['label' => 'Secret Access Key', 'type' => 'password'], 'bucket' => ['label' => 'Tên Bucket'], 'endpoint' => ['label' => 'Endpoint'], 'public_url' => ['label' => 'Public URL'], 'delete_local_file' => ['label' => 'Xóa file gốc', 'type' => 'checkbox'], 'enable_webp_conversion' => ['label' => 'Chuyển sang WebP', 'type' => 'checkbox']];
        foreach ($r2_fields as $id => $field) add_settings_field('tuancele_r2_' . $id, $field['label'], [ $this, 'r2_field_callback' ], 'tuancele-amp-r2', 'tuancele_r2_settings_section', array_merge($field, ['id' => $id]));
        add_settings_section('tuancele_r2_migration_section', 'Công cụ Di chuyển Dữ liệu cũ', [ $this, 'r2_migration_section_callback' ], 'tuancele-amp-r2');
        add_settings_field('tuancele_r2_migration_tool', 'Trạng thái & Hành động', [ $this, 'r2_migration_tool_callback' ], 'tuancele-amp-r2', 'tuancele_r2_migration_section');


        register_setting('tuancele_amp_recaptcha_group', 'tuancele_recaptcha_settings');
        add_settings_section('tuancele_recaptcha_main_section', 'Khóa API Google reCAPTCHA v3', null, 'tuancele-amp-recaptcha');
        $recaptcha_fields = [
            'enable_recaptcha'      => ['label' => 'Kích hoạt reCAPTCHA v3', 'type' => 'checkbox'],
            'recaptcha_v3_site_key' => ['label' => 'Site Key (v3)'], 
            'recaptcha_v3_secret_key' => ['label' => 'Secret Key (v3)', 'type' => 'password']
        ];
        foreach ($recaptcha_fields as $id => $field) {
            add_settings_field(
                'tuancele_recaptcha_' . $id, 
                $field['label'], 
                [ $this, 'recaptcha_field_callback' ], 
                'tuancele-amp-recaptcha', 
                'tuancele_recaptcha_main_section', 
                array_merge($field, ['id' => $id])
            );
        }
        
        register_setting('tuancele_amp_floating_buttons_group', 'tuancele_floating_buttons_options');
        add_settings_section('tuancele_floating_buttons_main_section', 'Thiết lập hiển thị', null, 'tuancele-amp-floating-buttons');
        add_settings_field('enable_call_button', 'Kích hoạt Nút Gọi', [ $this, 'floating_buttons_field_callback' ], 'tuancele-amp-floating-buttons', 'tuancele_floating_buttons_main_section', ['id' => 'enable_call_button']);
        add_settings_field('enable_form_button', 'Kích hoạt Nút Form', [ $this, 'floating_buttons_field_callback' ], 'tuancele-amp-floating-buttons', 'tuancele_floating_buttons_main_section', ['id' => 'enable_form_button']);
    }

    /**
     * 4. CÁC HÀM CALLBACK VÀ SANITIZE CHO TỪNG LOẠI CÀI ĐẶT
     *
     */
    
        public function integrations_field_callback($args) {
        $options = get_option('tuancele_integrations_settings', []);
        $id = $args['id'];
        $value = isset($options[$id]) && $options[$id] !== '' ? $options[$id] : ($args['default'] ?? '');
        $type = $args['type'] ?? 'text';
        $placeholder = $args['placeholder'] ?? '';
        $name_attr = 'tuancele_integrations_settings[' . esc_attr($id) . ']';

        switch ($type) {
            case 'checkbox':
                echo '<label><input type="checkbox" id="'.esc_attr($id).'" name="'.esc_attr($name_attr).'" value="on" ' . checked('on', $value, false) . '></label>';
                break;
            case 'textarea':
                echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name_attr) . '" rows="5" class="large-text code">' . esc_textarea($value) . '</textarea>';
                break;
            case 'number':
                echo '<input type="number" id="'.esc_attr($id).'" name="'.esc_attr($name_attr).'" value="'.esc_attr($value).'" class="small-text" placeholder="'.esc_attr($placeholder).'" />';
                break;
            case 'text':
            default:
                echo '<input type="text" id="'.esc_attr($id).'" name="'.esc_attr($name_attr).'" value="'.esc_attr($value).'" class="regular-text" placeholder="'.esc_attr($placeholder).'" />';
                break;
        }

        if (!empty($args['desc'])) {
            echo '<p class="description">' . wp_kses_post($args['desc']) . '</p>';
        }
    }

    public function ip_blocker_section_callback() {
        echo '<p>Phân tích log truy cập (từ CSDL <code>wp_visitor_logs</code>) để tự động chặn các IP có lưu lượng truy cập bất thường.</p>';
        
        // Hiển thị thời gian Cron Job chạy lần cuối
        $last_run = get_option('tuancele_ip_analyzer_last_run', 'Chưa chạy');
        if(is_numeric($last_run)) {
            // Chuyển đổi về múi giờ VN (GMT+7)
            $last_run_time = $last_run + (7 * 3600); 
            $last_run = date('H:i:s \n\g\à\y d/m/Y', $last_run_time);
        }
        echo '<p><strong>Lần quét gần nhất:</strong> ' . esc_html($last_run) . '</p>';
    }

// --- [THÊM MỚI] Callback cho A/B Testing ---
    public function ab_testing_field_callback() {
        $value = get_option('tuancele_ab_testing_settings', '');
        ?>
        <style>
            .ab-test-instructions {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-top: 10px;
                font-size: 13px;
                line-height: 1.6;
            }
            .ab-test-instructions p { margin-top: 0; }
            .ab-test-instructions code {
                background: #e0e0e0;
                padding: 2px 5px;
                border-radius: 3px;
                font-family: monospace;
            }
        </style>
        <textarea name="tuancele_ab_testing_settings" rows="15" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <div class="ab-test-instructions">
            <p>Nhập cấu hình thử nghiệm của bạn tại đây dưới dạng JSON. Cấu hình này sẽ được chèn vào thẻ <code>&lt;amp-experiment&gt;</code>.</p>
            <p><strong>Quan trọng:</strong> Mã theo dõi GA4 (từ trang "Tích hợp Dịch vụ") phải được cấu hình để A/B testing hoạt động.</p>
            <p><strong>Cấu trúc mẫu (JSON) - Phải khớp với shortcode:</strong></p>
<pre>{
  "form_title_test": {
    "sticky": true,
    "variants": {
      "tieu_de_goc": 50,
      "tieu_de_moi": 50
    }
  },
  "cta_button_test": {
    "sticky": true,
    "variants": {
      "button_xanh": 50,
      "button_do": 50
    }
  },
  "homepage_banner_test": {
    "sticky": true,
    "variants": {
      "banner_co_gai": 50,
      "banner_toa_nha": 50
    }
  }
}</pre>
            <p>Sử dụng shortcode <code>[ab_test_variant experiment="tên_thử_nghiệm" variant="tên_biến_thể"]...[/ab_test_variant]</code> trong bài viết để hiển thị nội dung tương ứng.</p>
        </div>
        <?php
    }
    
    public function schema_field_callback($args) {
        $options = get_option('tuancele_amp_schema_options', []);
        $id = $args['id'];
        $value = $options[$id] ?? '';
        $type = $args['type'] ?? 'text';
        $placeholder = '';
        
        if ($type === 'select') {
            echo '<select id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']">';
            if (!empty($args['options']) && is_array($args['options'])) {
                foreach ($args['options'] as $option_value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($label)
                    );
                }
            }
            echo '</select>';
        } elseif (in_array($id, ['description', 'sameAs', 'openingHours'])) {
            echo '<textarea id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']" rows="5" class="large-text code">' . esc_textarea($value) . '</textarea>';
            if ($id === 'sameAs') { echo '<p class="description">Nhập mỗi URL mạng xã hội trên một dòng.</p>'; }
            if ($id === 'openingHours') { echo '<p class="description">Định dạng chuẩn: <code>Mo-Fr 08:00-17:00</code>. Mỗi khoảng thời gian trên một dòng.</p>'; }
        } else {
            if (in_array($id, ['latitude', 'longitude'])) { $placeholder = 'Ví dụ: 21.028511'; }
            echo '<input type="text" id="' . esc_attr($id) . '" name="tuancele_amp_schema_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';
        }
        
        if (!empty($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    public function sanitize_callback($input) {
        $new_input = [];
        if ( !is_array($input) ) return $new_input;

        foreach ($input as $key => $value) {
            switch ($key) {
                case 'email': $new_input[$key] = sanitize_email($value); break;
                case 'logo': case 'url': $new_input[$key] = esc_url_raw(trim($value)); break;
                case 'sameAs':
                     $urls = preg_split('/[\r\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
                     $sanitized_urls = [];
                     foreach ($urls as $url) { $sanitized_urls[] = esc_url_raw(trim($url)); }
                     $new_input[$key] = implode("\r\n", array_filter($sanitized_urls));
                     break;
                case 'description': case 'openingHours': $new_input[$key] = sanitize_textarea_field($value); break;
                case 'latitude': case 'longitude': $new_input[$key] = preg_replace('/[^0-9.-]/', '', $value); break;
                default: $new_input[$key] = sanitize_text_field(trim($value)); break;
            }
        }
        return $new_input;
    }
    public function smtp_section_callback() {
        echo '<p>Sử dụng SMTP để tăng độ tin cậy khi gửi mail, tránh bị rơi vào hòm thư Spam.</p>';
        $status = get_option('tuancele_smtp_connection_status');
        if ($status && isset($status['message'])) {
            $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
            echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
        } else {
             echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra hoặc chưa lưu cài đặt.</span></strong>';
        }
    }
    public function smtp_field_callback($args) {
        $options = get_option('tuancele_smtp_settings', []);
        $id = $args['id'];
        $value = isset($options[$id]) ? $options[$id] : ($args['default'] ?? '');
        $type = $args['type'] ?? 'text';

        switch ($type) {
            case 'checkbox':
                 echo '<label><input type="checkbox" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
                 break;
            case 'password':
                echo '<input type="password" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
                break;
            case 'select':
                echo '<select id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']">';
                if (isset($args['options']) && is_array($args['options'])) {
                    foreach ($args['options'] as $val => $label) {
                        echo '<option value="' . esc_attr($val) . '" ' . selected($value, $val, false) . '>' . esc_html($label) . '</option>';
                    }
                }
                echo '</select>';
                break;
            default:
                echo '<input type="' . esc_attr($type) . '" id="tuancele_' . esc_attr($id) . '" name="tuancele_smtp_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                 break;
        }
        // Thêm dòng này để hiển thị mô tả (desc)
        if (!empty($args['desc'])) {
            echo '<p class="description">' . wp_kses_post($args['desc']) . '</p>';
        }
    }
    public function r2_section_callback() {
        echo '<p>Điền các thông tin dưới đây để kết nối website của bạn với dịch vụ lưu trữ Cloudflare R2.</p>';
        $status = get_option('tuancele_r2_connection_status');
         if ($status && isset($status['message'])) {
            $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
            echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
        } else {
             echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra.</span></strong>';
        }
    }
    public function r2_field_callback($args) {
        $options = get_option('tuancele_r2_settings', []);
        $id = $args['id'];
        $value = $options[$id] ?? '';
        $type = $args['type'] ?? 'text';

        switch ($type) {
            case 'checkbox':
                echo '<label><input type="checkbox" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
                break;
            case 'password':
                echo '<input type="password" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
                break;
            default:
                echo '<input type="text" id="tuancele_r2_' . esc_attr($id) . '" name="tuancele_r2_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
        }
    }
    public function r2_migration_section_callback() {
        echo '<p>Sử dụng công cụ này để tải lên Cloudflare R2 toàn bộ các tệp media đã được tải lên từ trước.</p>';
    }
    public function r2_migration_tool_callback() {
        $status = get_option('tuancele_r2_migration_status', ['running' => false, 'total' => 0, 'processed' => 0]);
        $is_running = $status['running'];
        
        $local_query = new WP_Query([
            'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query' => [['key' => '_tuancele_r2_offloaded', 'compare' => 'NOT EXISTS']]
        ]);
        $local_count = $local_query->post_count;
        ?>
        <style>#r2-migration-tool{border:1px solid #ccd0d4;padding:20px;background:#fff;border-radius:4px}#r2-migration-status{font-weight:700;margin-bottom:15px}#r2-progress-bar-container{width:100%;background-color:#e0e0e0;border-radius:4px;overflow:hidden;height:25px;margin-top:15px}#r2-progress-bar{width:0;height:100%;background-color:#4caf50;text-align:center;line-height:25px;color:#fff;transition:width .3s ease}#r2-migration-tool button{margin-right:10px}</style>
        <div id="r2-migration-tool">
            <div id="r2-migration-status"></div>
            <div id="r2-progress-bar-container"><div id="r2-progress-bar">0%</div></div>
<p style="margin-top:15px">
            <button type="button" class="button button-primary" id="start-r2-migration" <?php if ($is_running || $local_count === 0) echo 'disabled'; ?>>Bắt đầu Di chuyển <?php echo $local_count; ?> tệp</button>
            <button type="button" class="button" id="cancel-r2-migration" <?php if (!$is_running) echo 'disabled'; ?>>Hủy bỏ</button>
            <button type="button" class="button" id="recheck-r2-migration" style="margin-left: 15px;" <?php if ($is_running) echo 'disabled'; ?>><?php echo $is_running ? 'Đang chạy...' : 'Kiểm tra lại'; ?></button>
        </p>
        </div>
        <?php
    }

    // [ĐÃ SỬA] Hàm callback cho reCAPTCHA (hỗ trợ 'checkbox')
    public function recaptcha_field_callback($args) {
        $options = get_option('tuancele_recaptcha_settings', []);
        $id = $args['id'];
        $value = $options[$id] ?? '';
        $type = $args['type'] ?? 'text';
        
        switch ($type) {
            case 'checkbox':
                // Thêm 'name' attribute chính xác
                echo '<label><input type="checkbox" id="tuancele_recaptcha_' . esc_attr($id) . '" name="tuancele_recaptcha_settings[' . esc_attr($id) . ']" value="on" ' . checked('on', $value, false) . '></label>';
                break;
            case 'password':
                echo '<input type="password" id="tuancele_recaptcha_' . esc_attr($id) . '" name="tuancele_recaptcha_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
                break;
            case 'text':
            default:
                echo '<input type="text" id="tuancele_recaptcha_' . esc_attr($id) . '" name="tuancele_recaptcha_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
        }
    }

    // --- Callbacks cho Floating Buttons ---
    public function floating_buttons_field_callback($args) {
        $options = get_option('tuancele_floating_buttons_options', []);
        $id = $args['id'];
        $checked = isset($options[$id]) && $options[$id] === 'on';
        echo '<label><input type="checkbox" name="tuancele_floating_buttons_options[' . esc_attr($id) . ']" value="on" ' . checked($checked, true, false) . '></label>';
    }

 /**
     * 5. HÀM TỔNG HỢP ĐỂ TẢI SCRIPT CHO CÁC TRANG CÀI ĐẶT
     *
     */
    public function settings_admin_scripts($hook) {
        // [ĐÃ SỬA] Thêm trang reCAPTCHA vào mảng
        $pages_with_toggle = [
            'cai-dat-amp_page_tuancele-amp-smtp', 
            'cai-dat-amp_page_tuancele-amp-r2',
            'cai-dat-amp_page_tuancele-amp-recaptcha' // Thêm trang này
        ];
        
        // --- [ĐÃ XÓA] ---
        // Đã xóa khối if ( $hook === 'cai-dat-amp_page_tuancele-amp-shortcode-guide' )
        // vì script accordion đã được chuyển vào file inc/admin-shortcode-guide.php
        // --- KẾT THÚC XÓA ---


        if ( in_array($hook, $pages_with_toggle) ) {
            
            // Script này dùng chung cho cả 3 trang (SMTP, R2, reCAPTCHA)
            // Nó tự động tìm checkbox có name chứa "[enable_]" và ẩn tất cả các hàng <tr> phía sau
            $script_toggle = "
            jQuery(document).ready(function($) {
                'use strict';
                
                var mainCheckbox = null;
                
                // --- LOGIC CHO TRANG SMTP ---
                if ( $('body').hasClass('cai-dat-amp_page_tuancele-amp-smtp') ) {
                    
                    var enableSmtpCheckbox = $('input[type=\"checkbox\"][name*=\"[enable_smtp]\"]');
                    
                    if (enableSmtpCheckbox.length > 0) {
                        const dependentFields = enableSmtpCheckbox.closest('tr').nextAll();
                        var providerDropdown = $('select[name*=\"[smtp_provider]\"]');
                        var fromEmailRow = $('#tuancele_smtp_from_email').closest('tr'); // Tìm <tr> của trường 'Email gửi (From)'
                        
                        // Hàm 2: Ẩn/hiện trường 'Email gửi (From)' dựa trên Provider
                        function toggleProviderFields() {
                            if (providerDropdown.val() === 'ses') {
                                fromEmailRow.show();
                            } else { // 'default' (Gmail/Khác)
                                fromEmailRow.hide();
                            }
                        }
                        
                        // Hàm 1: Bật/tắt toàn bộ module SMTP
                        function toggleAllSmtpFields() {
                            if (enableSmtpCheckbox.is(':checked')) {
                                dependentFields.show();
                                toggleProviderFields(); // Chạy logic phụ
                            } else {
                                dependentFields.hide();
                            }
                        }
                        
                        // Chạy cả 2 hàm khi tải trang
                        toggleAllSmtpFields(); 
                        
                        // Gán sự kiện 'change'
                        enableSmtpCheckbox.on('change', toggleAllSmtpFields);
                        providerDropdown.on('change', toggleProviderFields);
                    }

                // --- LOGIC CHO TRANG R2 (Giữ nguyên) ---
                } else if ( $('body').hasClass('cai-dat-amp_page_tuancele-amp-r2') ) {
                    mainCheckbox = $('input[type=\"checkbox\"][name*=\"[enable_r2]\"]');
                
                // --- LOGIC CHO TRANG RECAPTCHA (Giữ nguyên) ---
                } else if ( $('body').hasClass('cai-dat-amp_page_tuancele-amp-recaptcha') ) {
                    mainCheckbox = $('input[type=\"checkbox\"][name*=\"[enable_recaptcha]\"]');
                }

                // Logic cũ cho R2 và reCAPTCHA (Không ảnh hưởng)
                if (mainCheckbox && mainCheckbox.length > 0) {
                    const dependentFields = mainCheckbox.closest('tr').nextAll();
                    function toggleFields() {
                        if (mainCheckbox.is(':checked')) { dependentFields.show(); } else { dependentFields.hide(); }
                    }
                    toggleFields(); 
                    mainCheckbox.on('change', toggleFields);
                }
            });";
            wp_add_inline_script('jquery-core', $script_toggle);
        }

        if ($hook === 'cai-dat-amp_page_tuancele-amp-r2') {
            
            // [SỬA LỖI] Sử dụng get_site_url() để lấy đường dẫn gốc,
            // tránh bị các plugin cache/offload khác rewrite đường dẫn.
            // Điều này đảm bảo file JS luôn được tải từ server local.
            $local_js_url = get_site_url(null, 'wp-content/themes/amp/inc/r2/admin-r2-migration.js');

            wp_enqueue_script(
                'tuancele-r2-migration',
                $local_js_url, // Sử dụng đường dẫn gốc, tuyệt đối
                ['jquery'], '1.2', true // Đổi version lên 1.2 để tránh cache
            );

            $nonce_data_script = sprintf(
                'const tuanceleR2Data = { ajax_url: "%s", nonce: "%s" };',
                admin_url('admin-ajax.php'),
                wp_create_nonce('r2_migration_nonce')
            );

            wp_add_inline_script('tuancele-r2-migration', $nonce_data_script, 'before');
        }
    }
} // Kết thúc Class