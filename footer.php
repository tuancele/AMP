<?php
/**
 * footer.php - Phiên bản đã được tối ưu UX, tích hợp các nút nổi và nút về đầu trang.
 */

// Lấy giá trị từ cài đặt Schema
$schema_options = get_option('tuancele_amp_schema_options');

// (FIX) Lấy hotline VÀ mô tả ngắn từ trang cài đặt Schema
$hotline = !empty($schema_options['hotline_number']) ? $schema_options['hotline_number'] : '0945 740 016';
$description = !empty($schema_options['description']) ? $schema_options['description'] : get_bloginfo('description'); // Lấy mô tả, nếu rỗng thì dùng tagline mặc định

// Xóa các ký tự không phải số để tạo link tel:
$clean_hotline = preg_replace('/[^0-9+]/', '', $hotline);
?>
        </main> <?php // Thẻ đóng của main.container ?>

    </div> <?php // Thẻ đóng của div.site-content-wrapper ?>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col footer-info">
                    <div class="footer-title"><?php bloginfo('name'); ?></div>
                    <p><?php echo esc_html($description); ?></p> <?php // <-- LỖI ĐÃ ĐƯỢỢC SỬA Ở ĐÂY ?>
                    <span class="footer-hotline">
                        <strong>HOTLINE HỖ TRỢ</strong>
                        <a href="tel:<?php echo esc_attr($clean_hotline); ?>"><?php echo esc_html($hotline); ?></a>
                    </span>
                </div>

                <div class="footer-col footer-links-1">
                    <div class="footer-title">Liên Kết Nhanh</div>
                    <?php 
                    if (has_nav_menu('footer_menu_1')) {
                        wp_nav_menu([
                            'theme_location' => 'footer_menu_1', 
                            'container' => false, 
                            'menu_class' => 'footer-menu-list'
                        ]);
                    } else {
                        echo '<ul><li><a href="#">Dịch vụ</a></li><li><a href="#">Bảng giá</a></li><li><a href="#">Hỗ trợ</a></li></ul>';
                    }
                    ?>
                </div>

                <div class="footer-col footer-social">
                    <div class="footer-title">Theo Dõi Chúng Tôi</div>
                    <ul>
                        <li><a href="https://www.facebook.com/bomygao/" target="_blank">Facebook</a></li>
                        <li><a href="#" target="_blank">Youtube</a></li>
                        <li><a href="https://zalo.me/0945740016" target="_blank">Zalo</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
             <div class="container">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved. | IP của bạn: <?php if (function_exists('get_the_user_ip')) { echo esc_html(get_the_user_ip()); } ?>
            </div>
        </div>
    </footer>

    <amp-lightbox id="form-lightbox" layout="nodisplay">
        <div class="lightbox-container">
            <button class="close-button" on="tap:form-lightbox.close" role="button" tabindex="0" aria-label="Đóng Form">×</button>
            <?php
            if (function_exists('get_amp_form_html')) {
                echo get_amp_form_html([
                    'tieu_de' => 'Nhận Báo Giá Miễn Phí',
                    'nut_gui' => 'Gửi Yêu Cầu Ngay'
                ]);
            }
            ?>
        </div>
    </amp-lightbox>

    <?php
    /**
     * =========================================================================
     * CÁC NÚT NỔI (FLOATING BUTTONS) & NÚT VỀ ĐẦU TRANG
     * =========================================================================
     */
    $floating_buttons_options = get_option('tuancele_floating_buttons_options', []);
    
    // 1. Nút Gọi (bên phải)
    $is_call_button_enabled = isset($floating_buttons_options['enable_call_button']) && $floating_buttons_options['enable_call_button'] === 'on';
    if ($is_call_button_enabled && !empty($hotline)) {
        ?>
        <a href="tel:<?php echo esc_attr($clean_hotline); ?>" class="floating-call-button" aria-label="Gọi ngay <?php echo esc_html($hotline); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
            </svg>
        </a>
        <?php
    }

    // 2. Nút Form (bên trái hoặc bên phải, phía trên nút gọi)
    $is_form_button_enabled = isset($floating_buttons_options['enable_form_button']) && $floating_buttons_options['enable_form_button'] === 'on';
    if ($is_form_button_enabled) {
        ?>
        <button on="tap:form-lightbox.open" class="floating-form-button" aria-label="Mở form đăng ký">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
            </svg>
        </button>
        <?php
    }

    // 3. Nút Về Đầu Trang
    ?>
    <button on="tap:page-top.scrollTo(duration=300)" class="back-to-top-button" aria-label="Về đầu trang">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6 1.41 1.41z"/>
        </svg>
    </button>
    
    <amp-position-observer
        on="enter:hideAnim.start; exit:showAnim.start"
        layout="nodisplay"
        target="page-top">
    </amp-position-observer>
    <amp-animation id="showAnim" layout="nodisplay">
        <script type="application/json">
        {
            "duration": "300ms", "fill": "forwards", "easing": "ease-in-out",
            "animations": [{
                "selector": ".back-to-top-button",
                "keyframes": [
                    { "opacity": "1", "visibility": "visible", "transform": "translateY(0)" }
                ]
            }]
        }
        </script>
    </amp-animation>
    <amp-animation id="hideAnim" layout="nodisplay">
        <script type="application/json">
        {
            "duration": "300ms", "fill": "forwards", "easing": "ease-in-out",
            "animations": [{
                "selector": ".back-to-top-button",
                "keyframes": [
                    { "opacity": "0", "visibility": "hidden", "transform": "translateY(20px)" }
                ]
            }]
        }
        </script>
    </amp-animation>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            // Đăng ký Service Worker từ gốc domain
            navigator.serviceWorker.register('<?php echo esc_url(home_url('/sw.js')); ?>')
                .then(registration => {
                    console.log('Service Worker registered: ', registration.scope);
                })
                .catch(err => {
                    console.log('Service Worker registration failed: ', err);
                });
        });
    }
    </script>
    <script src="<?php echo esc_url(get_template_directory_uri() . '/assets/js/install-pwa.js'); ?>" defer></script>
    <?php wp_footer(); ?>
</body>
</html>