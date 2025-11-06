<?php
/**
 * inc/helpers/conditional-css.php
 *
 * Tải CSS có điều kiện để giảm dung lượng file CSS chính,
 * tránh lỗi 75KB của AMP.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Nạp CSS cho các template trang đặc biệt.
 *
 * Hàm này được hook vào 'amp_custom_css' (trong theme-setup.php),
 * nó chạy SAU khi file amp-custom.min.css chính đã được nạp.
 */
function tuancele_load_conditional_css() {

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
    
    // (Bạn có thể thêm các khối `if ( is_page_template(...) )` khác ở đây)

}
// Hook hàm mới vào 'amp_custom_css' với độ ưu tiên 11 (chạy sau hàm gốc)
add_action('amp_custom_css', 'tuancele_load_conditional_css', 11);