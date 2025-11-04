<?php
// File: inc/admin/page-schema-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_Schema_Settings_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-schema';
        $this->menu_title   = 'Cấu hình Schema';
        $this->page_title   = 'Cấu hình Schema Doanh nghiệp & Local SEO';
        $this->option_group = 'tuancele_amp_schema_group';
        $this->option_name  = 'tuancele_amp_schema_options';
    }
    
    // Ghi đè (override) hàm sanitize
    public function sanitize( $input ) {
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

    public function register_sections_and_fields() {
        
        // Section 1: Thông tin chung
        $section_main = 'tuancele_schema_main_section';
        add_settings_section($section_main, 'Thông tin chung', null, $this->id);
        
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
            'description'       => ['label' => 'Mô tả ngắn', 'type' => 'textarea']
        ];
        foreach ($schema_fields_main as $id => $field_data) {
            $args = is_array($field_data) ? array_merge($field_data, ['id' => $id]) : ['id' => $id];
            $label = is_array($field_data) ? $field_data['label'] : $field_data;
            add_settings_field($id, $label, [ $this, 'render_field_callback' ], $this->id, $section_main, $args);
        }

        // Section 2: Địa chỉ
        $section_local = 'tuancele_schema_local_seo_section';
        add_settings_section($section_local, 'Địa chỉ & Local SEO', null, $this->id);
        $schema_fields_local = [
            'streetAddress' => 'Địa chỉ', 'addressLocality' => 'Quận / Huyện', 
            'addressRegion' => 'Tỉnh / Thành phố', 'postalCode' => 'Mã bưu chính', 
            'latitude' => 'Vĩ độ', 'longitude' => 'Kinh độ', 
            'openingHours' => ['label' => 'Giờ mở cửa', 'type' => 'textarea', 'desc' => 'Định dạng chuẩn: <code>Mo-Fr 08:00-17:00</code>. Mỗi khoảng thời gian trên một dòng.']
        ];
        foreach ($schema_fields_local as $id => $field_data) {
            $args = is_array($field_data) ? array_merge($field_data, ['id' => $id]) : ['id' => $id];
            $label = is_array($field_data) ? $field_data['label'] : $field_data;
            add_settings_field($id, $label, [ $this, 'render_field_callback' ], $this->id, $section_local, $args);
        }

        // Section 3: Mạng xã hội
        $section_social = 'tuancele_schema_social_section';
        add_settings_section($section_social, 'Mạng xã hội', null, $this->id);
        add_settings_field(
            'sameAs', 'Các trang MXH', 
            [ $this, 'render_field_callback' ], $this->id, $section_social, 
            ['id' => 'sameAs', 'type' => 'textarea', 'desc' => 'Nhập mỗi URL mạng xã hội trên một dòng.']
        );
    }
}