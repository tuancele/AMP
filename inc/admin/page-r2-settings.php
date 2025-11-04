<?php
// File: inc/admin/page-r2-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMP_R2_Settings_Page extends AMP_Admin_Settings_Page_Base {

    protected function init_properties() {
        $this->id           = 'tuancele-amp-r2';
        $this->menu_title   = 'Cài đặt R2';
        $this->page_title   = 'Cài đặt lưu trữ Cloudflare R2';
        $this->option_group = 'tuancele_amp_r2_group';
        $this->option_name  = 'tuancele_r2_settings';
    }

    /**
     * Ghi đè (override) hàm render_page để thêm trạng thái và công cụ migration
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $this->page_title ); ?></h1>
            <p>Điền các thông tin dưới đây để kết nối website của bạn với dịch vụ lưu trữ Cloudflare R2.</p>
            <?php
            // Lấy trạng thái từ file gốc
            $status = get_option('tuancele_r2_connection_status');
             if ($status && isset($status['message'])) {
                $color = isset($status['success']) && $status['success'] ? '#28a745' : '#dc3545';
                echo '<strong>Trạng thái kết nối: <span style="color:' . esc_attr($color) . ';">' . esc_html($status['message']) . '</span></strong>';
            } else {
                 echo '<strong>Trạng thái kết nối: <span style="color:#ffc107;">Chưa kiểm tra.</span></strong>';
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
        
        // Section 1: Cài đặt kết nối
        $section_settings_id = 'tuancele_r2_settings_section';
        add_settings_section($section_settings_id, 'Thông tin kết nối Cloudflare R2', null, $this->id);
        
        $r2_fields = [
            'enable_r2'              => ['label' => 'Kích hoạt R2', 'type' => 'checkbox', 'desc' => 'Bật để tự động offload media lên R2 khi upload.'],
            'access_key_id'          => ['label' => 'Access Key ID'],
            'secret_access_key'      => ['label' => 'Secret Access Key', 'type' => 'password'],
            'bucket'                 => ['label' => 'Tên Bucket'],
            'endpoint'               => ['label' => 'Endpoint'],
            'public_url'             => ['label' => 'Public URL', 'desc' => 'URL tên miền công khai của R2 (ví dụ: https://pub-xxx.r2.dev)'],
            'delete_local_file'      => ['label' => 'Xóa file gốc', 'type' => 'checkbox', 'desc' => 'Tự động xóa file trên máy chủ sau khi upload lên R2 (Chỉ áp dụng cho file mới upload).'],
            'enable_webp_conversion' => ['label' => 'Chuyển sang WebP', 'type' => 'checkbox', 'desc' => 'Tự động tạo và offload phiên bản WebP.']
        ];
        foreach ($r2_fields as $id => $field) {
            add_settings_field(
                'tuancele_r2_' . $id, $field['label'],
                [ $this, 'render_field_callback' ],
                $this->id, $section_settings_id,
                array_merge($field, ['id' => $id])
            );
        }

        // Section 2: Công cụ Migration
        $section_migration_id = 'tuancele_r2_migration_section';
        add_settings_section(
            $section_migration_id,
            'Công cụ Di chuyển Dữ liệu cũ',
            function() { echo '<p>Sử dụng công cụ này để tải lên Cloudflare R2 toàn bộ các tệp media đã được tải lên từ trước.</p>'; },
            $this->id
        );
        add_settings_field(
            'tuancele_r2_migration_tool',
            'Trạng thái & Hành động',
            [ $this, 'render_migration_tool' ], // Hàm render riêng
            $this->id,
            $section_migration_id
        );
    }
    
    /**
     * Hàm render riêng cho công cụ migration, gọi file view
     */
    public function render_migration_tool() {
        require_once get_template_directory() . '/inc/view-r2-migration-tool.php';
    }
}