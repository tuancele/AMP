<?php
// File: inc/admin/page-recaptcha-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_Recaptcha_Settings_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-recaptcha';
        $this->menu_title   = 'Cấu hình Captcha';
        $this->page_title   = 'Cấu hình Google reCAPTCHA v3';
        $this->option_group = 'tuancele_amp_recaptcha_group';
        $this->option_name  = 'tuancele_recaptcha_settings';
    }

    /**
     * Ghi đè (override) hàm render_page để thêm mô tả
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $this->page_title ); ?></h1>
            <p>Đăng ký và lấy key tại đây: <a href="https://www.google.com/recaptcha/admin/create" target="_blank">Google reCAPTCHA Admin</a>. Chọn loại **reCAPTCHA v3**.</p>
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
        $section_id = 'tuancele_recaptcha_main_section';
        add_settings_section($section_id, 'Khóa API Google reCAPTCHA v3', null, $this->id);

        $recaptcha_fields = [
            'recaptcha_v3_site_key'   => ['label' => 'Site Key (v3)'], 
            'recaptcha_v3_secret_key' => ['label' => 'Secret Key (v3)', 'type' => 'password']
        ];

        foreach ($recaptcha_fields as $id => $field) {
            add_settings_field(
                'tuancele_recaptcha_' . $id,
                $field['label'],
                [ $this, 'render_field_callback' ],
                $this->id,
                $section_id,
                array_merge($field, ['id' => $id])
            );
        }
    }
}