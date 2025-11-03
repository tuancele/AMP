<?php
/**
 * Template Name: Trang Cảm Ơn
 * Phiên bản nâng cấp, bảo vệ trang và tích hợp GTM dataLayer.
 */

// [FIX] BẢO VỆ TRANG VÀ LẤY DỮ LIỆU TRACKING
$token = isset($_GET['token']) ? sanitize_key($_GET['token']) : '';
$tracking_data = false;

if (!empty($token)) {
    $transient_key = 'thankyou_token_' . $token;
    // Thử lấy dữ liệu từ transient
    $tracking_data = get_transient($transient_key);

    if ($tracking_data !== false) {
        // Nếu có dữ liệu hợp lệ, xóa ngay token để không thể dùng lại
        delete_transient($transient_key);
    }
}

// Nếu không có token hợp lệ (truy cập trực tiếp hoặc token hết hạn/sai)
if ($tracking_data === false) {
    // Chuyển hướng về trang chủ
    wp_redirect(home_url('/'));
    exit;
}

// Nếu mọi thứ hợp lệ, tiếp tục hiển thị trang
get_header(); 
?>

<div class="thank-you-wrapper">
    <div class="thank-you-container">
        <div class="thank-you-icon"></div>
        <h1 class="thank-you-title">Đăng Ký Thành Công!</h1>
        <p class="thank-you-message">
            Cảm ơn bạn đã quan tâm. <br>
            Chúng tôi đã nhận được thông tin và sẽ liên hệ lại với bạn trong thời gian sớm nhất.
        </p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="thank-you-back-home">Quay Về Trang Chủ</a>
    </div>
</div>

<?php
// [FIX] ĐẨY DỮ LIỆU VÀO GTM DATALAYER
// Chỉ thực hiện nếu có dữ liệu tracking hợp lệ
if (is_array($tracking_data)) {
    ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'form_submission_success',
            'leadData': {
                'phone_hash': '<?php echo esc_js($tracking_data['phone_hash']); ?>',
                'name': '<?php echo esc_js($tracking_data['name']); ?>'
            }
        });
    </script>
    <?php
}

get_footer();
?>