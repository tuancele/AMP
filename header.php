<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * header.php - Phiên bản nâng cấp V2.
 *
 * [NÂNG CẤP MENU ĐA CẤP]
 * - Thêm script amp-accordion (được hook từ theme-setup.php).
 * - Sửa đổi <amp-sidebar> để chứa <amp-accordion>.
 * - Sử dụng Tuancele_AMP_Sidebar_Walker cho menu di động.
 * - Menu desktop (main-menu) sẽ được xử lý bằng CSS thuần.
 */
?>
<!doctype html>
<html ⚡ lang="<?php bloginfo('language'); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?php echo esc_url(home_url('/site.webmanifest')); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php bloginfo('name'); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/apple-touch-icon.png'); ?>">
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>
    <script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>
    <script async custom-element="amp-bind" src="https://cdn.ampproject.org/v0/amp-bind-0.1.js"></script> 
    <script async custom-element="amp-lightbox" src="https://cdn.ampproject.org/v0/amp-lightbox-0.1.js"></script>
    <script async custom-template="amp-mustache" src="https://cdn.ampproject.org/v0/amp-mustache-0.2.js"></script>
    <script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>
    <script async custom-element="amp-geo" src="https://cdn.ampproject.org/v0/amp-geo-0.1.js"></script>
    <script async custom-element="amp-position-observer" src="https://cdn.ampproject.org/v0/amp-position-observer-0.1.js"></script>
    <script async custom-element="amp-animation" src="https://cdn.ampproject.org/v0/amp-animation-0.1.js"></script>
    <style amp-custom><?php do_action('amp_custom_css'); ?></style>
    <script async custom-element="amp-recaptcha-input" src="https://cdn.ampproject.org/v0/amp-recaptcha-input-0.1.js"></script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>


    <amp-state id="pwaStatus">
        <script type="application/json">
            {"bannerDismissed": false}
        </script>
    </amp-state>
    
    <amp-geo layout="nodisplay">
        </amp-geo>
    <div id="toc-overlay" on="tap:tocAccordion.toggle" role="button" tabindex="-1" aria-label="Đóng Mục lục" hidden></div>
    
    <amp-sidebar id="my-sidebar" layout="nodisplay" side="left">
        <nav>
            <?php 
            // Kiểm tra xem có menu 'primary' và class Walker đã tồn tại chưa
            if ( has_nav_menu('primary') && class_exists('Tuancele_AMP_Sidebar_Walker') ) {
                echo '<amp-accordion>'; // Bọc menu trong accordion
                
                wp_nav_menu([
                    'theme_location' => 'primary', 
                    'container'      => false,
                    'items_wrap'     => '%3$s', // Chỉ xuất ra nội dung, không bọc <ul>
                    'walker'         => new Tuancele_AMP_Sidebar_Walker() // Sử dụng Walker mới
                ]);
                
                echo '</amp-accordion>';
            } elseif ( has_nav_menu('primary') ) {
                // Fallback nếu class Walker bị lỗi
                wp_nav_menu(['theme_location' => 'primary', 'container' => false]);
            }
            ?>
        </nav>
    </amp-sidebar>

    <div class="pwa-install-banner-wrapper" 
         [hidden]="pwaStatus.bannerDismissed"
         hidden>
        <div class="container pwa-banner-content">
            <p>
                Tải ứng dụng **<?php bloginfo('name'); ?>** để truy cập nhanh!
            </p>
            <button id="pwa-install-button" class="pwa-banner-button menu-button" aria-label="Cài đặt ứng dụng">
                Cài đặt ngay
            </button>
            
            <button on="tap:AMP.setState({ pwaStatus: { bannerDismissed: true } })" 
                    class="pwa-dismiss-button" aria-label="Tắt banner">
                &times;
            </button>
        </div>
    </div>
    
    <header id="page-top">
        <div class="container header-container">
            <div class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></div>
            <link rel="preload" href="<?php echo esc_url(get_template_directory_uri() . '/assets/fonts/poppins-v20-700.woff2'); ?>" as="font" type="font/woff2" crossorigin="anonymous">
            
            <?php // --- SỬA ĐỔI MENU DESKTOP --- ?>
            <nav class="main-menu">
                <?php 
                // Menu desktop không cần walker, chỉ cần CSS
                wp_nav_menu([
                    'theme_location' => 'primary', 
                    'container' => false 
                ]); 
                ?>
            </nav>
            <?php // --- KẾT THÚC SỬA ĐỔI MENU DESKTOP --- ?>
            
            <button on="tap:my-sidebar.toggle" class="menu-button" aria-label="Open menu">☰</button>
        </div>
    </header>
    <?php 
    echo do_shortcode('[amp_event_bar]');
    ?>
    <div class="site-content-wrapper"><main class="container">