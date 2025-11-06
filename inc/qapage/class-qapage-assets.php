<?php
/**
 * inc/qapage/class-qapage-assets.php
 *
 * Tải CSS và JS (AMP Components) dành riêng cho Module QAPage.
 * Đảm bảo tính độc lập và không làm nặng các trang khác.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Assets {

    /**
     * Khởi tạo, đăng ký các hook để chèn assets.
     */
    public function __construct() {
        // 1. Hook vào 'amp_custom_css' để chèn CSS tùy chỉnh của QAPage
        // (Hook này được định nghĩa trong /inc/theme-setup.php)
        add_action( 'amp_custom_css', [ $this, 'load_qapage_css' ] );
        
        // 2. Hook vào 'wp_head' để tải các AMP component script cần thiết
        // Priority 7 để chạy cùng lúc với các script AMP điều kiện khác của theme
        add_action( 'wp_head', [ $this, 'load_qapage_amp_scripts' ], 7 );
    }

    /**
     * Hàm helper private để kiểm tra xem có phải trang QAPage không.
     *
     * @return bool
     */
    private function is_qapage() {
        // Logic kiểm tra bao gồm:
        // 1. Trang single CPT (chi tiết câu hỏi)
        // 2. Trang archive CPT (danh sách câu hỏi)
        // 3. Template trang "Đặt Câu Hỏi" (chúng ta sẽ tạo ở File 16)
        return is_singular( 'qapage_question' ) || 
               is_post_type_archive( 'qapage_question' ) || 
               is_page_template( 'page-templates/page-qapage-ask.php' );
    }

    /**
     * Chèn nội dung của qapage-style.css vào <style amp-custom>.
     */
    public function load_qapage_css() {
        // Chỉ tải nếu đây là trang QAPage
        if ( ! $this->is_qapage() ) {
            return;
        }

        $css_file = get_template_directory() . '/inc/qapage/assets/css/qapage-style.css';

        if ( file_exists( $css_file ) ) {
            // Echo nội dung file CSS vào hook 'amp_custom_css'
            // Nó sẽ được gộp chung vào 1 thẻ <style> cùng CSS chính của theme
            echo file_get_contents( $css_file );
        }
    }

    /**
     * Tải các script AMP component cần thiết cho QAPage (Form, reCaptcha).
     */
    public function load_qapage_amp_scripts() {
        // Chỉ tải nếu đây là trang QAPage
        if ( ! $this->is_qapage() ) {
            return;
        }
        
        // Module QAPage cần các component sau:
        // 1. amp-form: Cho các form Đặt câu hỏi, Vote, Chấp nhận
        // 2. amp-recaptcha-input: Bảo mật reCaptcha
        // (Lưu ý: amp-mustache và amp-bind đã được tải global trong header.php)
        
        echo '<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>' . "\n";
        
        // Chỉ tải reCaptcha script nếu nó được bật trong cài đặt
        $options = get_option( 'tuancele_recaptcha_settings', [] );
        $is_enabled = isset( $options['enable_recaptcha'] ) && $options['enable_recaptcha'] === 'on';
        if ( $is_enabled && ! empty( $options['recaptcha_v3_site_key'] ) ) {
            echo '<script async custom-element="amp-recaptcha-input" src="https://cdn.ampproject.org/v0/amp-recaptcha-input-0.1.js"></script>' . "\n";
        }
    }
}