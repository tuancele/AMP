<?php
// File: inc/admin/page-floating-buttons-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_Floating_Buttons_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-floating-buttons';
        $this->menu_title   = 'Các Nút Nổi';
        $this->page_title   = 'Cài đặt các Nút Nổi (Floating Buttons)';
        $this->option_group = 'tuancele_amp_floating_buttons_group';
        $this->option_name  = 'tuancele_floating_buttons_options';
    }

    public function register_sections_and_fields() {
        $section_id = 'tuancele_floating_buttons_main_section';
        add_settings_section($section_id, 'Thiết lập hiển thị', null, $this->id);
        
        add_settings_field(
            'enable_call_button', 'Kích hoạt Nút Gọi',
            [ $this, 'render_field_callback' ],
            $this->id, $section_id,
            ['id' => 'enable_call_button', 'type' => 'checkbox']
        );
        
        add_settings_field(
            'enable_form_button', 'Kích hoạt Nút Form',
            [ $this, 'render_field_callback' ],
            $this->id, $section_id,
            ['id' => 'enable_form_button', 'type' => 'checkbox']
        );
    }
    
    // Sử dụng hàm sanitize mặc định (không cần override)
    public function sanitize( $input ) {
        return $input;
    }
}