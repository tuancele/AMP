<?php
/**
 * inc/core-setup.php
 * Contains core setup functions, cleanup, and basic AMP handling for the theme.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// =========================================================================
// LCP & CORE SETUP
// =========================================================================

/**
 * Preload the Largest Contentful Paint (LCP) image.
 */
function tuancele_responsive_lcp_preload_final() {
    $image_id = 0;
    // Determine the main image ID based on context
    if ( is_singular() ) {
        $post_id = get_the_ID();
        if ( has_post_thumbnail( $post_id ) ) {
            $image_id = get_post_thumbnail_id( $post_id );
        } else {
            // Fallback to the first image block in content
            $post_content = get_post_field( 'post_content', $post_id );
            if ( ! empty( $post_content ) && function_exists('has_blocks') && has_blocks( $post_content ) ) {
                $blocks = parse_blocks( $post_content );
                foreach ( $blocks as $block ) {
                    if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
                        $image_id = (int) $block['attrs']['id'];
                        break;
                    }
                }
            }
        }
    }
    elseif ( is_home() || is_front_page() || is_archive() ) {
        // Fallback for archives: Use the thumbnail of the first post
        global $wp_query;
        if ( $wp_query->have_posts() && isset( $wp_query->posts[0] ) ) {
            $post_id = $wp_query->posts[0]->ID;
            if ( has_post_thumbnail( $post_id ) ) {
                $image_id = get_post_thumbnail_id( $post_id );
            }
        }
    }

    // Generate the preload link if a valid image ID is found
    if ( $image_id > 0 ) {
        $fallback_src = wp_get_attachment_image_url( $image_id, 'large' );
        $image_srcset = wp_get_attachment_image_srcset( $image_id, 'large' );
        $image_sizes  = wp_get_attachment_image_sizes( $image_id, 'large' );
        if ( $fallback_src && $image_srcset && $image_sizes ) {
            echo '<link rel="preload" as="image" href="' . esc_url( $fallback_src ) . '" imagesrcset="' . esc_attr( $image_srcset ) . '" imagesizes="' . esc_attr( $image_sizes ) . '" fetchpriority="high" />' . "\n";
        }
    }
}
add_action( 'wp_head', 'tuancele_responsive_lcp_preload_final', 5 );

/**
 * Remove unwanted default WordPress actions for AMP optimization.
 */
function tuancele_remove_unwanted_scripts_and_links() {
    remove_action( 'wp_head', 'wp_resource_hints', 2 );
    remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
    remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
    remove_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_site_icon', 99 );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
    });
}
add_action('init', 'tuancele_remove_unwanted_scripts_and_links', 9999);

/**
 * Start output buffering to capture and clean HTML.
 */
function amp_start_output_buffer() {
    ob_start('amp_final_output_cleanup');
}
add_action('template_redirect', 'amp_start_output_buffer', -1);

/**
 * End output buffering and flush the cleaned output.
 */
function amp_end_output_buffer() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
add_action('shutdown', 'amp_end_output_buffer');

/**
 * Clean up the buffered HTML output for AMP compliance.
 * [FINAL CANONICAL FIX] This function now also acts as a fail-safe to ensure
 * a canonical tag is always present and correctly placed.
 */
function amp_final_output_cleanup($buffer) {
    // --- BẮT ĐẦU: LOGIC XỬ LÝ CANONICAL ---

    // 1. Kiểm tra xem thẻ canonical đã tồn tại trong buffer hay chưa.
    $canonical_exists = preg_match('/<link\s[^>]*rel=[\'"]canonical[\'"][^>]*>/is', $buffer);

    // 2. Nếu thẻ canonical CHƯA tồn tại, chúng ta sẽ tự tạo và chèn nó.
    if (!$canonical_exists) {
        global $wp;
        $canonical_url = '';

        if (is_front_page() || is_home()) {
            $canonical_url = home_url('/');
        } elseif (is_singular()) {
            $canonical_url = get_permalink();
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $canonical_url = get_term_link($term);
            }
        } elseif (is_author()) {
            $canonical_url = get_author_posts_url(get_queried_object_id());
        } elseif (is_archive()) {
            if (is_post_type_archive()) {
                 $canonical_url = get_post_type_archive_link(get_post_type());
            } else { // Fallback for date archives etc.
                 $canonical_url = home_url(add_query_arg([], $wp->request));
            }
        } else { // Fallback for any other page (like search results)
            $canonical_url = home_url(add_query_arg([], $wp->request));
        }
        
        // Handle pagination for archive pages
        $page = get_query_var('paged', 1);
        if ($page > 1) {
            $canonical_url = add_query_arg('paged', $page, $canonical_url);
        }

        // Tạo thẻ <link> hoàn chỉnh
        if (!empty($canonical_url) && filter_var($canonical_url, FILTER_VALIDATE_URL)) {
            $canonical_tag = '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
            
            // Chèn thẻ canonical ngay trước thẻ đóng </head>
            $buffer = preg_replace('/(<\/\s*head\s*>)/i', $canonical_tag . '$1', $buffer, 1);
        }
    }
    // --- KẾT THÚC: LOGIC XỬ LÝ CANONICAL ---

    // --- CÁC QUY TẮC DỌN DẸP HIỆN CÓ (GIỮ NGUYÊN) ---
    $buffer = ltrim($buffer);
    // Remove <style> tags unless they are amp-custom or amp-boilerplate
    $buffer = preg_replace('/<style\b(?!.*\b(amp-custom|amp-boilerplate)\b)[^>]*>.*?<\/style>/is', '', $buffer);
    // Remove <link rel="stylesheet"> tags
    $buffer = preg_replace('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*>/is', '', $buffer);
    // Remove disallowed <script> tags
    $safe_script_pattern = '/<script(?![^>]*type=[\'"]application\/(ld\+json|json)[\'"])(?![^>]*src=[\'"][^\'"]*cdn\.ampproject\.org)[^>]*>.*?<\/script>/is';
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $buffer, $matches)) {
        $body_content = $matches[1];
        $cleaned_body = preg_replace($safe_script_pattern, '', $body_content);
        $buffer = str_replace($body_content, $cleaned_body, $buffer);
    }
    $buffer = preg_replace('/<script type=[\'"]speculationrules[\'"].*?<\/script>/is', '', $buffer);
    $buffer = preg_replace('/ style=["\']\s*["\']/i', '', $buffer);
    
    return $buffer;
}

/**
 * Basic theme setup.
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
 */
add_filter('show_admin_bar', '__return_false');

/**
 * Dequeue unnecessary scripts and styles.
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
 * [LCP OPTIMIZER] Đánh dấu hình ảnh đầu tiên trong nội dung là ứng viên LCP.
 * Chạy rất sớm để đảm bảo marker có mặt trước các bộ lọc khác.
 */
function tuancele_mark_first_content_image_as_lcp($content) {
    // Chỉ chạy trên các trang đơn (bài viết, trang tĩnh), trong vòng lặp chính, và chỉ một lần.
    if (is_singular() && in_the_loop() && is_main_query()) {
        static $first_image_marked = false;
        if (!$first_image_marked) {
            // Tìm thẻ <img> đầu tiên và thêm một thuộc tính tạm thời để đánh dấu
            $content = preg_replace('/<img/i', '<img data-is-lcp-candidate="true"', $content, 1);
            $first_image_marked = true;
        }
    }
    return $content;
}
add_filter('the_content', 'tuancele_mark_first_content_image_as_lcp', 1);

/**
 * Filter content to convert standard HTML tags to their AMP equivalents.
 * [LCP OPTIMIZED] Bây giờ sẽ thêm data-fetchpriority="high" vào ảnh nội dung đầu tiên.
 */
function amp_filter_the_content($content) {
    // Convert <img> to <amp-img>
    $content = preg_replace_callback('/<img([^>]+)>/i', function ($matches) {
        $attributes_str = $matches[1];
        $fetch_priority_attr = '';

        // Kiểm tra marker ứng viên LCP của chúng ta
        if (strpos($attributes_str, 'data-is-lcp-candidate="true"') !== false) {
            $fetch_priority_attr = ' data-fetchpriority="high"';
            // Dọn dẹp marker để HTML cuối cùng được sạch sẽ
            $attributes_str = str_replace(' data-is-lcp-candidate="true"', '', $attributes_str);
        }

        // Các logic dọn dẹp cũ giữ nguyên
        $attributes_str = preg_replace('/\s(fetchpriority|loading|decoding)="[^"]*"/i', '', $attributes_str);
        if (strpos($attributes_str, 'layout=') === false) {
            $attributes_str .= ' layout="responsive"';
        }
        if (preg_match('/width="(\d+)"/i', $attributes_str, $w) && preg_match('/height="(\d+)"/i', $attributes_str, $h)) {
            if (intval($w[1]) > 0 && intval($h[1]) > 0) {
                // Thêm thuộc tính fetchpriority vào thẻ amp-img
                return '<amp-img ' . rtrim(trim($attributes_str), '/') . $fetch_priority_attr . '></amp-img>';
            }
        }
        return $matches[0];
    }, $content);

    // Convert <iframe> to <amp-iframe> (logic cũ giữ nguyên)
    $content = preg_replace_callback('/<iframe([^>]+)><\/iframe>/i', function ($matches) {
        $attr = $matches[1];
        if (strpos($attr, 'layout=') === false) $attr .= ' layout="responsive"';
        if (strpos($attr, 'width=') === false) $attr .= ' width="600"';
        if (strpos($attr, 'height=') === false) $attr .= ' height="400"';
        if (strpos($attr, 'sandbox=') === false) $attr .= ' sandbox="allow-scripts allow-same-origin allow-popups"';
        return '<amp-iframe ' . $attr . '><div placeholder style="background:#f0f4f8 url(/wp-includes/images/spinner.gif) no-repeat center; background-size: 20px; display:flex; align-items:center; justify-content:center;">Loading...</div></amp-iframe>';
    }, $content);
    return $content;
}
add_filter('the_content', 'amp_filter_the_content', 99);

/**
 * =========================================================================
 * CONDITIONAL ASSET LOADING (AMP SCRIPTS & META)
 * =========================================================================
 */

/**
 * Conditionally register required AMP component scripts.
 */
function tuancele_register_conditional_assets() {
    if ( !is_singular() && !is_front_page() && !is_archive() && !is_home() ) return; // Mở rộng điều kiện cho các trang khác
    
    global $post;
    $content = '';
    if ( is_object($post) && isset( $post->post_content ) ) {
        $content = $post->post_content;
    }

    $scripts_to_load = [];
    $meta_to_load = [];

    // Map shortcodes to their required AMP components
    $asset_map = [
        'tinh_lai_suat' => [
            ['element' => 'amp-script', 'src' => 'https://cdn.ampproject.org/v0/amp-script-0.1.js', 'hash' => 'sha384-zFiSt9Jd5Jmua7fQBSUTwWwkfqrXi0FuEdUuCXBksGNis3pHhuSsqPxgNHG_XITy']
        ],
        'amp_slider' => [
            ['element' => 'amp-carousel', 'src' => 'https://cdn.ampproject.org/v0/amp-carousel-0.2.js']
        ],
        'amp_imagemap' => [
            ['element' => 'amp-lightbox', 'src' => 'https://cdn.ampproject.org/v0/amp-lightbox-0.1.js'],
        ],
    ];

    // Check for shortcodes in content
    foreach ($asset_map as $shortcode => $assets) {
        if ( !empty($content) && has_shortcode( $content, $shortcode ) ) {
            foreach ($assets as $asset_info) {
                $scripts_to_load[$asset_info['element']] = $asset_info['src'];
                if (isset($asset_info['hash'])) {
                    $meta_to_load[] = $asset_info['hash'];
                }
            }
        }
    }

    // [FIX LỖI] Tự động kiểm tra và tải script cho Event Bar
    // Thực hiện một truy vấn nhẹ để xem có sự kiện nào được publish không
    $events_exist = get_posts([
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => 1, // Chỉ cần 1 để biết là có
        'fields' => 'ids', // Truy vấn nhẹ nhất có thể
    ]);
    // Nếu có sự kiện, luôn tải amp-carousel
    if (!empty($events_exist)) {
        $scripts_to_load['amp-carousel'] = 'https://cdn.ampproject.org/v0/amp-carousel-0.2.js';
    }


    // Always load amp-accordion on singular pages for TOC, FAQs, etc.
    if (is_singular()) {
        $scripts_to_load['amp-accordion'] = 'https://cdn.ampproject.org/v0/amp-accordion-0.1.js';
    }

    // Store the required scripts globally
    if ( ! empty( $scripts_to_load ) ) {
        $GLOBALS['conditional_amp_scripts'] = $scripts_to_load;
    }
    if ( ! empty( $meta_to_load ) ) {
        $GLOBALS['conditional_amp_meta'] = array_unique($meta_to_load);
    }
}
// Giữ nguyên add_action
add_action( 'wp', 'tuancele_register_conditional_assets' );

/**
 * Print the conditionally required AMP component scripts in the <head>.
 */
function tuancele_print_conditional_amp_scripts() {
    // These are loaded globally in header.php, so this function can be a fallback
    // or be used to consolidate all script loading logic.
    // For now, let's just print what was detected.
    if ( ! empty( $GLOBALS['conditional_amp_scripts'] ) && is_array( $GLOBALS['conditional_amp_scripts'] ) ) {
        foreach ( $GLOBALS['conditional_amp_scripts'] as $element => $src ) {
            if (!empty($element) && !empty($src)) {
                printf( '<script async custom-element="%s" src="%s"></script>', esc_attr( $element ), esc_url( $src ) );
                echo "\n";
            }
        }
    }
}
add_action( 'wp_head', 'tuancele_print_conditional_amp_scripts', 7 );

/**
 * Print the conditionally required AMP meta tags in the <head>.
 */
function tuancele_print_conditional_amp_meta() {
    if ( ! empty( $GLOBALS['conditional_amp_meta'] ) && is_array( $GLOBALS['conditional_amp_meta'] ) ) {
        foreach ( $GLOBALS['conditional_amp_meta'] as $hash ) {
             if (!empty($hash)) {
                printf( '<meta name="amp-script-src" content="%s">', esc_attr( $hash ) );
                echo "\n";
            }
        }
    }
}
add_action( 'wp_head', 'tuancele_print_conditional_amp_meta', 8 );

/**
 * Override the default WordPress caption shortcode output for AMP.
 */
function tuancele_override_caption_shortcode_output( $output, $attr, $content ) {
    $atts = shortcode_atts( ['id' => '', 'align' => 'alignnone', 'caption' => '', 'class' => ''], $attr, 'caption' );
    $atts['id'] = 'attachment_' . esc_attr( (int) preg_replace( '/\D/', '', $atts['id'] ) );
    $class = trim( 'wp-caption ' . $atts['align'] . ' ' . $atts['class'] );
    $html = '<div id="' . $atts['id'] . '" class="' . esc_attr( $class ) . '">';
    $html .= do_shortcode( $content );
    if ( $atts['caption'] ) {
        $html .= '<p class="wp-caption-text">' . wp_kses_post($atts['caption']) . '</p>';
    }
    $html .= '</div>';
    return $html;
}
add_filter( 'img_caption_shortcode', 'tuancele_override_caption_shortcode_output', 10, 3 );

/**
 * Inject the content of the AMP CSS file.
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
// [FIX] This hook must match the one used in header.php
add_action('amp_custom_css', 'tuancele_inject_amp_css_from_file');

?>
