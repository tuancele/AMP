<?php
/**
 * Template Name: Live Status Checker
 * Description: Hiển thị trạng thái hoạt động của nhiều dịch vụ/website.
 */

// =========================================================
// DANH SÁCH CẤU HÌNH KIỂM TRA (Đã bao gồm trong functions.php)
// Cần định nghĩa lại ở đây để sử dụng trong page template
// =========================================================
$services_to_check = [
    ['domain' => 'vpnmisa.com', 'port' => 443, 'name' => 'Viettel IDC'],
    ['domain' => 'google.com', 'port' => 443, 'name' => 'VNPT Hanoi'],
    ['domain' => 'cloudflare.com', 'port' => 443, 'name' => 'Azdigi Hochiminh'],
];

// Mảng chứa kết quả kiểm tra
$status_results = [];
$last_checked = ''; // Biến lưu thời gian kiểm tra

// Gọi hàm kiểm tra từ functions.php
if (function_exists('tuancele_get_cached_live_status')) {
    // Thời gian cache được đặt là 300 giây (5 phút)
    $cache_duration = 300; 

    foreach ($services_to_check as $service) {
        // GỌI HÀM CÓ SỬ DỤNG CACHE TRANSIENT
        // Hàm sẽ tự kiểm tra thực tế nếu cache hết hạn
        $status = tuancele_get_cached_live_status($service['domain'], $service['port'], $cache_duration); 
        
        $status_results[] = [
            'name'    => $service['name'],
            'domain'  => $service['domain'],
            'status'  => $status,
            'message' => ($status === 'online') ? 'Đang hoạt động' : 'Ngoại tuyến',
        ];
    }
    
    // Lấy thời gian lần cuối kiểm tra
    $first_service_key = 'live_status_' . sanitize_title($services_to_check[0]['domain']) . '_' . $services_to_check[0]['port'];
    $last_checked_timestamp = get_option('_transient_timeout_' . $first_service_key);
    
    if ($last_checked_timestamp) {
        // Thời gian kiểm tra thực tế = Thời gian hết hạn cache - Thời gian cache đã đặt (300 giây)
        $last_checked = date('H:i:s, d/m/Y', $last_checked_timestamp - $cache_duration); 
    } else {
        // Cache chưa tồn tại (ví dụ: lần tải đầu tiên)
        // Nếu không có transient timeout, nghĩa là kết quả vừa được tạo ra.
        $last_checked = date('H:i:s, d/m/Y'); 
    }

} else {
    // Xử lý lỗi nếu hàm kiểm tra không tồn tại
    $status_results[] = ['name' => 'Lỗi', 'domain' => 'Hệ thống', 'status' => 'error', 'message' => 'Lỗi: Hàm kiểm tra không tồn tại.'];
    $last_checked = date('H:i:s, d/m/Y');
}

// Hàm render SVG (Giữ nguyên)
function render_status_icon($status) {
    if ($status === 'online') {
        return '<svg class="status-svg status-online-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" /></svg>';
    }
    return '<svg class="status-svg status-offline-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" /></svg>';
}

get_header(); // Gọi header của theme AMP
?>

<div class="live-status-wrapper">
    <h1 class="status-title">TRẠNG THÁI MÁY CHỦ VPN</h1>
    <p class="status-intro">Kiểm tra kết nối tới dịch vụ VPN quan trọng.</p>
    
    <div class="status-list">
        <?php foreach ($status_results as $result) : ?>
        <div class="status-item status-<?php echo esc_attr($result['status']); ?>">
            <div class="status-icon-small">
                <?php echo render_status_icon($result['status']); ?>
            </div>
            <div class="status-details">
                <h3 class="item-name"><?php echo esc_html($result['name']); ?></h3>
                <p class="item-domain"><?php echo esc_html($result['domain']); ?>: 
                    <span class="item-status-text status-text-<?php echo esc_attr($result['status']); ?>">
                        <?php echo esc_html($result['message']); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <p class="last-checked">Thời gian kiểm tra gần nhất: <?php echo $last_checked; ?> (Dữ liệu cache 5 phút)</p>
</div>

<?php 
// Hiển thị nội dung nếu có
if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div class="page-content"><?php the_content(); ?></div>
<?php endwhile; endif; ?>

<?php get_footer(); // Gọi footer của theme AMP ?>