<?php
/**
 * inc/qapage/class-qapage-ajax.php
 *
 * Xử lý tất cả các tương tác AMP XHR (AJAX) cho module QAPage.
 *
 * [FIX V5] Đã sửa lỗi 404: Nâng vai trò (role) của user mới đăng ký
 * từ 'Subscriber' (mặc định) lên 'Author' (Tác giả) để
 * họ có quyền 'publish_posts' (theo 'capability_type' => 'post').
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Ajax {

    private $recaptcha_secret_key = '';
    private $is_recaptcha_enabled = false;

    /**
     * Khởi tạo, tải cài đặt reCaptcha và đăng ký các hook AJAX.
     */
    public function __construct() {
        // Tải cài đặt reCaptcha MỘT LẦN (tái sử dụng từ cài đặt theme)
        $options = get_option( 'tuancele_recaptcha_settings', [] );
        $this->is_recaptcha_enabled = isset( $options['enable_recaptcha'] ) && $options['enable_recaptcha'] === 'on';
        
        if ( $this->is_recaptcha_enabled ) {
            $this->recaptcha_secret_key = $options['recaptcha_v3_secret_key'] ?? '';
        }

        // --- Đăng ký Endpoints (Lối vào) ---

        // Lối vào 1: Người dùng đã đăng nhập đặt câu hỏi
        add_action( 'wp_ajax_qapage_ask', [ $this, 'handle_qapage_ask' ] );
        
        // Lối vào 2: Khách (ẩn danh) đặt câu hỏi (sẽ tự động đăng ký)
        add_action( 'wp_ajax_nopriv_qapage_register_and_ask', [ $this, 'handle_qapage_register_and_ask' ] );
        
        // Lối vào 3: Bỏ phiếu (Vote)
        add_action( 'wp_ajax_qapage_vote', [ $this, 'handle_qapage_vote' ] );
        add_action( 'wp_ajax_nopriv_qapage_vote', [ $this, 'handle_ajax_must_be_logged_in' ] );
        
        // Lối vào 4: Chấp nhận Câu trả lời (Accept)
        add_action( 'wp_ajax_qapage_accept_answer', [ $this, 'handle_qapage_accept_answer' ] );
        add_action( 'wp_ajax_nopriv_qapage_accept_answer', [ $this, 'handle_ajax_must_be_logged_in' ] );
    }

    /**
     * Xử lý khi KHÁCH gửi form "Đăng ký & Đặt câu hỏi".
     */
    public function handle_qapage_register_and_ask() {
        $this->send_amp_headers(); // Gửi AMP JSON headers
        check_ajax_referer( 'qapage_ask_nonce', '_ajax_nonce' );

        // 1. Xác thực reCaptcha
        if ( ! $this->verify_recaptcha( 'qapage_ask_form' ) ) {
            wp_send_json_error( [ 'message' => 'Xác minh reCaptcha thất bại. Bạn có phải là robot?' ], 403 );
        }

        // 2. Lấy và Validate dữ liệu đăng ký
        $email    = sanitize_email( $_POST['user_email'] ?? '' );
        $name     = sanitize_text_field( $_POST['user_name'] ?? '' );
        $pass     = $_POST['user_pass'] ?? '';
        
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Địa chỉ email không hợp lệ.' ], 400 );
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error( [ 'message' => 'Email này đã tồn tại. Vui lòng đăng nhập để đặt câu hỏi.' ], 400 );
        }
        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Vui lòng nhập Tên hiển thị.' ], 400 );
        }
        if ( empty( $pass ) || strlen( $pass ) < 6 ) {
            wp_send_json_error( [ 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.' ], 400 );
        }

        // 3. Tạo User Mới
        $user_id = wp_create_user( $email, $pass, $email );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => 'Không thể tạo tài khoản: ' . $user_id->get_error_message() ], 500 );
        }
        
        // [FIX V5] Nâng vai trò user lên 'Author' để có quyền 'publish_posts'
        // Đồng thời cập nhật display_name
        wp_update_user( [ 
            'ID'           => $user_id, 
            'role'         => 'author', 
            'display_name' => $name 
        ] );
        
        // 4. Tự động Đăng nhập
        wp_set_auth_cookie( $user_id );

        // 5. Đăng Câu hỏi (với ID user mới)
        // Vì user đã là 'Author', bài viết sẽ được 'publish' ngay lập tức.
        $this->create_qapage_post( $user_id );
    }

    /**
     * Xử lý khi NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP đặt câu hỏi.
     */
    public function handle_qapage_ask() {
        $this->send_amp_headers();
        check_ajax_referer( 'qapage_ask_nonce', '_ajax_nonce' );
        
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Bạn phải đăng nhập để đặt câu hỏi.' ], 403 );
        }

        // 1. Xác thực reCaptcha (Bắt buộc cho cả user đã đăng nhập)
        if ( ! $this->verify_recaptcha( 'qapage_ask_form' ) ) {
            wp_send_json_error( [ 'message' => 'Xác minh reCaptcha thất bại. Hãy thử lại.' ], 403 );
        }
        
        // 2. Đăng Câu hỏi
        $this->create_qapage_post( get_current_user_id() );
    }

    /**
     * Hàm lõi để tạo bài viết CPT 'qapage_question'.
     */
    private function create_qapage_post( $user_id ) {
        $title   = sanitize_text_field( $_POST['question_title'] ?? '' );
        $content = wp_kses_post( $_POST['question_content'] ?? '' );
        $context_url = esc_url_raw( $_POST['context_url'] ?? '' ); // Lấy URL liên kết

        if ( empty( $title ) || empty( $content ) ) {
            wp_send_json_error( [ 'message' => 'Vui lòng nhập cả Tiêu đề và Nội dung câu hỏi.' ], 400 );
        }
        
        // Người dùng (Author hoặc Admin) đã có quyền 'publish_posts'
        // nên bài viết sẽ được publish
        $post_data = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'qapage_question',
        ];
        
        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) || $post_id == 0 ) {
            wp_send_json_error( [ 'message' => 'Lỗi máy chủ, không thể đăng câu hỏi.' ], 500 );
        }

        // Nếu có URL liên kết, lưu nó vào meta
        if ( ! empty( $context_url ) ) {
            update_post_meta( $post_id, '_qapage_related_context_url', $context_url );
        }

        // Thành công! Chuyển hướng người dùng đến câu hỏi mới của họ.
        $redirect_url = get_permalink( $post_id );
        header( "AMP-Redirect-To: " . $redirect_url );
        header( "Access-Control-Expose-Headers: AMP-Redirect-To" );
        
        wp_send_json_success( [ 'message' => 'Đăng câu hỏi thành công!', 'redirect_url' => $redirect_url ] );
    }

    /**
     * Xử lý Bỏ phiếu (Vote) cho một Câu trả lời (Answer).
     */
    public function handle_qapage_vote() {
        $this->send_amp_headers();
        check_ajax_referer( 'qapage_vote_nonce', '_ajax_nonce' );
        
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Vui lòng đăng nhập để bỏ phiếu.' ], 403 );
        }

        $comment_id = absint( $_POST['comment_id'] ?? 0 );
        $direction  = sanitize_key( $_POST['direction'] ?? '' ); // 'up' hoặc 'down'
        $user_id    = get_current_user_id();

        if ( $comment_id == 0 || ! in_array( $direction, [ 'up', 'down' ] ) ) {
            wp_send_json_error( [ 'message' => 'Dữ liệu không hợp lệ.' ], 400 );
        }

        $comment = get_comment( $comment_id );
        // Ngăn user tự vote cho câu trả lời của mình
        if ( $comment->user_id == $user_id ) {
            wp_send_json_error( [ 'message' => 'Bạn không thể tự bỏ phiếu.' ], 403 );
        }

        // Lấy danh sách vote hiện tại
        $voters_up   = get_comment_meta( $comment_id, '_voters_up', true ) ?: [];
        $voters_down = get_comment_meta( $comment_id, '_voters_down', true ) ?: [];

        $user_has_voted_up = in_array( $user_id, $voters_up );
        $user_has_voted_down = in_array( $user_id, $voters_down );

        if ( $direction === 'up' ) {
            // Xóa vote down (nếu có)
            $voters_down = array_diff( $voters_down, [ $user_id ] );
            // Thêm/Xóa vote up
            if ( $user_has_voted_up ) {
                $voters_up = array_diff( $voters_up, [ $user_id ] ); // Bỏ vote
            } else {
                $voters_up[] = $user_id; // Thêm vote
            }
        } elseif ( $direction === 'down' ) {
            // Xóa vote up (nếu có)
            $voters_up = array_diff( $voters_up, [ $user_id ] );
            // Thêm/Xóa vote down
            if ( $user_has_voted_down ) {
                $voters_down = array_diff( $voters_down, [ $user_id ] ); // Bỏ vote
            } else {
                $voters_down[] = $user_id; // Thêm vote
            }
        }
        
        // Tính điểm mới
        $new_score = count( $voters_up ) - count( $voters_down );
        
        // Cập nhật CSDL
        update_comment_meta( $comment_id, '_voters_up', $voters_up );
        update_comment_meta( $comment_id, '_voters_down', $voters_down );
        update_comment_meta( $comment_id, '_vote_score', $new_score );

        wp_send_json_success( [ 'new_score' => $new_score ] );
    }

    /**
     * Xử lý Chấp nhận (Accept) một Câu trả lời.
     */
    public function handle_qapage_accept_answer() {
        $this->send_amp_headers();
        check_ajax_referer( 'qapage_accept_nonce', '_ajax_nonce' );
        
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Vui lòng đăng nhập.' ], 403 );
        }

        $comment_id = absint( $_POST['comment_id'] ?? 0 );
        $user_id    = get_current_user_id();
        $comment    = get_comment( $comment_id );

        if ( ! $comment ) {
            wp_send_json_error( [ 'message' => 'Câu trả lời không tồn tại.' ], 404 );
        }

        $post = get_post( $comment->comment_post_ID );

        // Kiểm tra quyền: Phải là Tác giả Câu hỏi HOẶC Admin
        if ( $user_id != $post->post_author && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Bạn không có quyền thực hiện hành động này.' ], 403 );
        }

        $old_accepted_id = get_post_meta( $post->ID, '_qapage_accepted_answer_id', true );
        $new_status = '';

        if ( $old_accepted_id == $comment_id ) {
            // Bỏ chấp nhận (Un-accept)
            delete_post_meta( $post->ID, '_qapage_accepted_answer_id' );
            $new_status = 'unaccepted';
        } else {
            // Chấp nhận câu trả lời này
            update_post_meta( $post->ID, '_qapage_accepted_answer_id', $comment_id );
            $new_status = 'accepted';
        }

        wp_send_json_success( [ 'status' => $new_status, 'comment_id' => $comment_id ] );
    }

    /**
     * [HÀM MỚI] Xử lý lỗi cho khách (nopriv)
     * Trả về lỗi JSON 403 thay vì để WordPress trả về 403 thô.
     */
    public function handle_ajax_must_be_logged_in() {
        $this->send_amp_headers(); // Gửi AMP JSON headers
        wp_send_json_error( [ 'message' => 'Bạn phải đăng nhập để thực hiện hành động này.' ], 403 );
    }

    /**
     * =========================================================================
     * HÀM HELPER (Bảo mật & AMP)
     * =========================================================================
     */

    /**
     * Gửi headers JSON tiêu chuẩn cho AMP XHR.
     */
    private function send_amp_headers() {
        if ( headers_sent() ) {
            return;
        }
        // Lấy domain gốc (ví dụ: https://domain.com)
        $domain_origin = 'https://' . sanitize_text_field( $_SERVER['HTTP_HOST'] );
        // Lấy domain cache AMP (ví dụ: https://domain-com.cdn.ampproject.org)
        $cdn_origin = 'https://' . str_replace( '.', '-', sanitize_text_field( $_SERVER['HTTP_HOST'] ) ) . '.cdn.ampproject.org';

        header( "Content-Type: application/json" );
        header( "access-control-allow-credentials: true" );
        header( "access-control-allow-origin: " . $cdn_origin );
        header( "AMP-Access-Control-Allow-Source-Origin: " . $domain_origin );
    }

    /**
     * Xác thực reCaptcha (Tái sử dụng từ logic Security).
     */
    private function verify_recaptcha( $action ) {
        if ( ! $this->is_recaptcha_enabled || empty( $this->recaptcha_secret_key ) ) {
            // Nếu reCaptcha bị tắt, coi như xác thực thành công
            return true;
        }

        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if ( empty( $recaptcha_token ) ) {
            return false;
        }

        return $this->verify_recaptcha_token( $recaptcha_token, $user_ip, $action );
    }
    
    /**
     * Hàm logic thô để xác thực token (Tái sử dụng từ module cũ).
     */
    private function verify_recaptcha_token( $token, $ip, $action ) {
        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $this->recaptcha_secret_key,
                'response' => $token,
                'remoteip' => $ip
            ],
        ] );
        
        if ( is_wp_error( $response ) ) { 
            error_log( 'QAPage AJAX reCAPTCHA WP_Error: ' . $response->get_error_message() );
            return false; 
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( ! isset( $body['action'] ) || $body['action'] !== $action ) {
            error_log( 'QAPage AJAX reCAPTCHA Action Mismatch. Expected: ' . $action . ' | Got: ' . ( $body['action'] ?? 'NULL' ) );
            return false;
        }
        
        return isset( $body['success'] ) && $body['success'] === true;
    }
}