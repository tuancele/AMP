<?php
/**
 * inc/helpers/conditional-css.php
 *
 * [TỐI ƯU V5.7 - FIX LỖI CSS 75KB]
 * - Tải CSS cho các component (Slider, ImageMap, BĐS...)
 * chỉ khi shortcode hoặc template của chúng được sử dụng.
 * - CSS này đã được gỡ bỏ khỏi amp-custom.min.css.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Nạp CSS cho các template trang đặc biệt.
 */
function tuancele_load_conditional_css() {

    global $post;
    $content = is_object($post) ? $post->post_content : '';

    // 1. NẠP CSS CHO TRANG HỖ TRỢ (SUPPORT.PHP)
    if ( is_page_template('support.php') ) {
        $css_file = get_template_directory() . '/css/page-specific/support.css';
        if ( file_exists( $css_file ) ) {
            echo file_get_contents( $css_file );
        }
    }

    // 2. NẠP CSS CHO TRANG 404 (404.PHP)
    if ( is_404() ) {
        $css_content = "
            .error-404-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 65vh; padding: 40px 15px; text-align: center; }
            .error-404-code { font-size: clamp(8rem, 25vw, 12rem); font-weight: 700; line-height: 1; color: rgba(0, 0, 0, 0.05); margin-bottom: -1.5rem; }
            .error-404-container .page-title { color: var(--mau-chu); margin-bottom: 1rem; font-size: 2rem; }
            .error-404-container .page-content p { color: #555; font-size: 1.1rem; max-width: 500px; margin: 0 auto 1.5rem; }
            .error-404-search-form { display: flex; max-width: 450px; width: 100%; margin: 0 auto 2rem; border-radius: 50px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
            .error-404-search-form .search-field { flex-grow: 1; border: 1px solid #ddd; border-right: none; padding: 12px 20px; font-size: 1rem; outline: none; }
            .error-404-search-form .search-submit { border: none; background-color: var(--mau-chu); color: #fff; padding: 0 25px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s ease; }
            .error-404-search-form .search-submit:hover { background-color: #004494; }
            .button-back-home { display: inline-block; padding: 12px 30px; background-color: var(--mau-chinh); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 700; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 4px 15px rgba(0, 115, 230, 0.2); }
            .button-back-home:hover { transform: translateY(-3px); box-shadow: 0 7px 20px rgba(0, 115, 230, 0.35); color: #fff; }
            .screen-reader-text { border: 0; clip: rect(1px, 1px, 1px, 1px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; width: 1px; word-wrap: normal; }
        ";
        echo $css_content;
    }

    // 3. NẠP CSS CHO TRANG TÌM KIẾM (SEARCH.PHP)
    if ( is_search() ) {
        $css_content = "
            .page-title .search-query { color: var(--mau-chinh); font-style: italic; }
            .search-results-form-container { max-width: 600px; margin: 0 auto 40px; }
            .search-results-form { display: flex; width: 100%; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background-color: #fff; }
            .search-results-form .search-field { flex-grow: 1; border: none; padding: 14px 18px; font-size: 1rem; outline: none; }
            .search-results-form .search-submit { display: flex; align-items: center; justify-content: center; border: none; background-color: var(--mau-chu); color: #fff; padding: 0 20px; cursor: pointer; transition: background-color 0.2s ease; }
            .search-results-form .search-submit:hover { background-color: #004494; }
            .no-search-results { text-align: center; padding: 50px 20px; background-color: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px; margin-top: 20px; }
            .no-search-results p { font-size: 1.1rem; color: #555; margin-bottom: 10px; }
            .search-highlight { background-color: #fff3cd; color: #333; padding: 2px 3px; border-radius: 3px; font-weight: 600; }
        ";
        echo $css_content;
    }

    // 4. NẠP CSS CHO CÁC TRANG IP (LOG IP, MY IP)
    if ( is_page_template('ip.php') || is_page_template('my-ip.php') ) {
        $css_content = "
            .log-page-container h1 { font-size: 2rem; margin-bottom: 25px; }
            .log-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .visitor-log-table { border-collapse: collapse; width: 100%; font-size: 14px; margin-top: 10px; border: 1px solid #ddd; }
            .visitor-log-table th, .visitor-log-table td { text-align: left; padding: 12px 15px; border-bottom: 1px solid #ddd; word-break: break-all; }
            .visitor-log-table th { background-color: #f2f2f2; font-weight: 600; }
            .visitor-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
            .visitor-log-table tbody tr:hover { background-color: #f1f1f1; }
            @media screen and (max-width: 767px) {
                .visitor-log-table { border: 0; }
                .visitor-log-table thead { display: none; }
                .visitor-log-table tr { display: block; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
                .visitor-log-table td { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; text-align: right; }
                .visitor-log-table td:last-child { border-bottom: 0; }
                .visitor-log-table td::before { content: attr(data-label); float: left; font-weight: bold; text-align: left; margin-right: 15px; color: #333; }
            }
            @keyframes icon-glow { 0%, 100% { text-shadow: 0 0 5px rgba(0, 123, 255, 0.5); } 50% { text-shadow: 0 0 20px rgba(0, 123, 255, 1); } }
            @keyframes icon-scan { 0%, 100% { transform: translateX(-5%) rotate(-10deg); } 50% { transform: translateX(5%) rotate(10deg); } }
            .my-ip-page-container { padding: 10px 0; }
            .ip-section { background-color: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 6px 12px rgba(0,0,0,0.06); }
            .ip-section h2 { font-size: 1.8rem; margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; display: flex; align-items: center; color: #333; }
            .ip-section h2 .icon { margin-right: 15px; font-size: 2rem; }
            .my-ip-section .icon { animation: icon-glow 3s ease-in-out infinite; }
            .ip-lookup-section .icon { animation: icon-scan 2.5s ease-in-out infinite; }
            .ip-section h3 { font-size: 1.3rem; margin-top: 30px; color: #333; }
            .ip-info-wrapper { display: flex; flex-direction: column; gap: 8px; }
            .info-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-size: 16px; }
            .info-row:last-child { border-bottom: none; }
            .info-row:nth-child(odd) { background-color: #fcfcfc; }
            .info-label { color: #555; padding-right: 15px; }
            .info-value { text-align: right; word-break: break-all; }
            .lookup-form { display: flex; gap: 10px; margin-top: 10px; }
            .lookup-form input[type=\"text\"] { flex-grow: 1; padding: 12px 15px; font-size: 16px; border: 1px solid #ccc; border-radius: 8px; transition: border-color 0.2s, box-shadow 0.2s; }
            .lookup-form input[type=\"text\"]:focus { border-color: var(--mau-chinh, #007bff); box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15); outline: none; }
            .lookup-form button { padding: 0 25px; font-size: 16px; font-weight: bold; background-color: var(--mau-chinh, #007bff); color: white; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s, transform 0.2s; }
            .lookup-form button:hover { background-color: var(--mau-chu, #0056b3); transform: translateY(-2px); }
            .error-notice { color: #d8000c; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-top: 20px; }
            @media screen and (max-width: 600px) {
                .ip-section { padding: 20px; }
                .ip-section h2 { font-size: 1.5rem; }
                .info-row { flex-direction: column; align-items: flex-start; padding: 12px 0; }
                .info-label { font-weight: bold; color: #333; margin-bottom: 5px; }
                .info-value { text-align: left; padding-left: 5px; color: #555; }
                .lookup-form { flex-direction: column; }
                .lookup-form button { padding: 14px; }
            }
        ";
        echo $css_content;
    }
    
    // 5. NẠP CSS CHO TRANG SITEMAP
    if ( is_page_template('page-sitemap.php') ) {
        $css_content = "
            .sitemap-container { margin: 30px 0; }
            .sitemap-grid { display: grid; grid-template-columns: 1fr; gap: 30px; }
            .sitemap-group { background-color: #fff; border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); padding: 25px 30px; transition: transform 0.3s ease-out, box-shadow 0.3s ease-out; }
            .sitemap-group:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
            .sitemap-group-title { font-size: 1.6rem; font-family: 'Poppins', sans-serif; margin-top: 0; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; background: linear-gradient(90deg, var(--mau-chu), var(--mau-chinh)); -webkit-background-clip: text; -moz-background-clip: text; background-clip: text; color: transparent; }
            .sitemap-list { list-style: none; padding: 0; margin: 0; max-height: 400px; overflow-y: auto; -webkit-overflow-scrolling: touch; border: 1px solid #f0f0f0; border-radius: 8px; overflow: hidden; }
            .sitemap-list::-webkit-scrollbar { width: 6px; }
            .sitemap-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
            .sitemap-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
            .sitemap-list::-webkit-scrollbar-thumb:hover { background: #aaa; }
            .sitemap-list li { margin: 0; }
            .sitemap-container .sitemap-list li h3 { margin: 0; font-size: 0.95rem; font-weight: 400; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; line-height: 1.6; }
            .sitemap-list li h3 a { display: block; padding: 10px 15px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: all 0.2s ease-out; transform: translateX(0); }
            .sitemap-list li:last-child h3 a { border-bottom: none; }
            .sitemap-list li h3 a:hover { background-color: #f0f8ff; color: var(--mau-chu); transform: translateX(8px); }
            @media (min-width: 768px) { .sitemap-grid { grid-template-columns: 1fr 1fr; } }
        ";
        echo $css_content;
    }
    
    // 6. NẠP CSS CHO TRANG LIVE STATUS
    if ( is_page_template('page-live-status.php') ) {
         $css_content = "
            .live-status-wrapper { padding: 40px 0; background-color: #f7f9fb; border-radius: 8px; margin-bottom: 30px; }
            .status-title { text-align: center; font-size: 30px; color: var(--mau-chinh); margin-bottom: 5px; }
            .status-intro { text-align: center; font-size: 16px; color: #666; margin-bottom: 30px; }
            .status-list { max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 15px; }
            .status-item { display: flex; align-items: center; padding: 15px 10px; border-bottom: 1px solid #eee; transition: background-color 0.2s; }
            .status-item:last-child { border-bottom: none; }
            .status-item:hover { background-color: #fafafa; }
            .status-icon-small { font-size: 24px; margin-right: 15px; width: 30px; height: 30px; text-align: center; }
            .status-svg { width: 100%; height: 100%; display: block; }
            .status-online-svg circle { fill: #28a745; animation: pulse 1.5s infinite; transform-origin: center; }
            .status-offline-svg circle { fill: #dc3545; }
            @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.15); opacity: 0.8; } 100% { transform: scale(1); opacity: 1; } }
            .status-details { flex-grow: 1; }
            .item-name { font-size: 18px; font-weight: 700; margin: 0 0 4px 0; color: #333; }
            .item-domain { font-size: 15px; color: #777; margin: 0; }
            .status-text-online { color: #28a745; font-weight: 700; }
            .status-text-offline { color: #dc3545; font-weight: 700; }
            .status-text-error { color: #ffc107; font-weight: 700; }
            .last-checked { text-align: center; font-size: 14px; color: #999; margin-top: 25px; }
            .page-content { padding-top: 20px; }
            @media (max-width: 768px) { .status-title { font-size: 24px; } .status-list { max-width: 100%; } .item-name { font-size: 16px; } }
         ";
         echo $css_content;
    }
    
    // 7. NẠP CSS CHO TIMELINE (NẾU CÓ SHORTCODE)
    if ( is_singular() && has_shortcode( $content, 'timeline' ) ) {
        $css_content = "
            .construction-timeline-container {
                position: relative;
                margin: 40px 0;
                padding-left: 25px; /* Khoảng cách cho đường line */
                border-left: 4px solid #e9ecef; /* Đường line timeline */
            }
            .timeline-item {
                position: relative;
                margin-bottom: 30px;
            }
            .timeline-item:last-child {
                margin-bottom: 0;
            }
            .timeline-dot {
                position: absolute;
                left: -35px; /* Căn giữa chấm tròn trên đường line */
                top: 0;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background-color: #ced4da; /* Màu chờ (pending) */
                border: 4px solid #f4f7f6; /* Tạo viền rỗng (màu nền trang) */
                z-index: 2;
                transition: background-color 0.3s ease;
            }
            .timeline-date {
                font-size: 0.9rem;
                font-weight: 700;
                color: #6c757d;
                margin-bottom: 5px;
            }
            .timeline-content {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.06);
                border: 1px solid #e9ecef;
                border-left-width: 4px; /* Viền trạng thái bên trái */
                border-left-color: #ced4da; /* Màu chờ */
                transition: border-left-color 0.3s ease;
            }
            .timeline-content h4 {
                font-family: 'Poppins', sans-serif;
                font-size: 1.3rem;
                color: var(--mau-chu);
                margin-top: 0;
                margin-bottom: 15px;
            }
            .timeline-image {
                margin-bottom: 15px;
                border-radius: 6px;
                overflow: hidden;
                border: 1px solid #eee;
            }
            .timeline-description p {
                margin-bottom: 10px;
            }
            .timeline-description p:last-child {
                margin-bottom: 0;
            }
            .timeline-item.status-completed .timeline-dot {
                background-color: #28a745; /* Xanh lá */
            }
            .timeline-item.status-completed .timeline-content {
                border-left-color: #28a745;
            }
            .timeline-item.status-completed .timeline-date {
                color: #28a745;
            }
            .timeline-item.status-ongoing .timeline-dot {
                background-color: #007bff; /* Xanh dương */
                animation: pulse-blue 2s infinite; /* Hiệu ứng nhấp nháy */
            }
            .timeline-item.status-ongoing .timeline-content {
                border-left-color: #007bff;
            }
            .timeline-item.status-ongoing .timeline-date {
                color: var(--mau-chu);
            }
            @keyframes pulse-blue {
                0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
                100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
            }
            @media (max-width: 600px) {
                .construction-timeline-container {
                    padding-left: 20px;
                }
                .timeline-dot {
                    left: -32px; /* Dịch lại chấm tròn */
                    width: 20px;
                    height: 20px;
                }
                .timeline-content {
                    padding: 15px;
                }
            }
        ";
        echo $css_content;
    }

    // --- [BẮT ĐẦU KHỐI MỚI] ---
    
    // 8. NẠP CSS CHO BĐS (LAYOUTS)
    if ( is_page_template('template-homepage-bds.php') || is_page_template('category-silo.php') ) {
        echo "
        /* CSS CHO TEMPLATE TRANG CHỦ BĐS (template-homepage-bds.php) */
        .homepage-bds-container .homepage-section { margin-top: 25px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #e9ecef; }
        .homepage-bds-container .homepage-section:last-child { border-bottom: none; padding-bottom: 0; }
        .homepage-section-header { margin-bottom: 25px; }
        .homepage-section-title { font-size: 1.8rem; font-family: 'Poppins', sans-serif; font-weight: 700; color: var(--mau-chu); margin: 0 0 15px 0; text-align: center; line-height: 1.3; }
        .homepage-view-all-btn { display: block; width: -moz-fit-content; width: fit-content; margin: 0 auto; padding: 8px 20px; background-color: #f0f4f8; border: 1px solid #dee2e6; color: var(--mau-chu); border-radius: 50px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s ease; }
        .homepage-view-all-btn:hover { background-color: #e9ecef; border-color: #ced4da; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .homepage-view-all-btn span { transition: transform 0.2s ease; display: inline-block; }
        .homepage-view-all-btn:hover span { transform: translateX(4px); }
        .main-post-wrapper .post-card { box-shadow: 0 8px 25px rgba(0, 86, 179, 0.1); }
        .homepage-banner-ad { display: block; margin-top: 15px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.12); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .homepage-banner-ad:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        @media (min-width: 600px) {
            .homepage-bds-container .homepage-section { margin-top: 40px; margin-bottom: 40px; padding-bottom: 40px; }
            .homepage-section-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 30px; }
            .homepage-section-title { text-align: left; margin-bottom: 0; }
            .homepage-view-all-btn { background: none; border: none; padding: 0; margin: 0; font-size: 1rem; color: var(--mau-chinh); }
            .homepage-view-all-btn:hover { background: none; border: none; transform: none; box-shadow: none; text-decoration: underline; }
        }
        @media (min-width: 768px) {
            .main-post-wrapper .post-card a { flex-direction: row; align-items: stretch; }
            .main-post-wrapper .post-card-image-wrapper { flex: 1 0 45%; min-height: 280px; }
            .main-post-wrapper .post-card-content { flex: 1 0 55%; padding: 25px 30px; justify-content: center; }
            .main-post-wrapper .post-card-title { font-size: 1.5rem; -webkit-line-clamp: 4; }
        }
        @media (min-width: 900px) {
            .main-post-wrapper .post-card-image-wrapper { flex: 1 0 50%; }
            .main-post-wrapper .post-card-content { flex: 1 0 50%; }
        }
        ";
    }

    // 9. NẠP CSS CHO BĐS (SHORTCODES & CPT)
    if ( is_singular('property') || (is_singular() && (
        has_shortcode($content, 'amp_slider') ||
        has_shortcode($content, 'chi_tiet_bds') ||
        has_shortcode($content, 'tinh_lai_suat') ||
        has_shortcode($content, 'tien_ich_xung_quanh') ||
        has_shortcode($content, 'amp_product')
    ))) {
        echo "
        /* CSS NÂNG CAO CHO AMP SLIDER */
        .amp-slider-container { margin: 30px 0; border-radius: 12px; overflow: hidden; position: relative; -webkit-mask-image: -webkit-radial-gradient(white, black); }
        .amp-slider-container amp-img { transition: transform 8s ease-in-out; transform: scale(1.05); }
        .amp-slider-container [aria-hidden=false] amp-img { transform: scale(1); }
        .amp-slider-container .amp-carousel-button { background-color: rgba(255, 255, 255, 0.15); backdrop-filter: blur(5px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 50%; width: 44px; height: 44px; top: calc(50% - 22px); transition: background-color 0.2s, transform 0.2s; transform: scale(0.9); opacity: 0; }
        .amp-slider-container:hover .amp-carousel-button { transform: scale(1); opacity: 1; }
        .amp-slider-container .amp-carousel-button:hover { background-color: rgba(255, 255, 255, 0.3); transform: scale(1.05); }
        .amp-slider-container .amp-carousel-button-prev .amp-carousel-button-img { background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"15 18 9 12 15 6\"></polyline></svg>'); }
        .amp-slider-container .amp-carousel-button-next .amp-carousel-button-img { background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"9 18 15 12 9 6\"></polyline></svg>'); }
        .amp-slider-container .amp-carousel-pagination { position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; padding: 6px 10px; background-color: rgba(0, 0, 0, 0.3); backdrop-filter: blur(5px); border-radius: 20px; }
        .amp-slider-container .amp-carousel-pagination-button { width: 10px; height: 10px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.5); transition: background-color 0.3s, transform 0.3s; padding: 0; }
        .amp-slider-container .amp-carousel-pagination-button[aria-selected=true] { background-color: #fff; transform: scale(1.2); }
        
        /* CSS CHO TÍNH NĂNG BẤT ĐỘNG SẢN */
        .bds-details-box { background-color: #f7f9fb; border: 1px solid #e9ecef; border-radius: 12px; padding: 25px; margin: 30px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .bds-details-title { margin-top: 0; margin-bottom: 20px; font-size: 1.4rem; color: var(--mau-chu); border-bottom: 2px solid var(--mau-chinh); padding-bottom: 10px; font-family: 'Poppins', sans-serif; }
        .bds-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .bds-detail-item { display: flex; flex-direction: column; padding: 15px; background-color: #fff; border-radius: 8px; border: 1px solid #eee; transition: transform 0.2s, box-shadow 0.2s; }
        .bds-detail-item:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
        .bds-detail-label { font-size: 0.85rem; color: #6c757d; margin-bottom: 5px; text-transform: uppercase; font-weight: 600; }
        .bds-detail-value { font-size: 1.1rem; font-weight: 600; color: #343a40; }
        .bds-detail-value.price { color: #dc3545; font-weight: 700; font-size: 1.2rem; }
        .bds-details-cta { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e9ecef; }
        .bds-details-button { display: inline-block; background-color: var(--mau-chu); color: #fff; padding: 12px 30px; border-radius: 50px; font-weight: 700; text-decoration: none; font-size: 1rem; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); }
        .bds-details-button:hover { color: #fff; text-decoration: none; transform: translateY(-3px); box-shadow: 0 7px 20px rgba(0, 123, 255, 0.35); }
        .mortgage-calculator { background-color: #fff; border: 2px dashed var(--mau-chinh); border-radius: 12px; padding: 25px; margin: 40px 0; }
        .calculator-title { text-align: center; margin-top: 0; margin-bottom: 25px; color: var(--mau-chu); font-family: 'Poppins', sans-serif; }
        .mortgage-calculator .form-row { margin-bottom: 15px; }
        .mortgage-calculator label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.9rem; color: #495057; }
        .mortgage-calculator input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .calculator-result { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; opacity: 0; transform: translateY(10px); transition: opacity 0.4s, transform 0.4s; }
        .mortgage-calculator.calculated .calculator-result { opacity: 1; transform: translateY(0); }
        .calculator-result h4 { margin: 0 0 10px 0; color: #555; font-size: 1rem; font-weight: normal; }
        .monthly-payment span { font-size: 2.2rem; font-weight: 700; color: #dc3545; font-family: 'Poppins', sans-serif; }
        .featured-properties-wrapper { margin: 40px 0; padding: 30px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; background-color: #fff; }
        .featured-properties-title { text-align: center; font-size: 1.8rem; color: var(--mau-chu); margin-top: 0; margin-bottom: 30px; font-family: 'Poppins', sans-serif; }
        .featured-properties-wrapper .posts-grid-container { max-width: 900px; margin: 0 auto; padding: 0 15px; }
        .utilities-map-section { display: flex; flex-wrap: wrap; gap: 30px; margin: 40px 0; }
        .map-container, .utilities-accordion-container { flex: 1; min-width: 300px; }
        .map-container { border-radius: 12px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 1px solid #eee; }
        .utilities-accordion-container amp-accordion section { border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 10px; background-color: #fff; }
        .utility-question { font-size: 1.1rem; font-weight: 600; padding: 15px; margin: 0; cursor: pointer; background-color: #f8f9fa; display: flex; align-items: center; gap: 10px; }
        .utility-answer { padding: 15px; font-size: 0.95rem; }
        .utility-answer ul { margin: 0; padding-left: 20px; }
        .utility-answer li { margin-bottom: 8px; }
        .utility-icon { display: inline-block; width: 24px; height: 24px; background-color: var(--mau-chinh); -webkit-mask-size: contain; mask-size: contain; -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat; -webkit-mask-position: center; mask-position: center; }
        .icon-school { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M12 3L1 9l11 6 9-4.5V12h2V9l-11-6zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z\"/></svg>'); mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M12 3L1 9l11 6 9-4.5V12h2V9l-11-6zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z\"/></svg>');}
        .icon-hospital { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z\"/></svg>'); mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z\"/></svg>');}
        .icon-market { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.24 17 6.5 17H20v-2H6.5c-.3 0-.5-.1-.6-.2l1.1-2h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A.996.996 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z\"/></svg>'); mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.24 17 6.5 17H20v-2H6.5c-.3 0-.5-.1-.6-.2l1.1-2h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A.996.996 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z\"/></svg>');}
        .icon-park { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M17.5 11.23c0 2.2-2.1 4.9-5.5 8.27-3.4-3.37-5.5-6.07-5.5-8.27 0-3.1 2.46-5.5 5.5-5.5s5.5 2.4 5.5 5.5zM12 8.75c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z\"/></svg>'); mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M17.5 11.23c0 2.2-2.1 4.9-5.5 8.27-3.4-3.37-5.5-6.07-5.5-8.27 0-3.1 2.46-5.5 5.5-5.5s5.5 2.4 5.5 5.5zM12 8.75c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z\"/></svg>');}
        .icon-default { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z\"/></svg>'); mask-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"white\"><path d=\"M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z\"/></svg>');}
        .amp-product-box { display: block; text-decoration: none; background-color: #fff; border: 1px solid #e9ecef; border-radius: 12px; margin: 30px 0; box-shadow: 0 6px 20px rgba(0,0,0,0.07); overflow: hidden; }
        .amp-product-content { padding: 20px; }
        .amp-product-title { margin: 0 0 10px 0; font-size: 1.6rem; font-family: 'Poppins', sans-serif; color: var(--mau-chu); }
        .amp-product-brand { font-size: 0.9rem; color: #6c757d; margin-bottom: 15px; }
        .amp-product-price { font-size: 2rem; font-weight: 700; color: #dc3545; margin-bottom: 15px; }
        .amp-product-description { font-size: 0.95rem; line-height: 1.7; color: #495057; margin-bottom: 20px; }
        .amp-product-box .rating-box { margin: 0; padding: 0; border: none; background: 0 0; }
        @media (min-width: 768px) {
            .bds-details-box { padding: 20px 15px; }
            .bds-details-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .bds-detail-item { padding: 12px; }
            .bds-detail-label { font-size: 0.75rem; }
            .bds-detail-value { font-size: 1rem; }
            .bds-detail-value.price { font-size: 1.1rem; }
            .amp-product-box { display: grid; grid-template-columns: 1fr 1.2fr; gap: 25px; align-items: start; }
            .amp-product-image-wrapper { padding: 15px; }
            .amp-product-content { padding: 20px 20px 20px 0; }
        }
        ";
    }

    // 10. NẠP CSS CHO IMAGE MAP
    if ( is_singular() && has_shortcode( $content, 'amp_imagemap' ) ) {
        echo "
        /* CSS CHO IMAGE MAP & HOTSPOT */
        .hotspot-title-display { display: none; margin-top: 8px; padding: 4px 10px; background-color: #fff; color: #222; font-size: 8px; font-weight: 700; border-radius: 15px; white-space: nowrap; box-shadow: 0 3px 6px rgba(0,0,0,0.25); border: 1px solid #ddd; opacity: 0; transform: translateY(-5px); transition: opacity 0.2s, transform 0.2s; pointer-events: none; }
        .css-hotspot-wrapper:hover .hotspot-title-display { opacity: 1; transform: translateY(0); }
        @media (min-width: 600px) {
            .hotspot-title-display { display: block; opacity: 1; transform: translateY(0); pointer-events: auto; }
        }
        @keyframes sonar-wave { 0% { transform: scale(0.7); opacity: 0.8; } 100% { transform: scale(3); opacity: 0; } }
        .amp-css-imagemap-wrapper { position: relative; border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 12px rgba(0,0,0,0.06); }
        .css-hotspot-wrapper { position: absolute; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; z-index: 2; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .css-hotspot-wrapper:hover { transform: translate(-50%, -50%) scale(1.1); z-index: 3; }
        .hotspot-on-image { position: relative; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; z-index: 2; background-color: var(--mau-chinh); cursor: pointer; border: none; border-radius: 50%; box-sizing: border-box; box-shadow: 0 3px 8px rgba(0,0,0,0.3); }
        .hotspot-on-image::before, .hotspot-on-image::after { content: ''; position: absolute; top: 0; left: 0; display: block; width: 100%; height: 100%; border-radius: 50%; background-color: var(--mau-chinh); z-index: -1; animation: sonar-wave 2.2s infinite ease-out; }
        .hotspot-on-image::after { animation-delay: -1.1s; }
        .amp-css-hotspot-list-wrapper { margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; }
        .amp-css-hotspot-list-wrapper .hotspot-list-title { margin-top: 0; margin-bottom: 15px; font-size: 1.2rem; font-weight: 700; color: var(--mau-chu); text-align: center; }
        .hotspot-list-ui { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .hotspot-list-button { display: block; width: 100%; padding: 8px 18px; background-color: #f8f9fa; color: var(--mau-chu); border: 1px solid #dee2e6; border-radius: 50px; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; text-align: center; box-sizing: border-box; transition: all 0.2s ease-in-out; }
        .hotspot-list-button:hover, .hotspot-list-ui li.hotspot-highlighted .hotspot-list-button:hover { background-color: var(--mau-chu); color: #ffffff; }
        .amp-lightbox-map .lightbox-content-wrapper { background: rgba(23, 33, 43, 0.85); backdrop-filter: blur(4px); width: 100%; height: 100%; position: fixed; top: 0; left: 0; display: flex; align-items: center; justify-content: center; box-sizing: border-box; }
        .amp-lightbox-map .lightbox-content { background: #ffffff; padding: 30px 35px; border-radius: 12px; width: 600px; max-width: 90vw; max-height: 85vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border-top: 4px solid var(--mau-chinh); }
        .amp-lightbox-map .lightbox-content h3 { margin-top: 0; margin-bottom: 15px; color: var(--mau-chu); font-size: 1.8rem; font-family: 'Poppins', sans-serif; }
        .amp-lightbox-map .lightbox-content div[html-bind] { line-height: 1.8; color: #333; font-size: 1rem; }
        .amp-lightbox-map .lightbox-content div[html-bind] p:last-child { margin-bottom: 0; }
        .amp-lightbox-map .lightbox-buttons { margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 12px; }
        .amp-lightbox-map .lightbox-cta-button, .amp-lightbox-map .lightbox-close-button { padding: 10px 25px; border: none; border-radius: 50px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .amp-lightbox-map .lightbox-cta-button { background-color: var(--mau-chinh); color: #ffffff; text-decoration: none ; box-shadow: 0 4px 15px rgba(0, 115, 230, 0.2); }
        .amp-lightbox-map .lightbox-cta-button:hover { transform: translateY(-3px); box-shadow: 0 7px 20px rgba(0, 115, 230, 0.35); }
        .amp-lightbox-map .lightbox-close-button { background-color: #f1f3f5; color: #495057; }
        .amp-lightbox-map .lightbox-close-button:hover { background-color: #e9ecef; }
        .css-hotspot-wrapper.hotspot-dimmed { opacity: 0.3; filter: blur(2px); transition: opacity 0.3s, filter 0.3s; }
        .css-hotspot-wrapper.hotspot-dimmed:hover { opacity: 1; filter: none; z-index: 4; }
        .css-hotspot-wrapper.hotspot-highlighted { z-index: 10; }
        .css-hotspot-wrapper.hotspot-highlighted .hotspot-on-image { background-color: #dc3545; transform: scale(1.3); border: 2px solid #fff; box-shadow: 0 0 20px rgba(220, 53, 69, 0.7); }
        .css-hotspot-wrapper.hotspot-dimmed .hotspot-on-image::before, .css-hotspot-wrapper.hotspot-dimmed .hotspot-on-image::after { animation: none; }
        .hotspot-list-ui li.hotspot-dimmed { opacity: 0.4; transition: opacity 0.3s; }
        .hotspot-list-ui li.hotspot-dimmed:hover { opacity: 1; }
        .hotspot-list-ui li.hotspot-highlighted .hotspot-list-button { background-color: var(--mau-chu); color: #ffffff; border-color: var(--mau-chinh); transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2); }
        @media (max-width: 600px) { .amp-css-hotspot-list-wrapper { margin-top: 20px; padding-top: 15px; } .hotspot-list-button { padding: 8px 16px; font-size: 0.85rem; } .amp-lightbox-map .lightbox-content { padding: 25px 20px; max-width: calc(100vw - 30px); max-height: calc(100vh - 30px); } .amp-lightbox-map .lightbox-content h3 { font-size: 1.5rem; } .amp-lightbox-map .lightbox-buttons { flex-direction: column-reverse; } .amp-lightbox-map .lightbox-cta-button, .amp-lightbox-map .lightbox-close-button { width: 100%; text-align: center; } }
        ";
    }

    // 11. NẠP CSS CHO EVENT BAR
    if ( is_singular() && has_shortcode( $content, 'amp_event_bar' ) ) {
        echo "
        /* CSS CHO THANH THÔNG BÁO SỰ KIỆN [amp_event_bar] */
        #amp-event-bar { margin: 0; width: 100%; max-width: none; background-color: #121212; height: 40px; overflow: hidden; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); border-radius: 0; position: relative; }
        #amp-event-bar .amp-carousel-slide { background-color: #121212; }
        #amp-event-bar .event-slide { display: flex; align-items: center; height: 100%; box-sizing: border-box; }
        #amp-event-bar .event-notification-link { display: flex; align-items: center; text-decoration: none; padding: 0 10px; height: 100%; width: 100%; transition: background-color 0.2s; }
        #amp-event-bar .event-notification-link:hover { background-color: #282828; }
        #amp-event-bar .event-description-text, #amp-event-bar a, #amp-event-bar p { color: #ffffff; }
        #amp-event-bar .event-description-text { margin: 0; flex-grow: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px; }
        #amp-event-bar .event-description-text strong { color: #00e676; font-weight: 700; }
        #amp-event-bar .sonar-icon-wrap { position: relative; width: 20px; height: 20px; margin-right: 8px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; }
        #amp-event-bar .event-status-icon { font-size: 14px; z-index: 10; line-height: 1; }
        #amp-event-bar .sonar-pulse { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 8px; height: 8px; background: #ff5252; border-radius: 50%; opacity: 0.9; animation: sonar-animation 1.8s infinite cubic-bezier(0.2, 0, 0.4, 1); z-index: 1; }
        @keyframes sonar-animation { 0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.7; } 100% { transform: translate(-50%, -50%) scale(2.8); opacity: 0; } }
        #amp-event-bar div[aria-hidden=true] a.event-notification-link { pointer-events: none; tabindex: -1; }
        ";
    }

    // --- [KẾT THÚC KHỐI MỚI] ---

    // 12. NẠP CSS CHO TRANG CẢM ƠN (page-camon.php)
    if ( is_page_template('page-camon.php') ) {
        $css_content = "
            .thank-you-wrapper { display: flex; align-items: center; justify-content: center; padding: 50px 20px; min-height: 60vh; background-color: #f4f7f6; }
            .thank-you-container { text-align: center; padding: 40px 30px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); max-width: 550px; width: 100%; border-top: 5px solid #28a745; transform: translateY(-20px); animation: fadeInUp 0.5s ease-out forwards; }
            @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
            .thank-you-icon { display: inline-block; width: 80px; height: 80px; background-color: #28a745; border-radius: 50%; position: relative; }
            .thank-you-icon::after { content: ''; position: absolute; left: 28px; top: 15px; width: 20px; height: 40px; border: solid white; border-width: 0 6px 6px 0; transform: rotate(45deg); }
            .thank-you-title { font-size: 28px; color: #333; margin: 25px 0 15px 0; font-weight: 700; }
            .thank-you-message { font-size: 18px; color: #666; line-height: 1.7; }
            .thank-you-back-home { display: inline-block; margin-top: 30px; padding: 14px 35px; background-color: var(--mau-chinh, #0073e6); color: #fff; text-decoration: none; border-radius: 50px; font-weight: 700; transition: transform 0.2s, box-shadow 0.2s; border: none; box-shadow: 0 4px 15px rgba(0, 115, 230, 0.4); }
            .thank-you-back-home:hover { transform: translateY(-3px); box-shadow: 0 7px 20px rgba(0, 115, 230, 0.5); color: #fff; text-decoration: none; }
            @media (max-width: 768px) { .thank-you-wrapper { padding: 30px 15px; } .thank-you-title { font-size: 24px; } .thank-you-message { font-size: 16px; } }
        ";
        echo $css_content;
    }

    // 13. NẠP CSS CHO SHORTCODE [schema_faq] VÀ [schema_howto]
    if ( is_singular() && ( has_shortcode( $content, 'schema_faq' ) || has_shortcode( $content, 'schema_howto' ) ) ) {
        $css_content = "
            /* === CSS CHO FAQ ACCORDION === */
            .faq-container { margin: 40px 0; border-top: 1px solid #e0e0e0; }
            .faq-container amp-accordion section { margin-bottom: 0; }
            .faq-question { background-color: #f9f9f9; padding: 15px 20px; margin: 0; font-size: 18px; font-weight: 700; border-bottom: 1px solid #e0e0e0; cursor: pointer; position: relative; }
            .faq-question:hover { background-color: #f1f1f1; }
            .faq-question::after { content: '+'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 24px; font-weight: 400; color: var(--mau-chinh); }
            .faq-container amp-accordion section[expanded] > .faq-question::after { content: '−'; }
            .faq-answer { padding: 20px; background-color: #ffffff; border-bottom: 1px solid #e0e0e0; }
            .faq-answer p:first-child { margin-top: 0; }
            .faq-answer p:last-child { margin-bottom: 0; }

            /* === CSS CHO HOWTO === */
            .howto-container { margin: 40px 0; padding: 25px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fcfcfc; }
            .howto-title { font-size: 24px; margin-top: 0; margin-bottom: 20px; color: var(--mau-chu); }
            .howto-steps { padding-left: 20px; margin: 0; }
            .howto-steps li { margin-bottom: 20px; }
            .howto-steps li:last-child { margin-bottom: 0; }
            .howto-step-title { display: block; font-size: 18px; margin-bottom: 5px; color: var(--mau-chu); }
            .howto-steps div p { margin: 0; }
        ";
        echo $css_content;
    }

    // 14. NẠP CSS CHO HỘP ĐÁNH GIÁ (RATING BOX)
    // Được sử dụng bởi hàm auto-rating và shortcode [amp_product]
    if ( is_singular() ) {
        $css_content = "
            /* === CSS CHO SCHEMA RATING (STARS) === */
            .rating-box {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px 12px;
                background-color: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px 20px;
                margin: 30px 0;
            }
            .star-rating {
                position: relative;
                display: inline-block;
                height: 24px;
                line-height: 24px;
            }
            .star-rating-background,
            .star-rating-foreground {
                display: flex;
            }
            .star-rating-background {
                color: #ccc;
            }
            .star-rating-foreground {
                position: absolute;
                top: 0;
                left: 0;
                color: #ffb400;
                overflow: hidden;
                white-space: nowrap;
            }
            .star-rating svg {
                width: 24px;
                height: 24px;
                fill: currentColor;
                flex-shrink: 0;
            }
            .rating-text {
                font-size: 0.95rem;
                color: #495057;
            }
            .rating-text strong {
                color: #212529;
            }
        ";
        echo $css_content;
    }
}
// Hook hàm mới vào 'amp_custom_css' với độ ưu tiên 11 (chạy sau hàm gốc)
add_action('amp_custom_css', 'tuancele_load_conditional_css', 11);