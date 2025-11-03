<?php
/**
 * Template Name: My IP Checker
 * Description: A tool for users to check their own IP and look up information for any IP or domain.
 */

get_header();

// --- Feature 1: Get the current user's IP and info ---
$user_ip = get_the_user_ip();
$user_ip_details = [];
if ($user_ip !== 'Unknown') {
    $user_ip_details = get_ip_info_from_api($user_ip);
}

// --- Feature 2: Process the form for IP/domain lookup ---
$lookup_host = '';
$lookup_ip = '';
$lookup_results = [];
$error_message = '';

if (isset($_GET['lookup_host']) && !empty($_GET['lookup_host'])) {
    $lookup_host = sanitize_text_field(trim($_GET['lookup_host']));

    if (filter_var($lookup_host, FILTER_VALIDATE_IP)) {
        $lookup_ip = $lookup_host;
    } else {
        $lookup_ip = gethostbyname($lookup_host);
        if ($lookup_ip === $lookup_host) {
            $error_message = 'Không thể phân giải tên miền. Vui lòng kiểm tra lại.';
            $lookup_ip = '';
        }
    }

    if ($lookup_ip) {
        $lookup_results = get_ip_info_from_api($lookup_ip);
        if (empty($lookup_results)) {
             $error_message = 'Không tìm thấy thông tin cho IP hoặc tên miền này.';
        }
    }
}

?>

<div class="my-ip-page-container">
    
    <div class="ip-section my-ip-section">
        <h2><span class="icon">👤</span> IP Của Bạn</h2>
        <div class="ip-info-wrapper">
             <div class="info-row">
                <span class="info-label">Địa chỉ IP</span>
                <span class="info-value"><strong><?php echo esc_html($user_ip); ?></strong></span>
            </div>
            <?php if (!empty($user_ip_details)) : ?>
                <div class="info-row">
                    <span class="info-label">Tên máy chủ</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['hostname']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nhà cung cấp</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['isp']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Đơn vị</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['org']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Quốc gia</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['country']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Khu vực</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['region']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Múi giờ</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['timezone']); ?></span>
                </div>
                 <div class="info-row">
                    <span class="info-label">Châu lục</span>
                    <span class="info-value"><?php echo esc_html($user_ip_details['continent']); ?></span>
                </div>
            <?php else: ?>
                 <div class="info-row">
                    <span class="info-label">Trạng thái</span>
                    <span class="info-value">Không thể tải thông tin chi tiết.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ip-section ip-lookup-section">
        <h2><span class="icon">🔍</span> Công Cụ Tra Cứu IP / Tên Miền</h2>
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="lookup-form" target="_top">
            <input type="text" name="lookup_host" placeholder="Nhập IP hoặc tên miền (ví dụ: google.com)" value="<?php echo isset($lookup_host) ? esc_attr($lookup_host) : ''; ?>" required>
            <button type="submit">Kiểm Tra</button>
        </form>

        <?php if (!empty($lookup_results)) : ?>
            <h3>Kết quả cho: <?php echo esc_html($lookup_host); ?></h3>
            <div class="ip-info-wrapper">
                <div class="info-row">
                    <span class="info-label">Địa chỉ IP</span>
                    <span class="info-value"><strong><?php echo esc_html($lookup_ip); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tên máy chủ</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['hostname']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nhà cung cấp</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['isp']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Đơn vị</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['org']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Quốc gia</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['country']) . ' (' . esc_html($lookup_results['countryCode']) . ')'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Khu vực</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['region']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Múi giờ</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['timezone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Châu lục</span>
                    <span class="info-value"><?php echo esc_html($lookup_results['continent']); ?></span>
                </div>
            </div>
        <?php elseif ($error_message) : ?>
            <p class="error-notice"><?php echo $error_message; ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>