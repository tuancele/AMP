<?php
/**
 * inc/qapage/class-qapage-security.php
 *
 * Xử lý bảo mật cho Module QAPage, bao gồm:
 * 1. Tích hợp reCaptcha vào Form Gửi Trả lời (comment_form).
 * 2. Xác thực reCaptcha cho bình luận mới (preprocess_comment).
 * 3. Tái sử dụng logic xác thực reCaptcha từ các module cũ.
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
     *
     * [SỬA LỖI]: Sửa tên hàm 'publicGAge_Security()' thành 'public function __construct()'
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

        // Tạo HTML cho reCaptcha
        $recaptcha_html = '<div class="comment-form-captcha qapage-recaptcha" style="font-size: 11px; color: #777; margin-bottom: 10px;">
            <amp-recaptcha-input
                layout="nodisplay"
                name="g-recaptcha-response"
                data-sitekey="' . esc_attr( $this->recaptcha_site_key ) . '"
                data-action="qapage_submit_answer">
            </amp-recaptcha-input>
        </div>';

        // Chèn HTML reCaptcha vào cuối form, ngay trước nút submit
        // 'comment_notes_after' là một vị trí an toàn, đáng tin cậy
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

        // Lấy token từ POST request
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        $user_ip = $commentdata['comment_author_IP'] ?? '';

        if ( empty( $recaptcha_token ) ) {
            wp_die( __( 'Lỗi: reCaptcha token bị thiếu. Vui lòng quay lại và thử lại.' ), 'Lỗi reCaptcha', 400 );
        }

        // Gọi hàm xác thực (logic tái sử dụng)
        if ( ! $this->verify_recaptcha_token( $recaptcha_token, $user_ip, 'qapage_submit_answer' ) ) {
            wp_die( __( 'Lỗi: Xác minh reCaptcha thất bại. Có vẻ bạn là robot.' ), 'Lỗi reCaptcha', 403 );
        }

        // Xác thực thành công, cho phép lưu bình luận
        return $commentdata;
    }

    /**
     * Tái sử dụng logic xác thực reCaptcha.
     * Hàm này được sao chép từ inc/integrations-module.php
     * để đảm bảo module này hoạt động độc lập.
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

        // Kiểm tra action (tên hành động)
        if ( ! isset( $body['action'] ) || $body['action'] !== $action ) {
             error_log( 'QAPage reCAPTCHA Action Mismatch. Expected: ' . $action . ' | Got: ' . ( $body['action'] ?? 'NULL' ) );
            return false;
        }

        return isset( $body['success'] ) && $body['success'] === true;
    }
}