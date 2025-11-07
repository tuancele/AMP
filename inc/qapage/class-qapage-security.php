<?php
/**
 * inc/qapage/class-qapage-security.php
 *
 * Xử lý bảo mật cho Module QAPage, bao gồm:
 * 1. Tích hợp reCaptcha vào Form Gửi Trả lời (comment_form).
 * 2. Xác thực reCaptcha cho bình luận mới (preprocess_comment).
 *
 * [FIX V2] Sửa lỗi 403 khi gửi câu trả lời.
 * - Đổi data-action mong đợi từ 'qapage_submit_answer' thành 'submit_comment'
 * để khớp với data-action được gửi từ tuancele_amp_comment_form()
 * (trong inc/comments-module.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Security {

    private $recaptcha_site_key = '';
    private $recaptcha_secret_key = '';
    private $is_recaptcha_enabled = false;

    /**
     * Khởi tạo class, tải cài đặt reCaptcha và đăng ký hooks.
     */
    public function __construct() {
        // Tải cài đặt reCaptcha MỘT LẦN (tái sử dụng từ cài đặt theme)
        $options = get_option( 'tuancele_recaptcha_settings', [] );
        $this->is_recaptcha_enabled = isset( $options['enable_recaptcha'] ) && $options['enable_recaptcha'] === 'on';

        if ( $this->is_recaptcha_enabled ) {
            $this->recaptcha_site_key = $options['recaptcha_v3_site_key'] ?? '';
            $this->recaptcha_secret_key = $options['recaptcha_v3_secret_key'] ?? '';

            // Chỉ thêm các hook nếu reCaptcha được bật và có key
            if ( ! empty( $this->recaptcha_site_key ) && ! empty( $this->recaptcha_secret_key ) ) {

                // Hook để chèn HTML reCaptcha vào form bình luận
                add_filter( 'comment_form_defaults', [ $this, 'inject_recaptcha_into_form' ] );

                // Hook để xác thực reCaptcha TRƯỚC KHI lưu bình luận
                add_filter( 'preprocess_comment', [ $this, 'verify_recaptcha_for_comment' ] );
            }
        }
    }

    /**
     * Chèn <amp-recaptcha-input> vào form bình luận của QAPage.
     *
     * @param array $defaults Cài đặt mặc định của comment_form().
     * @return array Cài đặt đã được sửa đổi.
     */
    public function inject_recaptcha_into_form( $defaults ) {
        // Chỉ can thiệp nếu đây là CPT 'qapage_question'
        if ( get_post_type() !== 'qapage_question' ) {
            return $defaults;
        }
        
        // [FIX V2] Đổi data-action thành 'submit_comment'
        $recaptcha_html = '<div class="comment-form-captcha qapage-recaptcha" style="font-size: 11px; color: #777; margin-bottom: 10px;">
            <amp-recaptcha-input
                layout="nodisplay"
                name="g-recaptcha-response"
                data-sitekey="' . esc_attr( $this->recaptcha_site_key ) . '"
                data-action="submit_comment">
            </amp-recaptcha-input>
        </div>';

        // Chèn HTML reCaptcha vào cuối form, ngay trước nút submit
        $defaults['comment_notes_after'] = ( $defaults['comment_notes_after'] ?? '' ) . $recaptcha_html;

        return $defaults;
    }

    /**
     * Xác thực reCaptcha cho bình luận mới (Hook: preprocess_comment).
     *
     * @param array $commentdata Dữ liệu bình luận đang chờ xử lý.
     * @return array Dữ liệu bình luận (nếu hợp lệ).
     */
    public function verify_recaptcha_for_comment( $commentdata ) {
        // Chỉ can thiệp nếu đây là CPT 'qapage_question'
        if ( ! isset( $commentdata['comment_post_ID'] ) || get_post_type( $commentdata['comment_post_ID'] ) !== 'qapage_question' ) {
            return $commentdata;
        }

        // Admin không cần kiểm tra
        if ( current_user_can( 'manage_options' ) ) {
            return $commentdata;
        }
        
        // Nếu bình luận này được gửi qua AMP XHR (bởi module kia),
        // nó sẽ có 'comment_agent' là 'AMP-Form'.
        // Chúng ta chỉ nên chạy xác thực reCaptcha này nếu nó
        // KHÔNG phải là form XHR (ví dụ: form `comment_form()` gốc).
        if ( isset( $commentdata['comment_agent'] ) && $commentdata['comment_agent'] === 'AMP-Form' ) {
            // Lần kiểm tra 1 (trong inc/comments-module.php) đã chạy rồi.
            // Bỏ qua lần kiểm tra thứ 2 này để tránh lỗi.
            return $commentdata;
        }

        // Lấy token từ POST request
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        $user_ip = $commentdata['comment_author_IP'] ?? '';

        if ( empty( $recaptcha_token ) ) {
            wp_die( __( 'Lỗi: reCaptcha token bị thiếu. Vui lòng quay lại và thử lại.' ), 'Lỗi reCaptcha', 400 );
        }

        // [FIX V2] Đổi action mong đợi thành 'submit_comment'
        if ( ! $this->verify_recaptcha_token( $recaptcha_token, $user_ip, 'submit_comment' ) ) {
            wp_die( __( 'Lỗi: Xác minh reCaptcha thất bại. Có vẻ bạn là robot.' ), 'Lỗi reCaptcha', 403 );
        }

        // Xác thực thành công, cho phép lưu bình luận
        return $commentdata;
    }

/**
 * Tái sử dụng logic xác thực reCaptcha.
 * [FIX] Đã sửa lỗi: Gỡ bỏ kiểm tra 'action' để đồng bộ với
 * các module reCaptcha khác của theme (chỉ kiểm tra 'success').
 *
 * @param string $token Token reCaptcha từ form.
 * @param string $ip Địa chỉ IP của người dùng.
 * @param string $action Tên action (phải khớp với data-action).
 * @return bool True nếu hợp lệ, False nếu thất bại.
 */
private function verify_recaptcha_token( $token, $ip, $action ) {
    if ( empty( $this->recaptcha_secret_key ) || empty( $token ) ) {
        return false;
    }

    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => $this->recaptcha_secret_key,
            'response' => $token,
            'remoteip' => $ip
        ],
    ] );

    if ( is_wp_error( $response ) ) { 
        error_log( 'QAPage reCAPTCHA WP_Error: ' . $response->get_error_message() );
        return false; 
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    // [ĐÃ SỬA LỖI] Chỉ kiểm tra 'success', bỏ qua 'action'
    return isset( $body['success'] ) && $body['success'] === true;
}
}