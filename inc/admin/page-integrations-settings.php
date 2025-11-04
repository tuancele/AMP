<?php
// File: inc/admin/page-integrations-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_Integrations_Settings_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-integrations';
        $this->menu_title   = 'Tích hợp Dịch vụ';
        $this->page_title   = 'Cài đặt Tích hợp Dịch vụ Bên ngoài';
        $this->option_group = 'tuancele_amp_integrations_group';
        $this->option_name  = 'tuancele_integrations_settings';
    }

    public function register_sections_and_fields() {
        
        // Section 1: Zoho
        $section_zoho = 'tuancele_integrations_zoho_section';
        add_settings_section(
            $section_zoho,
            'Tích hợp Zoho CRM',
            null,
            $this->id
        );

        add_settings_field(
            'zoho_xnqsjsdp', 'Zoho Key (xnQsjsdp)',
            [ $this, 'render_field_callback' ],
            $this->id, $section_zoho,
            ['id' => 'zoho_xnqsjsdp'] // Ghi đè $args
        );
        add_settings_field(
            'zoho_xmiwtld', 'Zoho Key (xmIwtLD)',
            [ $this, 'render_field_callback' ],
            $this->id, $section_zoho,
            ['id' => 'zoho_xmiwtld']
        );

        // Section 2: Kích hoạt Module
        $section_modules = 'tuancele_integrations_modules_section';
        add_settings_section(
            $section_modules,
            'Kích hoạt Module',
            null,
            $this->id
        );

        add_settings_field(
            'enable_property_cpt', 'Kích hoạt Module BĐS',
            [ $this, 'render_field_callback' ],
            $this->id, $section_modules,
            ['id' => 'enable_property_cpt', 'type' => 'checkbox']
        );
    }
    
    // Sử dụng hàm sanitize mặc định (không cần override)
    public function sanitize( $input ) {
        return $input;
    }
}