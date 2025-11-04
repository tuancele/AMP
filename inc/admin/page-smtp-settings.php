<?php
// File: inc/admin/page-smtp-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_SMTP_Settings_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-smtp';
        $this->menu_title   = 'Cài đặt SMTP';
        $this->page_title   = 'Cài đặt gửi mail (SMTP)';
        $this->option_group = 'tuancele_amp_smtp_group';
        $this->option_name  = 'tuancele_smtp_settings';
    }

    /**
     * Ghi đè (override) hàm render_page để thêm phần hiển thị trạng thái
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $this->page_title ); ?></h1>
            <p>Sử dụng SMTP để tăng độ tin cậy khi gửi mail, tránh bị rơi vào hòm thư Spam.</p>
            <?php
            // Lấy trạng thái từ file gốc
            $status = get_option('tuancele_smtp_connection_status');
            if ($status && isset($status['message'])) {
                $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
                echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
            } else {
                 echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra hoặc chưa lưu cài đặt.</span></strong>';
            }
            ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( $this->id );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_sections_and_fields() {
        $section_id = 'tuancele_smtp_settings_section';
        add_settings_section($section_id, 'Cấu hình gửi Mail (SMTP)', null, $this->id);

        $smtp_fields = [
            'notification_email' => ['label' => 'Email nhận thông báo', 'type' => 'email'],
            'enable_smtp'        => ['label' => 'Kích hoạt SMTP', 'type' => 'checkbox', 'desc' => 'Bật để sử dụng SMTP. (Nếu tắt, các trường bên dưới không có tác dụng).'],
            'smtp_user'          => ['label' => 'Tài khoản SMTP'],
            'smtp_pass'          => ['label' => 'Mật khẩu SMTP', 'type' => 'password'],
            'smtp_host'          => ['label' => 'Máy chủ SMTP', 'default' => 'smtp.gmail.com'],
            'smtp_port'          => ['label' => 'Cổng SMTP', 'type' => 'number', 'default' => '587', 'class' => 'small-text'],
            'smtp_secure'        => ['label' => 'Mã hóa', 'type' => 'select', 'options' => ['' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL']]
        ];

        foreach ($smtp_fields as $id => $field) {
            add_settings_field(
                'tuancele_' . $id,
                $field['label'],
                [ $this, 'render_field_callback' ],
                $this->id,
                $section_id,
                array_merge($field, ['id' => $id])
            );
        }
    }
}