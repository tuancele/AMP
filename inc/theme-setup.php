<?php
/**
 * inc/theme-setup.php
 * CHỈ CHỨA CÁC THIẾT LẬP GIAO DIỆN (THEME/SKIN).
 * Logic lõi AMP đã được chuyển sang inc/amp-core.php
 *
 * [TỐI ƯU V1 - CSS CACHE]
 * - Đã thêm hằng số TUANCELE_CSS_CACHE_KEY.
 * - Đã cập nhật hàm tuancele_inject_amp_css_from_file() để sử dụng WordPress Transients.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Định nghĩa khóa transient cho cache CSS.
 * Thêm 'v2' để đảm bảo cache cũ (nếu có) bị vô hiệu hóa.
 */
define('TUANCELE_CSS_CACHE_KEY', 'tuancele_amp_css_cache_v2');

/**
 * Basic theme setup.
 *
 */
function amp_custom_theme_setup() {
    register_nav_menus([
        'primary'       => __('Primary Menu', 'tuancele-amp'),
        'footer_menu_1' => __('Footer Links 1', 'tuancele-amp')
    ]);
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo', [
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_image_size('archive-thumb', 400, 229, true);
    add_image_size('banner-main', 1600, 900, true); // 16:9 crop
    add_image_size('og-image', 1200, 630, true); // 1.91:1 crop
}
add_action('after_setup_theme', 'amp_custom_theme_setup');

/**
 * Disable admin bar on the frontend.
 *
 */
add_filter('show_admin_bar', '__return_false');

/**
 * Dequeue unnecessary scripts and styles.
 *
 */
function amp_dequeue_scripts_and_styles() {
    wp_dequeue_style('wp-block-library');
    wp_deregister_style('wp-block-library');
    wp_dequeue_style('global-styles');
    wp_deregister_style('global-styles');
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_dequeue_script('jquery');
        wp_deregister_script('jquery-migrate');
        wp_dequeue_script('jquery-migrate');
    }
}
add_action('wp_enqueue_scripts', 'amp_dequeue_scripts_and_styles', 100);

/**
 * Inject the content of the AMP CSS file.
 *
 * [TỐI ƯU V1]
 * Sử dụng Transients API để cache nội dung file CSS,
 * tránh việc đọc file (file_get_contents) trên mỗi lần tải trang.
 */
function tuancele_inject_amp_css_from_file() {
    
    // 1. Thử lấy CSS từ cache (transient) trước
    $css_content = get_transient( TUANCELE_CSS_CACHE_KEY );

    // 2. Nếu cache không có (false), thì mới đọc file
    if ( false === $css_content ) {
        $theme_dir = get_template_directory();
        $min_css_path = $theme_dir . '/css/amp-custom.min.css';
        $css_path = $theme_dir . '/css/amp-custom.css';
        $css_content = '';

        if ( file_exists( $min_css_path ) && filesize( $min_css_path ) > 0 ) {
            $css_content = file_get_contents( $min_css_path );
        }
        elseif ( file_exists( $css_path ) && filesize( $css_path ) > 0 ) {
            $css_content = file_get_contents( $css_path );
        }

        if ( empty( trim( $css_content ) ) ) {
            $css_content = '/* ERROR: Could not find a valid CSS file. */';
        }

        // 3. Lưu nội dung file vừa đọc được vào cache (transient)
        // Đặt thời gian cache là 1 giờ.
        set_transient( TUANCELE_CSS_CACHE_KEY, $css_content, HOUR_IN_SECONDS );
    }

    // 4. In CSS ra (từ cache hoặc từ file)
    echo trim($css_content);
}
// Hook này phải khớp với hook trong header.php
add_action('amp_custom_css', 'tuancele_inject_amp_css_from_file');

/**
 * =========================================================================
 * [BƯỚC 1] WALKER CHO MENU SIDEBAR (AMP-ACCORDION V3 - CHUẨN AMP)
 * =========================================================================
 */

class Tuancele_AMP_Sidebar_Walker extends Walker_Nav_Menu {
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) { $output .= '<div><ul class="sub-menu">'; } 
        else { $output .= '<ul class="sub-menu">'; }
    }
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) { $output .= '</ul></div>'; } 
        else { $output .= '</ul>'; }
    }
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $has_children = in_array( 'menu-item-has-children', $item->classes );
        if ( $depth === 0 ) {
            $output .= '<section>'; 
            if ( $has_children ) {
                $output .= '<h4 class="amp-menu-header">' . esc_html( $item->title ) . '</h4>';
            } else {
                $output .= '<h4 class="amp-menu-link-section">';
                $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
                $output .= '</h4>';
                $output .= '<div></div>'; // Thẻ rỗng bắt buộc cho AMP
            }
        } else {
            $output .= '<li>';
            $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
        }
    }
    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ( $depth === 0 ) { $output .= '</section>'; } 
        else { $output .= '</li>'; }
    }
}

?>