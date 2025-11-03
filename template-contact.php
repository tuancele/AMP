<?php
/**
 * Template Name: Trang Liên hệ AMP
 * Description: Template hiển thị thông tin liên hệ, form và bản đồ nhúng.
 */

get_header();

// Lấy dữ liệu Schema đã cài đặt
$options = get_option('tuancele_amp_schema_options', []);

$company_name = $options['name'] ?? get_bloginfo('name');
$address = $options['streetAddress'] ?? 'Địa chỉ đang cập nhật';
$phone = $options['hotline_number'] ?? $options['telephone'] ?? '09x xxx xxxx';
$email = $options['email'] ?? 'contact@website.com';
$lat = $options['latitude'] ?? '21.0285';
$lon = $options['longitude'] ?? '105.8542'; 

// Xây dựng URL Google Maps nhúng
$map_embed_url = "https://maps.google.com/maps?q={$lat},{$lon}&hl=vi&z=14&output=embed";

// Kiểm tra xem trang có nội dung không
$has_content = have_posts() && get_the_content();
?>

<div class="container contact-page-container">
    
    <header class="page-header">
        <h1 class="page-title">Liên hệ với chúng tôi</h1>
    </header>

    <?php 
    // Tối ưu 1: Hiển thị breadcrumbs
    if (function_exists('tuancele_amp_display_breadcrumbs')) {
        tuancele_amp_display_breadcrumbs();
    }
    ?>

    <div class="contact-layout-grid">
        
        <div class="contact-info-col">
            
            <?php if ( $has_content ) : the_post(); ?>
                <div class="contact-intro-content">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>

            <div class="contact-details-box">
                <h3 class="contact-box-title">Thông tin liên hệ</h3>
                <ul>
                    <li>
                        <span class="icon">📍</span>
                        <strong>Địa chỉ:</strong> <?php echo esc_html($address); ?>
                    </li>
                    <li>
                        <span class="icon">📞</span>
                        <strong>Hotline:</strong> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                    </li>
                    <li>
                        <span class="icon">📧</span>
                        <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </li>
                    <li>
                        <span class="icon">🏢</span>
                        <strong>Công ty:</strong> <?php echo esc_html($company_name); ?>
                    </li>
                </ul>
            </div>
            
            <?php
            // Tối ưu 2: Thêm form liên hệ bằng shortcode đã có
            echo do_shortcode('[form_dang_ky tieu_de="Đăng Ký Tư Vấn Nhanh" nut_gui="Gửi Yêu Cầu Tư Vấn"]');
            ?>

        </div>
        
        <div class="contact-map-col">
            <h3 class="contact-box-title">Vị trí trên bản đồ</h3>
            <amp-iframe width="600" height="400" layout="responsive" 
                        sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
                        src="<?php echo esc_url($map_embed_url); ?>"
                        frameborder="0">
                <div placeholder class="map-placeholder">Đang tải bản đồ...</div>
            </amp-iframe>
        </div>
        
    </div>
</div>

<?php 
get_footer();