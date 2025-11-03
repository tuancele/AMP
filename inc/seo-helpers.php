<?php
/**
 * inc/seo-helpers.php
 * Chứa các hàm liên quan đến SEO: Schema, Meta Tags, Open Graph, Twitter Cards.
 * PHIÊN BẢN HOÀN CHỈNH: Tích hợp đầy đủ các nâng cấp và sửa lỗi trên nền code gốc.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * =========================================================================
 * THẺ META CƠ BẢN (DESCRIPTION) & SOCIAL TAGS (Giữ nguyên)
 * =========================================================================
 */
function tuancele_amp_meta_tags() {
    global $post; 
    $description = '';
    
    if (is_front_page() || is_home()) {
        $description = get_bloginfo('description');
    } elseif (is_singular()) {
        if (has_excerpt($post->ID)) {
            $description = get_the_excerpt($post->ID);
        } else {
            $description = wp_trim_words(strip_shortcodes(strip_tags($post->post_content)), 55, '...');
        }
    } elseif (is_category() || is_tag()) {
        $description = term_description();
        if ( empty($description) ) {
            $queried_object = get_queried_object();
            if ( $queried_object && isset($queried_object->name) ) {
                $description = 'Tổng hợp các bài viết trong chuyên mục ' . $queried_object->name . ' tại ' . get_bloginfo('name') . '.';
            }
        }
    }

    if (!empty($description)) {
        echo '<meta name="description" content="' . esc_attr(strip_tags($description)) . '">' . "\n";
    }
}
add_action('wp_head', 'tuancele_amp_meta_tags', 1);

function tuancele_add_social_meta_tags() {
    if ( is_singular() ) {
        global $post;
        $title = get_the_title();
        $description = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_shortcodes($post->post_content), 55, '...');
        $url = get_permalink();
        
        if ( has_post_thumbnail($post->ID) ) {
            $image = get_the_post_thumbnail_url($post->ID, 'large');
        } else {
            $schema_options = get_option('tuancele_amp_schema_options');
            $image = $schema_options['logo'] ?? '';
        }
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        if (!empty($image)) {
            echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
        }
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        if (!empty($image)) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
        }
    }
}
add_action('wp_head', 'tuancele_add_social_meta_tags', 2);

/**
 * =========================================================================
 * [HOÀN CHỈNH] SCHEMA.ORG JSON-LD @GRAPH
 * =========================================================================
 */
function tuancele_amp_generate_rich_schema() {
    // Thêm is_front_page() để hàm vẫn chạy trên trang chủ
    if ( ! is_front_page() && ! is_singular() && ! is_archive() ) return;
    
    global $post;
    $schema_graph = [];
    $home_url = home_url( '/' );
    $options = get_option('tuancele_amp_schema_options', []);

    // --- Schema Doanh nghiệp ---
    $org_type = $options['organization_type'] ?? 'Corporation';
    $corporation_schema = [
        '@type' => $org_type,
        '@id' => $home_url . '#corporation',
        'name' => $options['name'] ?? get_bloginfo('name'),
        'url' => $home_url,
    ];
    if (!empty($options['logo'])) {
        $corporation_schema['logo'] = ['@type' => 'ImageObject', '@id' => $home_url . '#logo', 'url' => $options['logo']];
        $corporation_schema['image'] = ['@id' => $home_url . '#logo'];
    }
    $final_phone = !empty($options['hotline_number']) ? $options['hotline_number'] : ($options['telephone'] ?? '');
    if (!empty($final_phone)) $corporation_schema['telephone'] = $final_phone;
    if (!empty($options['email'])) $corporation_schema['email'] = $options['email'];
    if (!empty($options['description'])) $corporation_schema['description'] = $options['description'];
    $address_schema = ['@type' => 'PostalAddress', 'streetAddress' => $options['streetAddress'] ?? '', 'addressLocality' => $options['addressLocality'] ?? '', 'addressRegion' => $options['addressRegion'] ?? '', 'postalCode' => $options['postalCode'] ?? '', 'addressCountry' => 'VN' ];
    if (!empty(array_filter($address_schema, fn($v) => !empty($v) && $v !== 'VN'))) $corporation_schema['address'] = $address_schema;
    if (!empty($options['latitude']) && !empty($options['longitude'])) $corporation_schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $options['latitude'], 'longitude' => $options['longitude']];
    if (!empty($options['openingHours'])) {
        $opening_hours_raw = preg_split('/[\r\n]+/', $options['openingHours'], -1, PREG_SPLIT_NO_EMPTY);
        $opening_hours_specs = [];
        $day_map = ['Mo'=>'Monday', 'Tu'=>'Tuesday', 'We'=>'Wednesday', 'Th'=>'Thursday', 'Fr'=>'Friday', 'Sa'=>'Saturday', 'Su'=>'Sunday'];
        foreach ($opening_hours_raw as $line) {
            if (preg_match('/^([a-zA-Z, -]+)\s+([\d:]{4,5})-([\d:]{4,5})$/i', trim($line), $matches)) {
                $days = explode(',', str_replace(' ', '', $matches[1]));
                $day_of_week = [];
                foreach ($days as $day_part) {
                    if (strpos($day_part, '-') !== false) {
                        list($start_day, $end_day) = explode('-', $day_part);
                        $day_keys = array_keys($day_map);
                        $start_index = array_search($start_day, $day_keys);
                        $end_index = array_search($end_day, $day_keys);
                        if ($start_index !== false && $end_index !== false) {
                            for ($i = $start_index; $i <= $end_index; $i++) { $day_of_week[] = "https://schema.org/" . $day_map[$day_keys[$i]]; }
                        }
                    } else if (isset($day_map[$day_part])) {
                        $day_of_week[] = "https://schema.org/" . $day_map[$day_part];
                    }
                }
                if (!empty($day_of_week)) $opening_hours_specs[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array_unique($day_of_week), 'opens' => $matches[2], 'closes' => $matches[3]];
            }
        }
        if (!empty($opening_hours_specs)) $corporation_schema['openingHoursSpecification'] = $opening_hours_specs;
    }
    if ($org_type === 'RealEstateAgent' && !empty($options['price_range'])) $corporation_schema['priceRange'] = esc_html($options['price_range']);
    if (!empty($options['sameAs'])) $corporation_schema['sameAs'] = array_filter(array_map('trim', preg_split('/[\r\n]+/', $options['sameAs'])));
    $schema_graph[] = $corporation_schema;
    
    // --- Schema Tác giả (Person) ---
    $author_id = 1; $author_name = get_the_author_meta('display_name', $author_id);
    $author_entity_id = $home_url . '#author/' . sanitize_title($author_name);
    $schema_graph[] = [ '@type' => 'Person', '@id' => $author_entity_id, 'name' => $author_name, 'url' => get_author_posts_url($author_id), 'description' => get_the_author_meta('description', $author_id), 'worksFor' => ['@id' => $home_url . '#corporation'], 'alumniOf' => [ ['@type' => 'EducationalOrganization', 'name' => 'Đại học Bách khoa Hà Nội'], ['@type' => 'Organization', 'name' => 'Tập đoàn Vingroup'] ] ];
    
    $breadcrumbs = [];
    $breadcrumbs[] = [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Trang Chủ', 'item' => $home_url ];

    // --- Schema theo ngữ cảnh trang ---
    if ( is_singular() || is_front_page() ) {
        $post_url = is_front_page() ? $home_url : get_permalink();

        // Xử lý entities 'mentions' và 'about' từ Meta Box
        $entities_raw = get_post_meta($post->ID, '_schema_entities', true);
        $mentions = []; $about = [];
        if (!empty($entities_raw)) {
            $lines = explode("\n", str_replace("\r", "", $entities_raw));
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) === 3 && !empty($parts[0]) && !empty($parts[1])) {
                    $entity_schema = ['@type' => sanitize_text_field($parts[0]), 'name' => sanitize_text_field($parts[1]), 'description' => sanitize_text_field($parts[2])];
                    $mentions[] = $entity_schema;
                    if (in_array($entity_schema['@type'], ['Place', 'Organization'])) $about[] = $entity_schema;
                }
            }
        }

        $webpage_schema = [
            '@type' => 'WebPage', '@id' => $post_url, 'url' => $post_url, 'name' => get_the_title(),
            'isPartOf' => ['@id' => $home_url . '#website'], 'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'), 'inLanguage' => 'vi',
            'potentialAction' => [['@type' => 'ReadAction', 'target' => [$post_url]]],
        ];
        if (!empty($mentions)) $webpage_schema['mentions'] = $mentions;
        if (!empty($about)) $webpage_schema['about'] = $about;
        $schema_graph[] = $webpage_schema;

        if ( is_singular('post') ) {
            $categories = get_the_category(); $current_pos = 2;
            if (!empty($categories)) $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $current_pos++, 'name' => $categories[0]->name, 'item' => get_category_link($categories[0]->term_id)];
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $current_pos, 'name' => get_the_title(), 'item' => $post_url];
            $article_content_clean = wp_strip_all_tags(strip_shortcodes($post->post_content));
            $article_schema = [
                '@type' => 'BlogPosting', '@id' => $post_url . '#article', 'mainEntityOfPage' => ['@id' => $post_url],
                'headline' => get_the_title(), 'description' => has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words($article_content_clean, 55, '...'),
                'author' => ['@id' => $author_entity_id], 'publisher' => ['@id' => $home_url . '#corporation'],
                'datePublished' => get_the_date('c'), 'dateModified' => get_the_modified_date('c'),
                'articleBody' => $article_content_clean, 'commentCount' => (int) get_comments_number($post->ID),
                'wordCount' => str_word_count($article_content_clean),
            ];
            $comments_with_rating = (new WP_Comment_Query)->query(['post_id' => $post->ID, 'status' => 'approve', 'meta_key' => 'rating']);
            if (!empty($comments_with_rating)) {
                $total_rating = 0; $review_schemas = [];
                foreach ($comments_with_rating as $comment) {
                    $total_rating += (int) get_comment_meta($comment->comment_ID, 'rating', true);
                    $review_schemas[] = ['@id' => get_comment_link($comment)];
                }
                $article_schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round($total_rating / count($comments_with_rating), 1), 'reviewCount' => count($comments_with_rating)];
                $article_schema['review'] = $review_schemas;
            }
            if (has_post_thumbnail()) {
                $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
                $article_schema['image'] = ['@type' => 'ImageObject', '@id' => $post_url . '#primaryimage', 'url' => $image_data[0], 'width' => $image_data[1], 'height' => $image_data[2]];
            }
            $schema_graph[] = $article_schema;
        } elseif ( is_singular('service') ) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dịch vụ', 'item' => get_post_type_archive_link('service')];
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => $post_url];
            $service_schema = [
                '@type' => 'Service', '@id' => $post_url . '#service', 'name' => get_the_title(),
                'description' => has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_tags(get_the_content()), 55),
                'provider' => ['@id' => $home_url . '#corporation'],
            ];
            $service_type = get_post_meta($post->ID, '_service_type', true);
            $area_served = get_post_meta($post->ID, '_area_served', true);
            $low_price = get_post_meta($post->ID, '_low_price', true);
            $high_price = get_post_meta($post->ID, '_high_price', true);
            if (!empty($service_type)) $service_schema['serviceType'] = $service_type;
            if (!empty($area_served)) $service_schema['areaServed'] = ['@type' => 'Country', 'name' => $area_served];
            if (is_numeric($low_price) && is_numeric($high_price)) $service_schema['offers'] = ['@type' => 'AggregateOffer', 'lowPrice' => $low_price, 'highPrice' => $high_price, 'priceCurrency' => 'VND'];
            $schema_graph[] = $service_schema;
        } elseif ( is_page() && !is_front_page() ) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(), 'item' => $post_url];
        }
    } elseif ( is_archive() ) {
        $archive_title = strip_tags(get_the_archive_title());
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => esc_html($archive_title), 'item' => get_term_link(get_queried_object())];
    }
    
    // --- Schema WebSite (Đã chuyển lên trên để đảm bảo luôn có) ---
    
    // [SỬA LỖI] Chỉ thêm BreadcrumbList nếu không phải trang chủ
    if ( !is_front_page() && count( $breadcrumbs ) > 1 ) {
        $schema_graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbs];
    }
    
    if ( ! empty( $GLOBALS['page_specific_schema'] ) ) {
        $schema_graph = array_merge($schema_graph, (array) $GLOBALS['page_specific_schema']);
    }

    if ( ! empty( $schema_graph ) ) {
        echo '<script type="application/ld+json">' . json_encode( ['@context' => 'https://schema.org', '@graph' => $schema_graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }
}
add_action('wp_footer', 'tuancele_amp_generate_rich_schema');


/**
 * =========================================================================
 * CÁC HÀM HỖ TRỢ SCHEMA REVIEW TỪ BÌNH LUẬN
 * =========================================================================
 */
add_action( 'comment_post', 'tuancele_save_comment_rating' );
function tuancele_save_comment_rating( $comment_id ) {
    if ( isset( $_POST['rating'] ) && ! empty( $_POST['rating'] ) ) {
        add_comment_meta( $comment_id, 'rating', absint( $_POST['rating'] ) );
    }
}

add_filter( 'comment_text', 'tuancele_generate_review_schema_for_comment', 99 );
function tuancele_generate_review_schema_for_comment( $comment_text ) {
    if ( is_admin() ) return $comment_text;

    global $comment;
    if ( !is_object($comment) || empty($comment->comment_ID) ) return $comment_text;

    $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
    if ( ! empty( $rating ) ) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            '@id' => get_comment_link( $comment ),
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => '5', 'worstRating' => '1'],
            'author' => ['@type' => 'Person', 'name' => get_comment_author( $comment->comment_ID )],
            'reviewBody' => wp_strip_all_tags( get_comment_text( $comment->comment_ID ) ),
            'datePublished' => get_comment_date('c', $comment->comment_ID),
            'itemReviewed' => ['@id' => get_permalink($comment->comment_post_ID) . '#article'],
        ];
        $schema_output = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        return $schema_output . $comment_text;
    }
    return $comment_text;
}