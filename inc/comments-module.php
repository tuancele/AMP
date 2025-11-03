<?php
/**
 * inc/comments-module.php
 * Module Class xử lý hệ thống bình luận AMP tùy chỉnh và tích hợp Turnstile.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AMP_Comments_Module {

    public function __construct() {
        // Hook xử lý submit bình luận AMP
        add_action('wp_ajax_amp_submit_comment', [ $this, 'handle_amp_comment_submission' ]);
        add_action('wp_ajax_nopriv_amp_submit_comment', [ $this, 'handle_amp_comment_submission' ]);

        // Các hàm helper cho bình luận vẫn cần ở global scope
        // để template 'comments.php' có thể gọi
        // (Xem Bước 4.3)
    }

    /**
     * =========================================================================
     * XỬ LÝ SUBMIT BÌNH LUẬN AMP
     *
     * =========================================================================
     */
    public function handle_amp_comment_submission() {
        if (!isset($_POST['_amp_comment_nonce_field']) || !wp_verify_nonce($_POST['_amp_comment_nonce_field'], 'amp_comment_nonce_action')) {
            wp_send_json_error(['message' => 'Xác thực không hợp lệ. Vui lòng tải lại trang và thử lại.'], 403);
        }

        if ( ! function_exists( 'wp_handle_comment_submission_cookies' ) ) require_once( ABSPATH . 'wp-includes/comment.php' );
        header("Content-Type: application/json");
        if (isset($_SERVER['HTTP_ORIGIN'])) { 
            $origin = $_SERVER['HTTP_ORIGIN']; 
            if (strpos($origin, '.ampproject.org') > 0 || strpos($origin, '.amp.cloudflare.com') > 0 || $origin === 'https://' . $_SERVER['HTTP_HOST']) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }
        header("Access-Control-Allow-Credentials: true");
        header("AMP-Access-Control-Allow-Source-Origin: " . 'https://' . $_SERVER['HTTP_HOST']);
        header("Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin");

        $user_ip = function_exists('get_the_user_ip') ? get_the_user_ip() : '';
        $country_code = $this->get_ip_country_code($user_ip); // Gọi hàm nội bộ
        
        $options = get_option('tuancele_turnstile_settings', []);
        if (!empty($options['site_key'])) {
            $turnstile_token = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
            if (!$this->verify_turnstile_token($turnstile_token, $user_ip)) { // Gọi hàm nội bộ
                 wp_send_json_error(['message' => 'Xác minh CAPTCHA thất bại. Vui lòng làm lại.'], 400);
            }
        }
        
        if ($country_code !== null && $country_code !== 'VN') {
            wp_send_json_error(['message' => 'Xin lỗi, tính năng bình luận chỉ dành cho người dùng tại Việt Nam.'], 403);
        }
        
        $post_id = isset($_POST['comment_post_ID']) ? (int)$_POST['comment_post_ID'] : 0;
        if (empty($post_id) || get_post_status($post_id) !== 'publish' || !comments_open($post_id)) {
            wp_send_json_error(['message' => 'Không thể bình luận cho bài viết này.'], 400);
        }
        if (empty(trim($_POST['comment']))) {
            wp_send_json_error(['message' => 'Vui lòng nhập nội dung bình luận.'], 400);
        }

        $comment_data = [ 
            'comment_post_ID' => $post_id, 
            'comment_author' => sanitize_text_field($_POST['author'] ?? ''), 
            'comment_author_email' => sanitize_email($_POST['email'] ?? ''), 
            'comment_author_url' => '',
            'comment_content' => wp_kses_post(trim($_POST['comment'])), 
            'comment_parent' => (int) $_POST['comment_parent'], 
            'user_id' => get_current_user_id(), 
            'comment_agent' => 'AMP-Form', 
            'comment_author_IP' => $user_ip, 
        ];
        $comment_id = wp_new_comment(wp_slash($comment_data));
        if (is_wp_error($comment_id)) { 
            wp_send_json_error(['message' => $comment_id->get_error_message()], 400); 
        } else { 
            wp_send_json_success(); 
        }
        wp_die();
    }

    /**
     * =========================================================================
     * CÁC HÀM HELPER BẢO MẬT (Đã chuyển vào trong Class)
     *
     * =========================================================================
     */
    private function get_turnstile_secret_key() { 
        $options = get_option('tuancele_turnstile_settings', []);
        return $options['secret_key'] ?? '';
    }

    private function verify_turnstile_token($token, $ip) {
        $secret_key = $this->get_turnstile_secret_key();
        if (empty($secret_key) || empty($token)) {
            return false;
        }
        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => ['secret' => $secret_key, 'response' => $token, 'remoteip' => $ip],
        ]);
        if (is_wp_error($response)) { return false; }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['success']) && $body['success'] === true;
    }

    private function get_ip_country_code($ip) {
        if (in_array($ip, ['127.0.0.1', '::1', 'Invalid IP'])) return 'VN';
        $cache_key = 'ip_country_code_' . md5($ip);
        if (false !== ($cached_code = get_transient($cache_key))) return $cached_code;
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,countryCode");
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;
        $data = json_decode(wp_remote_retrieve_body($response));
        if ($data && $data->status === 'success' && isset($data->countryCode)) {
            set_transient($cache_key, $data->countryCode, DAY_IN_SECONDS);
            return $data->countryCode;
        }
        return null;
    }

} // Kết thúc Class

/**
 * =========================================================================
 * CÁC HÀM TEMPLATE (Bắt buộc phải ở Global Scope)
 * Các hàm này được gọi từ template 'comments.php'
 *
 * =========================================================================
 */
function tuancele_amp_comment_callback( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?> id="comment-<?php comment_ID(); ?>">
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php if ( $args['avatar_size'] != 0 ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
                    <b class="fn"><?php echo get_comment_author_link(); ?></b>
                </div>
                <div class="comment-metadata">
                    <a href="<?php echo esc_url( get_comment_link( $comment->comment_ID, $args ) ); ?>">
                        <time datetime="<?php comment_time( 'c' ); ?>">
                            <?php printf( '%1$s vào lúc %2$s', get_comment_date(), get_comment_time() ); ?>
                        </time>
                    </a>
                    <?php edit_comment_link( 'Chỉnh sửa', '<span class="edit-link">', '</span>' ); ?>
                </div>
                <?php if ( $comment->comment_approved == '0' ) : ?>
                <p class="comment-awaiting-moderation">Bình luận của bạn đang chờ duyệt.</p>
                <?php endif; ?>
            </footer>
            <div class="comment-content">
                <?php comment_text(); ?>
            </div>
        </article>
    <?php
}

function tuancele_amp_comment_form() {
    if ( ! comments_open() ) return;
    $commenter = wp_get_current_commenter();
    $form_action_url = esc_url( admin_url('admin-ajax.php?action=amp_submit_comment') );
    
    $options = get_option('tuancele_turnstile_settings', []);
    $turnstile_site_key = $options['site_key'] ?? ''; 
    ?>
    <div id="respond" class="comment-respond">
        <h3 id="reply-title" class="comment-reply-title">Gửi bình luận của bạn</h3>
        <form action-xhr="<?php echo $form_action_url; ?>" method="post" id="commentform" class="comment-form" target="_top">
            <p class="comment-notes">Email của bạn sẽ không được hiển thị công khai.</p>
            <p class="comment-form-comment"><label for="comment">Bình luận *</label><textarea id="comment" name="comment" cols="45" rows="2" maxlength="65525" required placeholder="Viết bình luận của bạn..."></textarea></p>
            <?php if ( ! is_user_logged_in() ) : ?>
                <div class="comment-form-fields-grid">
                    <p class="comment-form-author"><label for="author">Tên *</label><input id="author" name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ); ?>" required></p>
                    <p class="comment-form-email"><label for="email">Email *</label><input id="email" name="email" type="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ); ?>" required></p>
                </div>
            <?php endif; ?>
            <?php wp_nonce_field('amp_comment_nonce_action', '_amp_comment_nonce_field'); ?>
            <?php if (!empty($turnstile_site_key)) : ?>
            <div class="comment-form-captcha" id="cf-turnstile-wrapper">
                <amp-iframe width="300" height="65" layout="fixed" sandbox="allow-scripts"
                    src="<?php echo esc_url(home_url('/turnstile-iframe.html')); ?>"
                    data-loading-strategy="on-visible"
                    resizable>
                    <div overflow tabindex="0" role="button" aria-label="Tải CAPTCHA"></div>
                    <div placeholder on="message:AMP.setState({turnstileState: {token: event.data.token}})"></div>
                </amp-iframe>
                <input type="hidden" name="cf-turnstile-response" [value]="turnstileState.token" required>
            </div>
            <?php endif; ?>
            <input type="hidden" name="wp-comment-cookies-consent" value="yes" />
            <p class="form-submit"><button name="submit" type="submit" id="submit" class="submit-button"><span class="button-text">Gửi đi</span><div class="loader"></div></button></p>
            <input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>"><input type="hidden" name="comment_parent" value="0">
            <div submit-success><template type="amp-mustache"><div class="form-feedback form-success">Cảm ơn! Bình luận của bạn đang chờ duyệt.</div></template></div>
            <div submit-error><template type="amp-mustache"><div class="form-feedback form-error">Lỗi: {{message}}</div></template></div>
        </form>
    </div>
    <?php
}