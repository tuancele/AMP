<?php
/**
 * inc/integrations.php
 * Tích hợp các dịch vụ bên ngoài: Zoho, SMTP, Cloudflare R2.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * =========================================================================
 * ASYNC (BACKGROUND) ACTIONS
 * =========================================================================
 */

/**
 * Hàm này được gọi bởi WP-Cron trong nền để gửi email thông báo.
 * Nó nhận dữ liệu khách hàng và gọi hàm gửi email.
 */
function tuancele_send_notification_email_async($arg) {
    // Chỉ cần gọi lại hàm gửi email hiện có
    send_notification_email($arg);
}
// Hook hàm trên vào một action tùy chỉnh. WP-Cron sẽ gọi action này.
add_action('tuancele_send_form_notification', 'tuancele_send_notification_email_async', 10, 1);


// =========================================================================
// ZOHO & FORM SUBMISSION
// =========================================================================

function cele_zoho ($arg) {
    $integration_options = get_option('tuancele_integrations_settings', []);
    $zoho_key_1 = $integration_options['zoho_xnqsjsdp'] ?? '';
    $zoho_key_2 = $integration_options['zoho_xmiwtld'] ?? '';

    if (empty($zoho_key_1) || empty($zoho_key_2)) {
        return;
    }

    wp_remote_post( "https://crm.zoho.com/crm/WebToLeadForm", [ 
        'method' => 'POST', 
        'timeout' => 15, 
        'body' => [ 
            'Last Name' => !empty($arg['name']) ? $arg['name'] : 'Khách từ trang AMP', 
            'Mobile' => !empty($arg['phone']) ? $arg['phone'] : '', 
            'Email' => !empty($arg['email']) ? $arg['email'] : '', 
            'Website' => !empty($arg['link']) ? $arg['link'] : 'Không có', 
            'xnQsjsdp' => $zoho_key_1, 
            'xmIwtLD' => $zoho_key_2, 
            'actionType' => 'TGVhZHM=' 
        ] 
    ]);
}

function send_notification_email($arg) {
    $smtp_options = get_option('tuancele_smtp_settings', []);
    $recipient_email = !empty($smtp_options['notification_email']) ? sanitize_email($smtp_options['notification_email']) : get_option('admin_email');

    if (empty($recipient_email)) {
        return;
    }

    $to = $recipient_email;
    
    $subject = sprintf(
        'Khách hàng: %s - Họ tên: %s - ĐK từ: %s',
        esc_html($arg['phone']),
        esc_html($arg['name']),
        esc_url($arg['link'])
    );
    
    $body = '<html><body><h2>Bạn có một lượt đăng ký mới từ website:</h2><table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;"><tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Họ và Tên:</th><td style="padding: 8px;">' . esc_html($arg['name']) . '</td></tr><tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Số Điện Thoại:</th><td style="padding: 8px;">' . esc_html($arg['phone']) . '</td></tr><tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Email:</th><td style="padding: 8px;">' . esc_html($arg['email']) . '</td></tr><tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Đăng ký từ trang:</th><td style="padding: 8px;"><a href="' . esc_url($arg['link']) . '">' . esc_url($arg['link']) . '</a></td></tr></table></body></html>';
    wp_mail( $to, $subject, $body, ['Content-Type: text/html; charset=UTF-8'] );
}

// Hàm xử lý cho form đầy đủ [form_dang_ky]
function namespace_handle_amp_form_submit() {
    check_ajax_referer('amp_form_nonce_action', '_amp_form_nonce_field');
    
    $domain_origin = 'https://' . sanitize_text_field($_SERVER['HTTP_HOST']);
    $cdn_origin = 'https://' . str_replace('.', '-', sanitize_text_field($_SERVER['HTTP_HOST'])) . '.cdn.ampproject.org';
    header("Content-Type: application/json");
    header("access-control-allow-credentials: true");
    header("access-control-allow-origin: " . $cdn_origin);
    header("AMP-Access-Control-Allow-Source-Origin: " . $domain_origin);
    header("access-control-expose-headers: AMP-Access-Control-Allow-Source-Origin, AMP-Redirect-To");
    
    $phone = isset($_POST['Mobile']) ? sanitize_text_field($_POST['Mobile']) : '';
    if (empty($phone) || !preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
        wp_send_json_error(['message' => 'Số điện thoại không hợp lệ. Vui lòng kiểm tra lại.'], 400);
        die();
    }
    
    $arg = [ 'phone' => $phone, 'name'  => isset($_POST['Name']) ? sanitize_text_field($_POST['Name']) : 'Chưa có tên', 'email' => isset($_POST['Email']) ? sanitize_email($_POST['Email']) : '', 'link'  => isset($_POST['link']) ? esc_url_raw($_POST['link']) : 'N/A' ];
    
    cele_zoho($arg);
    // [OPTIMIZED] Lên lịch gửi email trong nền thay vì gửi trực tiếp
    wp_schedule_single_event(time(), 'tuancele_send_form_notification', array($arg));
    
// [FIX] TẠO TOKEN VÀ DỮ LIỆU TRACKING
    $token = wp_generate_password(24, false); // Tạo một chuỗi ngẫu nhiên
    $tracking_data = [
        'phone_hash' => hash('sha256', $phone), // Mã hóa SĐT để an toàn cho GTM
        'name' => $name
    ];
    // Lưu dữ liệu tạm thời trong 5 phút
    set_transient('thankyou_token_' . $token, $tracking_data, 5 * MINUTE_IN_SECONDS);

    // Tạo URL chuyển hướng mới
    $redirect_url = add_query_arg('token', $token, home_url('/cam-on/'));
    
    header("AMP-Redirect-To: " . $redirect_url);
    wp_send_json_success(['message' => 'Success']);
    die();
}
add_action("wp_ajax_amp_form_submit", "namespace_handle_amp_form_submit");
add_action("wp_ajax_nopriv_amp_form_submit", "namespace_handle_amp_form_submit");

// Hàm render HTML cho form đầy đủ [form_dang_ky]
function get_amp_form_html($args) {
    $form_action_url = esc_url(admin_url('admin-ajax.php?action=amp_form_submit'));
    $current_page_link = (is_singular() ? get_permalink() : home_url(add_query_arg(null, null)));
    ob_start(); ?>
    <div class="amp-form-container">
        <div class="form-title"><?php echo esc_html($args['tieu_de']); ?></div>
        <form method="POST" target="_top" action-xhr="<?php echo $form_action_url; ?>">
            <div class="form-row">
                <label for="form-name-<?php echo uniqid(); ?>">Họ và Tên:</label>
                <input type="text" id="form-name-<?php echo uniqid(); ?>" name="Name" placeholder="Ví dụ: Nguyễn Văn A" required>
                <div visible-when-invalid="valueMissing" validation-for="form-name-<?php echo uniqid(); ?>" class="validation-error">Vui lòng nhập họ tên của bạn.</div>
            </div>
            <div class="form-row">
                <label for="form-phone-<?php echo uniqid(); ?>">Số Điện Thoại:</label>
                <input type="tel" id="form-phone-<?php echo uniqid(); ?>" name="Mobile" placeholder="Nhập SĐT để nhận tư vấn" required pattern="(03|05|07|08|09)[0-9]{8}">
                <div visible-when-invalid="valueMissing" validation-for="form-phone-<?php echo uniqid(); ?>" class="validation-error">Vui lòng nhập số điện thoại.</div>
                <div visible-when-invalid="patternMismatch" validation-for="form-phone-<?php echo uniqid(); ?>" class="validation-error">Số điện thoại không đúng định dạng.</div>
            </div>
            <div class="form-row"><label for="form-email-<?php echo uniqid(); ?>">Email (Không bắt buộc):</label><input type="email" id="form-email-<?php echo uniqid(); ?>" name="Email" placeholder="vidu@email.com"></div>
            
            <?php
            // [SỬA ĐỔI] Thêm trường Nonce vào form
            wp_nonce_field('amp_form_nonce_action', '_amp_form_nonce_field');
            ?>

            <input type="hidden" name="link" value="<?php echo esc_url($current_page_link); ?>">
            <div class="form-row"><button type="submit" class="submit-button"><span class="button-text"><?php echo esc_html($args['nut_gui']); ?></span><div class="loader"></div></button></div>
            <div submit-success><div class="form-feedback form-success"><span>Đăng ký thành công! Chúng tôi sẽ sớm liên hệ với bạn.</span></div></div>
            <div submit-error><div class="form-feedback form-error"><span>Đã có lỗi xảy ra, vui lòng thử lại!</span></div></div>
        </form>
    </div>
    <?php return ob_get_clean();
}

/**
 * =========================================================================
 * XỬ LÝ FORM ĐĂNG KÝ CHỈ CÓ SỐ ĐIỆN THOẠI [dang_ky_sdt]
 * =========================================================================
 */
function tuancele_handle_phone_only_submit() {
    check_ajax_referer('amp_form_nonce_action', '_amp_form_nonce_field');

    $domain_origin = 'https://' . sanitize_text_field($_SERVER['HTTP_HOST']);
    $cdn_origin = 'https://' . str_replace('.', '-', sanitize_text_field($_SERVER['HTTP_HOST'])) . '.cdn.ampproject.org';
    header("Content-Type: application/json");
    header("access-control-allow-credentials: true");
    header("access-control-allow-origin: " . $cdn_origin);
    header("AMP-Access-Control-Allow-Source-Origin: " . $domain_origin);
    header("access-control-expose-headers: AMP-Access-Control-Allow-Source-Origin, AMP-Redirect-To");

    $phone = isset($_POST['Mobile']) ? sanitize_text_field($_POST['Mobile']) : '';
    if (empty($phone) || !preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $phone)) {
        wp_send_json_error(['message' => 'Số điện thoại không hợp lệ. Vui lòng kiểm tra lại.'], 400);
        die();
    }

    $arg = [
        'phone' => $phone,
        'name'  => 'Khách đăng ký SĐT',
        'email' => '',
        'link'  => isset($_POST['link']) ? esc_url_raw($_POST['link']) : 'N/A'
    ];

    cele_zoho($arg);
    // [OPTIMIZED] Lên lịch gửi email trong nền thay vì gửi trực tiếp
    wp_schedule_single_event(time(), 'tuancele_send_form_notification', array($arg));

// [FIX] TẠO TOKEN VÀ DỮ LIỆU TRACKING
    $token = wp_generate_password(24, false);
    $tracking_data = [
        'phone_hash' => hash('sha256', $phone),
        'name' => $name
    ];
    set_transient('thankyou_token_' . $token, $tracking_data, 5 * MINUTE_IN_SECONDS);

    $redirect_url = add_query_arg('token', $token, home_url('/cam-on/'));

    header("AMP-Redirect-To: " . $redirect_url);
    wp_send_json_success(['message' => 'Success']);
    die();
}
add_action("wp_ajax_amp_submit_phone_only", "tuancele_handle_phone_only_submit");
add_action("wp_ajax_nopriv_amp_submit_phone_only", "tuancele_handle_phone_only_submit");


// =========================================================================
// SMTP INTEGRATION
// =========================================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if ( file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
}
function tuancele_smtp_handle_settings_update($old_value, $new_value) {
    if (!isset($new_value['enable_smtp']) || $new_value['enable_smtp'] !== 'on') { update_option('tuancele_smtp_connection_status', ['success' => true, 'message' => 'Đã tắt. Sử dụng hàm mail mặc định của máy chủ.']); return; }
    if (empty($new_value['smtp_user']) || empty($new_value['smtp_pass'])) { update_option('tuancele_smtp_connection_status', ['success' => false, 'message' => 'Thất bại - Vui lòng điền đầy đủ Tài khoản và Mật khẩu.']); return; }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = $new_value['smtp_host']; $mail->SMTPAuth = true; $mail->Username = $new_value['smtp_user']; $mail->Password = $new_value['smtp_pass']; $mail->SMTPSecure = $new_value['smtp_secure']; $mail->Port = $new_value['smtp_port'];
        if ($mail->smtpConnect()) { $mail->smtpClose(); update_option('tuancele_smtp_connection_status', ['success' => true, 'message' => 'Kết nối thành công!']);
        } else { update_option('tuancele_smtp_connection_status', ['success' => false, 'message' => 'Kết nối thất bại: ' . $mail->ErrorInfo]); }
    } catch (Exception $e) { update_option('tuancele_smtp_connection_status', ['success' => false, 'message' => 'Kết nối thất bại: ' . $mail->ErrorInfo]); }
}
add_action('update_option_tuancele_smtp_settings', 'tuancele_smtp_handle_settings_update', 10, 2);
function tuancele_configure_smtp( $phpmailer ) {
    $options = get_option('tuancele_smtp_settings', []);
    if (!isset($options['enable_smtp']) || $options['enable_smtp'] !== 'on' || empty($options['smtp_user'])) return;
    $phpmailer->isSMTP(); $phpmailer->Host = $options['smtp_host']; $phpmailer->SMTPAuth = true; $phpmailer->Port = $options['smtp_port']; $phpmailer->Username = $options['smtp_user']; $phpmailer->Password = $options['smtp_pass']; $phpmailer->SMTPSecure = $options['smtp_secure']; $phpmailer->From = $options['smtp_user']; $phpmailer->FromName = get_bloginfo('name');
}
add_action('phpmailer_init', 'tuancele_configure_smtp');

