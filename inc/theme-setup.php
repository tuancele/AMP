<?php
/**
 * inc/theme-setup.php
 * CHỈ CHỨA CÁC THIẾT LẬP GIAO DIỆN (THEME/SKIN).
 * Logic lõi AMP đã được chuyển sang inc/amp-core.php
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

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
 */
function tuancele_inject_amp_css_from_file() {
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

    if ( ! empty( trim( $css_content ) ) ) {
        echo trim($css_content);
    } else {
        echo '/* ERROR: Could not find a valid CSS file. */';
    }
}
// Hook này phải khớp với hook trong header.php
add_action('amp_custom_css', 'tuancele_inject_amp_css_from_file');
?>