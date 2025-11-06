<?php
/**
 * inc/shortcodes-module.php
 * Module Class cho việc đăng ký và xử lý TẤT CẢ các shortcode của theme.
 *
 * [TỐI ƯU HOWTO V28] - Giải pháp cuối cùng.
 * 1. Gỡ bỏ filter 'no_texturize_shortcodes' (trong __construct)
 * để cho phép wptexturize chạy nhất quán trên cả Post và Page.
 * 2. Thêm logic chuẩn hóa dấu nháy cong (str_replace) vào hàm cha [schema_howto]
 * để sửa lỗi do wptexturize gây ra TRƯỚC KHI do_shortcode.
 * 3. Khôi phục hàm con [step] về logic $atts đơn giản, vì $atts
 * bây giờ sẽ luôn được parse chính xác.
 *
 * [TỐI ƯU LAI SUAT v1]: Sửa lỗi duplicate ID của [tinh_lai_suat] bằng uniqid().
 * [TỐI ƯU IMAGEMAP V6]: Sửa HTML để hiển thị tiêu đề hotspot cố định.
 * [THÊM MỚI]: Thêm shortcode [ab_test_variant]
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AMP_Shortcodes_Module {

    // Biến tạm để lưu các steps khi shortcode [howto] chạy
    private $howto_steps = [];

    /**
     * Khởi tạo module, đăng ký tất cả các hook và shortcode.
     */
    public function __construct() {
        // Đăng ký tất cả các shortcode
        add_shortcode('form_dang_ky', [ $this, 'form_dang_ky' ]);
        add_shortcode('schema_faq', [ $this, 'schema_faq' ]);

        // --- BỘ FIX HOWTO (V28) ---
        add_shortcode('schema_howto', [ $this, 'schema_howto' ]); // Hàm cha
        add_shortcode('step', [ $this, 'schema_howto_step' ]); // Hàm con
        
        // [FIX V28] Gỡ bỏ filter gây ra sự không nhất quán giữa Post và Page
        // add_filter( 'no_texturize_shortcodes', [ $this, 'prevent_texturize_in_howto' ] );
        // --- KẾT THÚC BỘ FIX HOWTO ---

        add_shortcode('amp_slider', [ $this, 'amp_slider' ]);
        add_shortcode('chi_tiet_bds', [ $this, 'chi_tiet_bds' ]);
        add_shortcode('tinh_lai_suat', [ $this, 'tinh_lai_suat' ]);
        add_shortcode('bds_noibat', [ $this, 'bds_noibat' ]);
        add_shortcode('tien_ich_xung_quanh', [ $this, 'tien_ich_wrapper' ]);
        add_shortcode('tien_ich', [ $this, 'tien_ich_item' ]);
        add_shortcode('geo_display', [ $this, 'geo_display' ]);
        add_shortcode('geo_option', [ $this, 'geo_option' ]);
        add_shortcode('amp_product', [ $this, 'amp_product' ]);
        add_shortcode('quang_cao_noi_bo', [ $this, 'internal_ad' ]);
        add_shortcode('dang_ky_sdt', [ $this, 'phone_registration' ]);
        add_shortcode('amp_imagemap', [ $this, 'amp_imagemap' ]);
        add_shortcode('amp_event_bar', [ $this, 'amp_event_bar' ]);
        
       // Đảm bảo 2 dòng này tồn tại
    add_shortcode('ab_test_wrapper', [ $this, 'ab_test_wrapper' ]);
    add_shortcode('ab_test_variant', [ $this, 'ab_test_variant' ]);
        
        // Hook tự động chèn quảng cáo nội bộ
        add_filter('the_content', [ $this, 'auto_inject_internal_ad' ], 10);
    }

    // [FIX V28] Gỡ bỏ hàm này (không cần thiết)
    // public function prevent_texturize_in_howto( $shortcodes ) { ... }

    // =========================================================================
    // CÁC HÀM CALLBACK CHO SHORTCODE
    // =========================================================================

    /**
     * SHORTCODE [form_dang_ky]
     *
     */
    public function form_dang_ky($atts) {
        $args = shortcode_atts(['tieu_de' => 'Đăng Ký Tư Vấn Miễn Phí', 'nut_gui' => 'Gửi Thông Tin Ngay'], $atts);
        if (function_exists('get_amp_form_html')) {
            return get_amp_form_html($args); // Hàm này nằm trong inc/integrations.php
        }
        return '';
    }

    /**
     * [SỬA LỖI A/B TEST V8 - FINAL] SHORTCODE WRAPPER (CHA)
     * Mô phỏng logic dọn dẹp <p> từ [tien_ich_wrapper]
     */
    public function ab_test_wrapper($atts, $content = null) {
        $atts = shortcode_atts(['experiment' => ''], $atts, 'ab_test_wrapper');
        if (empty($atts['experiment'])) return '<div class="shortcode-error">Lỗi A/B Test: Thiếu tên "experiment".</div>';

        // Lấy Config JSON
        $all_configs_json = get_option('tuancele_ab_testing_settings', '{}');
        $all_configs = json_decode($all_configs_json, true);
        if (!isset($all_configs[$atts['experiment']])) {
             return '<div class="shortcode-error">Lỗi A/B Test: Không tìm thấy config cho "' . esc_html($atts['experiment']) . '".</div>';
        }
        $experiment_config = $all_configs[$atts['experiment']];
        $experiment_json = json_encode([$atts['experiment'] => $experiment_config], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // --- QUY TRÌNH DỌN DẸP WP-AUTOP (THEO [tien_ich_wrapper]) ---

        // 1. [FIX V8] Dọn dẹp <p> và <br> mà wpautop
        // đã thêm VÀO GIỮA các shortcode con [ab_test_variant].
        // Đây là logic từ [tien_ich_wrapper] và [schema_faq]
        $content_clean = str_replace( ['<p>', '</p>', '<br />', '<br>'], '', $content );
        
        // 2. [FIX V8 - THAM KHẢO HOWTO]
        // Xử lý dấu nháy cong (wptexturize) có thể làm hỏng shortcode con
        $curly_quotes = ['“', '”', '‘', '’'];
        $straight_quotes = ['"', '"', "'", "'"];
        $content_clean = str_replace( $curly_quotes, $straight_quotes, $content_clean );

        // 3. Chạy do_shortcode trên nội dung đã dọn dẹp.
        // Thao tác này sẽ gọi hàm ab_test_variant (V8)
        // và nhận về các chuỗi HTML <div option="...">
        $variant_html = do_shortcode($content_clean);
        
        // 4. Xây dựng HTML
        ob_start();
        ?>
        <amp-experiment>
            <script type="application/json">
                <?php echo $experiment_json; ?>
            </script>
            <?php echo $variant_html; // In các thẻ <div option="..."> đã hợp lệ ?>
        </amp-experiment>
        <?php
        
        return ob_get_clean();
    }

    /**
     * SHORTCODE [schema_faq]
     *
     */
    public function schema_faq( $atts, $content = null ) {
        // Dọn dẹp <p> và <br>
        $content = str_replace( ['<p>', '</p>', '<br />', '<br>'], ['', '', "\n", "\n"], $content );
        preg_match_all( '/\[q\](.*?)\[\/q\]\s*\[a\](.*?)\[\/a\]/s', $content, $matches );
        if ( empty( $matches[1] ) ) return '<div class="shortcode-error">[LỖI: Shortcode FAQ sai cú pháp]</div>';

        $main_entity = [];
        $visible_html = '<div class="faq-container"><amp-accordion>';
        for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
            $question = trim( strip_tags( $matches[1][$i] ) );
            $answer = trim( $matches[2][$i] );
            $main_entity[] = [ '@type' => 'Question', 'name' => $question, 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => strip_tags($answer) ] ];
            $visible_html .= '<section><h4 class="faq-question">' . esc_html($question) . '</h4><div class="faq-answer">' . wpautop($answer) . '</div></section>';
        }
        $visible_html .= '</amp-accordion></div>';
        $schema = [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $main_entity ];
        $schema_output = '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
        return $visible_html . $schema_output;
    }

    /**
     * SHORTCODE [schema_howto] (Wrapper)
     * [FIX V28] - Chuẩn hóa dấu nháy cong
     */
    public function schema_howto( $atts, $content = null ) {
        $args = shortcode_atts( [ 'title' => '', 'total_time' => '', ], $atts, 'schema_howto' );
        if ( empty( $args['title'] ) ) {
            return '<div class="shortcode-error">[LỖI: Shortcode HowTo thiếu thuộc tính "title"]</div>';
        }

        // 1. Reset mảng steps
        $this->howto_steps = []; 
        
        // 2. [FIX V28] CHUẨN HÓA DẤU NGOẶC KÉP
        // Đổi tất cả dấu ngoặc kép cong (do wptexturize gây ra)
        // thành dấu ngoặc kép thẳng (") mà trình parser của WP có thể hiểu.
        $curly_quotes = ['“', '”', '‘', '’'];
        $straight_quotes = ['"', '"', "'", "'"];
        $content = str_replace( $curly_quotes, $straight_quotes, $content );

        // 3. Chạy do_shortcode() với $content đã được chuẩn hóa
        // Vì $content đã sạch, WP sẽ parse [step title='...'] chính xác
        $list_items_html = do_shortcode( $content );

        // 4. Kiểm tra xem có step nào được xử lý không
        if ( empty( $this->howto_steps ) ) {
            return '<div class="shortcode-error">[LỖI: Shortcode HowTo không tìm thấy thẻ [step] nào. Vui lòng kiểm tra cú pháp.] (V28)</div>';
        }

        // 5. Bọc HTML
        $visible_html = '<div class="howto-container">';
        $visible_html .= '<h2 class="howto-title">' . esc_html( $args['title'] ) . '</h2>';
        $visible_html .= '<ol class="howto-steps">' . $list_items_html . '</ol>';
        $visible_html .= '</div>';
        
        // 6. Tạo Schema từ mảng đã được populate
        $schema = [ 
            '@context' => 'https://schema.org', 
            '@type' => 'HowTo', 
            'name' => $args['title'], 
            'step' => $this->howto_steps // Dùng mảng đã được populate
        ];
        if( ! empty( $args['total_time'] ) ) { $schema['totalTime'] = $args['total_time']; }
        $schema_output = '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
        
        // 7. Trả về
        return $visible_html . $schema_output;
    }

    /**
     * SHORTCODE [step] (con của [schema_howto])
     * [FIX V28] - Quay lại logic đơn giản, chỉ dùng $atts
     */
    public function schema_howto_step( $atts, $content = null ) {
        
        // [FIX V28] Quay lại logic đơn giản.
        // Vì hàm cha (V28) đã dọn dẹp $content,
        // $atts nhận được ở đây sẽ luôn chính xác.
        // (ví dụ: $atts['title'] = 'Bước 1')

        $step_title = 'Không có tiêu đề';
        if ( ! empty( $atts['title'] ) ) {
            $step_title = $atts['title'];
        }
        
        // 2. Dọn dẹp nội dung
        $step_text_clean = str_replace( ['<p>', '</p>', '<br />', '<br>'], ['', '', "\n", "\n"], $content );

        // 3. Thêm dữ liệu vào mảng schema
        $this->howto_steps[] = [ 
            '@type' => 'HowToStep', 
            'name'  => wp_strip_all_tags($step_title), 
            'text'  => wp_strip_all_tags($step_text_clean) 
        ];
        
        // 4. Trả về HTML cho một mục <li>
        return '<li><strong class="howto-step-title">' . esc_html($step_title) . '</strong><div>' . wpautop($step_text_clean) . '</div></li>';
    }

    /**
     * SHORTCODE [amp_slider]
     *
     */
    public function amp_slider( $atts ) {
        $atts = shortcode_atts( ['ids' => '', 'width' => '1600', 'height' => '900'], $atts, 'amp_slider' );
        if ( empty( $atts['ids'] ) ) return '<p style="color:red;">Lỗi Slider: Vui lòng cung cấp ID của ảnh. Ví dụ: [amp_slider ids="1,2,3"]</p>';
        $image_ids = array_map( 'intval', explode( ',', $atts['ids'] ) );
        ob_start();
        ?>
        <div class="amp-slider-container">
            <amp-carousel width="<?php echo esc_attr( $atts['width'] ); ?>" height="<?php echo esc_attr( $atts['height'] ); ?>" layout="responsive" type="slides" controls loop autoplay delay="4000">
                <?php foreach ( $image_ids as $id ) :
                    $image_data = wp_get_attachment_image_src( $id, 'large' );
                    $alt_text = get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title($id);
                    if ( $image_data ) : ?>
                        <amp-img src="<?php echo esc_url( $image_data[0] ); ?>" width="<?php echo esc_attr( $image_data[1] ); ?>" height="<?php echo esc_attr( $image_data[2] ); ?>" layout="responsive" alt="<?php echo esc_attr( $alt_text ); ?>"></amp-img>
                    <?php endif;
                endforeach; ?>
            </amp-carousel>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * SHORTCODE [chi_tiet_bds]
     *
     */
    public function chi_tiet_bds($atts) {
        $atts = shortcode_atts([ 'title' => 'Thông số chi tiết', 'gia' => 'Thỏa thuận', 'dientich'  => 'N/A', 'phongngu'  => 'N/A', 'phongtam'  => 'N/A', 'huong'     => 'N/A', 'phaply'    => 'Sổ hồng', 'url' => '', 'price' => '0', 'price_unit' => 'Tỷ', 'street_address' => '', 'address_locality' => '', 'address_region' => ''], $atts, 'chi_tiet_bds');
        if ( floatval($atts['price']) > 0 ) {
            $post_id = get_the_ID();
            $price_value = floatval($atts['price']) * ($atts['price_unit'] === 'Tỷ' ? 1000000000 : 1000000);
            $GLOBALS['page_specific_schema'] = [ '@context' => 'https://schema.org', '@type' => 'RealEstateListing', 'name' => get_the_title($post_id), 'url' => get_permalink($post_id), 'description' => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words( get_post_field('post_content', $post_id), 55 ), 'image' => get_the_post_thumbnail_url($post_id, 'large'), 'floorSize' => ['@type' => 'QuantitativeValue', 'value' => floatval($atts['dientich']), 'unitCode' => 'MTK'], 'numberOfBedrooms' => intval($atts['phongngu']), 'numberOfBathroomsTotal' => intval($atts['phongtam']), 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $atts['street_address'], 'addressLocality' => $atts['address_locality'], 'addressRegion' => $atts['address_region'], 'addressCountry' => 'VN'], 'offers' => ['@type' => 'Offer', 'price' => $price_value, 'priceCurrency' => 'VND'] ];
        }
        ob_start();
        ?>
        <div class="bds-details-box">
            <h3 class="bds-details-title"><?php echo esc_html($atts['title']); ?></h3>
            <div class="bds-details-grid">
                <div class="bds-detail-item"><span class="bds-detail-label">Mức giá</span><span class="bds-detail-value price"><?php echo esc_html($atts['gia']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Diện tích</span><span class="bds-detail-value"><?php echo esc_html($atts['dientich']); ?> m²</span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Phòng ngủ</span><span class="bds-detail-value"><?php echo esc_html($atts['phongngu']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Phòng tắm</span><span class="bds-detail-value"><?php echo esc_html($atts['phongtam']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Hướng nhà</span><span class="bds-detail-value"><?php echo esc_html($atts['huong']); ?></span></div>
                <div class="bds-detail-item"><span class="bds-detail-label">Pháp lý</span><span class="bds-detail-value"><?php echo esc_html($atts['phaply']); ?></span></div>
            </div>
            <?php if ( ! empty( $atts['url'] ) ) : ?>
            <div class="bds-details-cta"><a href="<?php echo esc_url( $atts['url'] ); ?>" class="bds-details-button">Xem Chi Tiết & Hình Ảnh</a></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * SHORTCODE [tinh_lai_suat]
     * [FIX V32] - Giải pháp cuối cùng (Chuẩn AMP).
     * 1. Bỏ uniqid() để giữ nội dung script TĨNH (để hash không đổi).
     * 2. Dùng ID tĩnh cho <script> ("calc_mortgage").
     * 3. Dùng ID tĩnh cho các input ("loanAmount", "loanTerm"...).
     * 4. Dùng JS tương đối (closest/querySelector) để các shortcode
     * không xung đột nhau.
     */
    public function tinh_lai_suat() {
        // [FIX V32] Dùng ID tĩnh.
        $static_script_id = 'calc_mortgage'; 
        ob_start(); ?>
        
        <div class="mortgage-calculator">
            <h3 class="calculator-title">Ước tính khoản vay mua nhà</h3>
            
            <?php // --- CẤU TRÚC V32 (ĐÃ CHUẨN) --- ?>
            
            <amp-script script="<?php echo esc_attr($static_script_id); ?>" layout="container">
                
                <form method="GET" action="#" target="_top">
                    <div class="form-row"><label for="loanAmount">Số tiền vay (triệu VNĐ)</label><input type="number" id="loanAmount" placeholder="Ví dụ: 800" required></div>
                    <div class="form-row"><label for="loanTerm">Thời hạn vay (năm)</label><input type="number" id="loanTerm" value="20" required></div>
                    <div class="form-row"><label for="interestRate">Lãi suất (%/năm)</label><input type="number" step="0.1" id="interestRate" value="7.5" required></div>
                </form>

                <div class="calculator-result">
                    <h4>Số tiền trả hàng tháng (ước tính):</h4>
                    <div class="monthly-payment"><span id="result-display">0 ₫</span></div>
                </div>

            </amp-script>

            <script id="<?php echo esc_attr($static_script_id); ?>" type="text/plain" target="amp-script">
                
                // [FIX V32] Dùng JavaScript tương đối (relative)
                
                // 'this' trong amp-script là thẻ <amp-script>
                const ampScriptTag = this; 
                
                // 1. Tìm .mortgage-calculator cha gần nhất
                const calculatorDiv = ampScriptTag.closest('.mortgage-calculator');

                // 2. Tìm các input BÊN TRONG div cha đó
                const loanAmountInput = calculatorDiv.querySelector("#loanAmount");
                const loanTermInput = calculatorDiv.querySelector("#loanTerm");
                const interestRateInput = calculatorDiv.querySelector("#interestRate");
                const resultDisplay = calculatorDiv.querySelector("#result-display");

                function calculateAndDisplay(){
                    const t=parseFloat(loanAmountInput.value)*1e6||0,e=parseFloat(interestRateInput.value)/1200||0,n=parseInt(loanTermInput.value)*12||0;
                    let l=0;
                    t>0&&e>0&&n>0&&(l=t*e*Math.pow(1+e,n)/(Math.pow(1+e,n)-1));
                    resultDisplay.textContent=l.toLocaleString("vi-VN",{style:"currency",currency:"VND",minimumFractionDigits:0});
                    
                    if(calculatorDiv){
                        l>0?calculatorDiv.classList.add("calculated"):calculatorDiv.classList.remove("calculated");
                    }
                }
                
                loanAmountInput.addEventListener("input",calculateAndDisplay);
                loanTermInput.addEventListener("input",calculateAndDisplay);
                interestRateInput.addEventListener("input",calculateAndDisplay);
            </script>
            
            <?php // --- KẾT THÚC CẤU TRÚC V32 --- ?>

        </div>
        
        <?php return ob_get_clean();
    }


    /**
     * SHORTCODE [bds_noibat]
     *
     */
    public function bds_noibat($atts) {
        $atts = shortcode_atts(['ids' => '', 'title' => 'Các bất động sản nổi bật'], $atts, 'bds_noibat');
        if (empty($atts['ids'])) return '';
        $ids = array_map('intval', explode(',', $atts['ids']));
        $query_args = ['post_type' => 'post', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => -1, 'ignore_sticky_posts' => 1];
        $query = new WP_Query($query_args);
        if (!$query->have_posts()) return '';
        ob_start();
        if ( ! empty( $atts['title'] ) ) {
            echo '<div class="featured-properties-wrapper"><h2 class="featured-properties-title">' . esc_html($atts['title']) . '</h2>';
        }
        echo '<div class="posts-grid-container">';
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('template-parts/content-card');
        }
        echo '</div>';
        if ( ! empty( $atts['title'] ) ) echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * SHORTCODES [tien_ich_xung_quanh] & [tien_ich]
     *
     */
    public function tien_ich_wrapper($atts, $content = null) {
        // [FIX V28] Thêm bộ chuẩn hóa dấu nháy cong, giống hệt [schema_howto]
        $curly_quotes = ['“', '”', '‘', '’'];
        $straight_quotes = ['"', '"', "'", "'"];
        $content = str_replace( $curly_quotes, $straight_quotes, $content );
        
        $content = str_replace( ['<p>', '</p>', '<br />', '<br>'], '', $content );
        $output = do_shortcode($content);
        return '<div class="utilities-accordion-container"><div class="utilities-scroller"><amp-accordion>' . $output . '</amp-accordion></div></div>';
    }
    public function tien_ich_item($atts, $content = null) {
        // [FIX V28] Dùng $atts đơn giản, vì hàm cha đã chuẩn hóa
        $atts = shortcode_atts(['title' => 'Tiện ích', 'icon' => 'default'], $atts);
        
        $content = str_replace(['<p><ul>', '</ul></p>'], ['<ul>', '</ul>'], $content);
        ob_start(); ?>
        <section>
            <h4 class="utility-question"><span class="utility-icon icon-<?php echo esc_attr($atts['icon']); ?>"></span><?php echo esc_html($atts['title']); ?></h4>
            <div class="utility-answer"><?php echo do_shortcode($content); ?></div>
        </section>
        <?php return ob_get_clean();
    }

    /**
     * SHORTCODES [geo_display] & [geo_option]
     *
     */
    public function geo_display( $atts, $content = null ) {
        global $geo_options;
        $geo_options = [];
        do_shortcode( $content );
        if (!isset($geo_options['default'])) return 'Lỗi: Shortcode geo_display thiếu geo_option code="default".';
        $output = '<span class="geo-text-wrapper">';
        foreach ( $geo_options as $code => $text ) {
            $output .= '<span class="geo-text-for-' . esc_attr($code) . '">' . esc_html($text) . '</span>';
        }
        $output .= '</span>';
        return $output;
    }
    public function geo_option( $atts ) {
        global $geo_options;
        $atts = shortcode_atts( ['code' => '', 'text' => ''], $atts );
        if ( ! empty( $atts['code'] ) ) $geo_options[ $atts['code'] ] = $atts['text'];
        return '';
    }

    /**
     * SHORTCODE [amp_product]
     *
     */
    public function amp_product($atts) {
        $post_id = get_the_ID();
        $atts = shortcode_atts([
            'url' => '', 'name' => get_the_title($post_id), 'image_id' => get_post_thumbnail_id($post_id),
            'price' => '0', 'currency' => 'VND', 'availability' => 'InStock', 'brand' => get_bloginfo('name'),
            'sku' => $post_id, 'description' => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(get_post_field('post_content', $post_id), 30, '...'),
            'rating_value' => '', 'rating_count' => ''
        ], $atts, 'amp_product');

        $product_url = !empty($atts['url']) ? $atts['url'] : get_permalink($post_id);
        $rating_value = !empty($atts['rating_value']) ? $atts['rating_value'] : 5.0;
        $rating_count = !empty($atts['rating_count']) ? $atts['rating_count'] : get_post_meta($post_id, '_post_view_count', true);
        if (empty($rating_count)) $rating_count = rand(5, 25);
        $image_data = wp_get_attachment_image_src($atts['image_id'], 'large');
        $image_url = $image_data ? $image_data[0] : 'https://placehold.co/600x600/f0f4f8/48525c?text=Product+Image';

        ob_start();
        ?>
        <a href="<?php echo esc_url($product_url); ?>" class="amp-product-box">
            <div class="amp-product-image-wrapper"><amp-img src="<?php echo esc_url($image_url); ?>" layout="responsive" width="600" height="600" alt="<?php echo esc_attr($atts['name']); ?>"></amp-img></div>
            <div class="amp-product-content">
                <h3 class="amp-product-title"><?php echo esc_html($atts['name']); ?></h3>
                <?php if (!empty($atts['brand'])): ?><div class="amp-product-brand">Thương hiệu: <strong><?php echo esc_html($atts['brand']); ?></strong></div><?php endif; ?>
                <div class="amp-product-price"><?php echo esc_html(number_format_i18n($atts['price'])); ?> <?php echo esc_html($atts['currency']); ?></div>
                <div class="amp-product-description"><p><?php echo esc_html($atts['description']); ?></p></div>
                <?php if ($rating_value > 0 && $rating_count > 0) : $percentage = ($rating_value / 5) * 100; ?>
                <div class="rating-box">
                    <div class="star-rating" title="Đánh giá: <?php echo esc_attr($rating_value); ?> trên 5 sao">
                        <div class="star-rating-background"><?php for ($i = 0; $i < 5; $i++) echo '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?></div>
                        <div class="star-rating-foreground" style="width: <?php echo esc_attr($percentage); ?>%;"><?php for ($i = 0; $i < 5; $i++) echo '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'; ?></div>
                    </div>
                    <div class="rating-text"><strong><?php echo esc_html($rating_value); ?></strong>/5 (<?php echo esc_html( number_format_i18n($rating_count) ); ?> đánh giá)</div>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php

        $schema = [
            '@context' => 'https://schema.org/', '@type' => 'Product', 'name' => $atts['name'], 'image' => $image_url,
            'description' => $atts['description'], 'sku' => $atts['sku'], 'brand' => ['@type' => 'Brand', 'name' => $atts['brand']],
            'offers' => [
                '@type' => 'Offer', 'url' => $product_url, 'priceCurrency' => $atts['currency'], 'price' => $atts['price'],
                'availability' => 'https://schema.org/' . $atts['availability'], 'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
                'deliveryMethod' => 'https://schema.org/OnlineService',
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy', 'applicableCountry' => 'VN', 'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays' => 7, 'returnMethod' => 'https://schema.org/ReturnByMail', 'returnFees' => 'https://schema.org/FreeReturn'
                ],
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails', 'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => '0', 'currency' => 'VND'],
                    'deliveryTime' => [
                        '@type' => 'ShippingDeliveryTime',
                        'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY'],
                        'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 0, 'unitCode' => 'DAY']
                    ],
                    'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'VN']
                ]
            ]
        ];
        if ($rating_value > 0 && $rating_count > 0) {
            $schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $rating_value, 'reviewCount' => $rating_count];
        }
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        return ob_get_clean();
    }

    /**
     * SHORTCODE [quang_cao_noi_bo]
     *
     */
    public function internal_ad($atts) {
        $atts = shortcode_atts(['id' => ''], $atts, 'quang_cao_noi_bo');
        if (empty($atts['id'])) return '';
        $post_ids = array_map('intval', explode(',', $atts['id']));
        $output = '';
        foreach ($post_ids as $post_id) {
            $ad_post = get_post($post_id);
            if (!$ad_post) continue;
            $ad_title = get_the_title($ad_post);
            $ad_permalink = get_permalink($ad_post);
            $ad_thumbnail_url = get_the_post_thumbnail_url($ad_post, 'thumbnail') ?: 'https://placehold.co/100x100/f0f4f8/48525c?text=IMG';
            $ad_excerpt = has_excerpt($ad_post) ? get_the_excerpt($ad_post) : wp_trim_words(strip_shortcodes($ad_post->post_content), 20, '...');
            $output .= '<a href="' . esc_url($ad_permalink) . '" class="internal-ad-v2-container"><div class="internal-ad-v2-body"><div class="internal-ad-v2-image"><amp-img src="' . esc_url($ad_thumbnail_url) . '" width="100" height="100" layout="responsive" alt="' . esc_attr($ad_title) . '"></amp-img></div><div class="internal-ad-v2-content"><h4 class="ad-title">' . esc_html($ad_title) . '</h4><p class="ad-excerpt">' . esc_html($ad_excerpt) . '</p><span class="ad-cta">Xem ngay →</span></div></div><div class="internal-ad-v2-label">Đề xuất</div></a>';
        }
        return (count($post_ids) > 1) ? '<div class="internal-ad-group">' . $output . '</div>' : $output;
    }

    /**
     * Tự động chèn [quang_cao_noi_bo] vào nội dung
     *
     */
    public function auto_inject_internal_ad($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            $latest_posts = get_posts(['numberposts' => 1, 'post_status' => 'publish', 'post__not_in' => [get_the_ID()]]);
            if (!empty($latest_posts)) {
                // Gọi method 'internal_ad' của class này
                $ad_code = $this->internal_ad(['id' => $latest_posts[0]->ID]);
                $paragraphs = explode('</p>', $content);
                if (count($paragraphs) > 3) {
                    array_splice($paragraphs, 3, 0, $ad_code);
                    $content = implode('</p>', $paragraphs);
                }
            }
        }
        return $content;
    }

    /**
     * SHORTCODE [dang_ky_sdt]
     *
     */
    public function phone_registration($atts) {
        $args = shortcode_atts(['tieu_de' => 'Để lại số điện thoại, chúng tôi sẽ gọi lại ngay!', 'nut_gui' => 'Yêu Cầu Gọi Lại'], $atts);
        
        // Hàm get_amp_phone_only_form_html() nằm trong 'inc/integrations-module.php'
        if (function_exists('get_amp_phone_only_form_html')) {
            return get_amp_phone_only_form_html($args);
        }
        return '<div class="shortcode-error">[LỖI: Hàm get_amp_phone_only_form_html() không tồn tại]</div>';
    }

    /**
     * SHORTCODE [amp_imagemap]
     * [TỐI ƯU V6] Sửa HTML để hiển thị tiêu đề hotspot cố định.
     */
    public function amp_imagemap($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $map_id = intval($atts['id']);
        if ($map_id <= 0 || get_post_type($map_id) !== 'image_map') {
            return '<div class="shortcode-error">Lỗi [amp_imagemap]: ID không hợp lệ.</div>';
        }

        $image_id = get_post_meta($map_id, '_im_image_id', true);
        $raw_data = get_post_meta($map_id, '_im_hotspot_data', true);
        $mode = get_post_meta($map_id, '_im_mode', true) ?: 'url';
        $hotspot_size = get_post_meta($map_id, '_im_hotspot_size', true) ?: '25px';
        $lightbox_id = sanitize_title_with_dashes('map-lightbox-' . $map_id);

        $image_url = wp_get_attachment_url($image_id);
        $image_meta = wp_get_attachment_metadata($image_id);
        $width = $image_meta['width'] ?? 1000;
        $height = $image_meta['height'] ?? 500;
        $alt = get_the_title($image_id) ?: 'Image Map ' . $map_id;

        if (empty($raw_data) || empty($image_url)) {
            return '<div class="shortcode-error">Lỗi [amp_imagemap]: Image Map chưa được cấu hình đầy đủ.</div>';
        }
        
        preg_match_all('/^(.+?):\s*left:\s*([\d.]+)\%;\s*top:\s*([\d.]+)\%;/im', $raw_data, $css_matches, PREG_SET_ORDER);
        preg_match_all( '/\[hotspot\s+name=[\"“”](.*?)[\"“”]\s+url=[\"“”](.*?)[\"“”]\s*\](.*?)\[\/hotspot\]/s', $raw_data, $hotspot_content_matches, PREG_SET_ORDER );
        
        $content_map = [];
        foreach ($hotspot_content_matches as $m) {
            $content_map[trim($m[1])] = [ 'url' => esc_url($m[2]), 'content' => trim($m[3]) ];
        }
        
        $hotspot_data = [];
        $number_counter = 1;
        foreach ($css_matches as $m) {
            $unit_name = trim($m[1]); 
            $details = $content_map[$unit_name] ?? ['url' => '#', 'content' => 'Nội dung chưa được định nghĩa.'];
            $hotspot_data[] = [ 'name' => $unit_name, 'number' => $number_counter++, 'left' => floatval($m[2]), 'top' => floatval($m[3]), 'url' => $details['url'], 'content' => $details['content'] ];
        }
        if (empty($hotspot_data)) return '';

        ob_start();
        ?>
        <div class="amp-css-imagemap-wrapper" data-map-id="<?php echo $map_id; ?>" style="position: relative;">
            <amp-img src="<?php echo $image_url; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" layout="responsive" alt="<?php echo esc_attr($alt); ?>"></amp-img>
            
            <?php // --- BẮT ĐẦU SỬA LỖI V6 --- ?>
            <?php foreach ($hotspot_data as $data) : 
                $number = esc_html($data['number']);
                $safe_unit_id = sanitize_title($data['name']);
                $extra_attrs = ''; $tag = 'a'; 
                if ($mode === 'popup') {
                    $tag = 'button';
                    $allowed_html = ['h1'=>[], 'h2'=>[], 'h3'=>[], 'h4'=>[], 'h5'=>[], 'h6'=>[], 'p'=>['style'=>[]], 'br'=>[], 'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'ul'=>[], 'ol'=>[], 'li'=>[], 'a'=>['href'=>[], 'title'=>[], 'target'=>[]], 'img'=>['src'=>[], 'alt'=>[], 'width'=>[], 'height'=>[], 'style'=>[]]];
                    $safe_content = wp_kses($data['content'], $allowed_html);
                    $hotspot_state_data = ['title' => $data['name'], 'content_text' => wp_strip_all_tags( $safe_content ), 'link' => esc_url( $data['url'] )];
                    $json_data_string = json_encode($hotspot_state_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $amp_action = "tap:{$lightbox_id}.open, AMP.setState({ currentHotspot: {$json_data_string} })";
                    $extra_attrs = "on='" . htmlspecialchars($amp_action, ENT_QUOTES, 'UTF-8') . "'";
                } else {
                    $extra_attrs = 'href="' . esc_url($data['url']) . '"';
                }
            ?>
                
                <div class="css-hotspot-wrapper" style="position: absolute; left: <?php echo $data['left']; ?>%; top: <?php echo $data['top']; ?>%; transform: translate(-50%, -50%);">
                    
                    <<?php echo $tag; ?> <?php echo $extra_attrs; ?> class="css-hotspot hotspot-on-image" id="hotspot-image-<?php echo esc_attr($safe_unit_id); ?>" style="width: <?php echo esc_attr($hotspot_size); ?>; height: <?php echo esc_attr($hotspot_size); ?>;" title="<?php echo esc_attr($data['name']); // Giữ title cho accessibility ?>">
                       <?php echo $number; ?>
                    </<?php echo $tag; ?>>
                    
                    <span class="hotspot-title-display"><?php echo esc_html($data['name']); ?></span>
                </div>

            <?php endforeach; ?>
            <?php // --- KẾT THÚC SỬA LỖI V6 --- ?>
        </div>
        
        <div class="amp-css-hotspot-list-wrapper">
            <h4 class="hotspot-list-title">Danh sách:</h4>
            <ul class="hotspot-list-ui">
                <?php foreach ($hotspot_data as $data) : 
                    $extra_attrs = ''; $tag = 'a';
                    if ($mode === 'popup') {
                        $tag = 'button';
                        $allowed_html = ['h1'=>[], 'h2'=>[], 'h3'=>[], 'h4'=>[], 'h5'=>[], 'h6'=>[], 'p'=>['style'=>[]], 'br'=>[], 'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'ul'=>[], 'ol'=>[], 'li'=>[], 'a'=>['href'=>[], 'title'=>[], 'target'=>[]], 'img'=>['src'=>[], 'alt'=>[], 'width'=>[], 'height'=>[], 'style'=>[]]];
                        $safe_content = wp_kses($data['content'], $allowed_html);
                        $hotspot_state_data = ['title' => $data['name'], 'content_text' => wp_strip_all_tags( $safe_content ), 'link' => esc_url( $data['url'] )];
                        $json_data_string = json_encode($hotspot_state_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $amp_action = "tap:{$lightbox_id}.open, AMP.setState({ currentHotspot: {$json_data_string} })";
                        $extra_attrs = "on='" . htmlspecialchars($amp_action, ENT_QUOTES, 'UTF-8') . "'";
                    } else {
                        $extra_attrs = 'href="' . esc_url($data['url']) . '"';
                    }
                    $display_name = $data['name'];
                    $safe_unit_id = sanitize_title($data['name']);
                ?>
                    <li>
                        <<?php echo $tag; ?> <?php echo $extra_attrs; ?> class="css-hotspot hotspot-list-button" id="hotspot-list-<?php echo esc_attr($safe_unit_id); ?>"><?php echo esc_html($display_name); ?></<?php echo $tag; ?>>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php if ($mode === 'popup') : ?>
            <amp-state id="currentHotspot"><script type="application/json">{"title": "Thông tin", "content_text": "Vui lòng chọn một điểm trên bản đồ hoặc trong danh sách.", "link": "#"}</script></amp-state>
            <amp-lightbox id="<?php echo $lightbox_id; ?>" layout="nodisplay" class="amp-lightbox-map">
                <div class="lightbox-content-wrapper" role="button" tabindex="0" on="tap:<?php echo $lightbox_id; ?>.close, toc-overlay.hide">
                    <div class="lightbox-content" role="dialog" aria-labelledby="hotspot-title" on="tap:AMP.noop" tabindex="0">
                        <h3 id="hotspot-title" [text]="currentHotspot.title"></h3>
                        <div [text]="currentHotspot.content_text"></div>
                        <div class="lightbox-buttons">
                            <a [href]="currentHotspot.link" class="lightbox-cta-button">Xem Chi Tiết →</a>
                            <button on="tap:<?php echo $lightbox_id; ?>.close" class="lightbox-close-button">Đóng</button>
                        </div>
                    </div>
                </div>
            </amp-lightbox>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * SHORTCODE [amp_event_bar]
     * (Đã sửa lỗi "low trust")
     */
    public function amp_event_bar($atts) {
        $args = ['post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => -1];
        $event_query = new WP_Query($args);
        if (!$event_query->have_posts()) { return ''; }

        $schema_items = [];
        ob_start();
        ?>
        <div id="amp-event-bar" class="event-carousel-wrapper">
            <amp-carousel id="eventCarousel" layout="fill" type="slides" autoplay delay="5000" loop>
                <?php 
                while ($event_query->have_posts()) : $event_query->the_post(); 
                    $post_id = get_the_ID();
                    $meta = get_post_meta($post_id);
                    $event_title = get_the_title();
                    $event_url = $meta['_event_url'][0] ?? '#';
                    $description = $meta['_event_description'][0] ?? '';
                    $icon = $meta['_event_icon'][0] ?? '🚀';
                    
                    // --- Schema Data Logic ---
                    $event_schema = ['@type' => 'Event', 'name' => $event_title];
                    if (!empty($meta['_event_start_date'][0])) { try { $dt_start = new DateTime($meta['_event_start_date'][0], new DateTimeZone('Asia/Ho_Chi_Minh')); $event_schema['startDate'] = $dt_start->format(DateTime::ATOM); } catch (Exception $e) {} }
                    if (!empty($meta['_event_end_date'][0])) { try { $dt_end = new DateTime($meta['_event_end_date'][0], new DateTimeZone('Asia/Ho_Chi_Minh')); $event_schema['endDate'] = $dt_end->format(DateTime::ATOM); } catch (Exception $e) {} }
                    if (!empty($meta['_event_image_id'][0])) { $event_schema['image'] = wp_get_attachment_url($meta['_event_image_id'][0]); }
                    $event_schema['eventStatus'] = 'https://schema.org/EventScheduled'; $event_schema['description'] = $description; $event_schema['url'] = $event_url;
                    $location_type = $meta['_event_location_type'][0] ?? 'virtual';
                    if ($location_type === 'virtual') { $event_schema['location'] = ['@type' => 'VirtualLocation', 'url' => $event_url]; } else { $event_schema['location'] = ['@type' => 'Place', 'name' => $meta['_event_location_name'][0] ?? '', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $meta['_event_location_address'][0] ?? '']]; }
                    $organizer_name = !empty($meta['_event_organizer_name'][0]) ? $meta['_event_organizer_name'][0] : get_bloginfo('name');
                    $event_schema['organizer'] = ['@type' => 'Organization', 'name' => $organizer_name, 'url' => home_url('/')];
                    if (!empty($meta['_event_performer_name'][0])) { $event_schema['performer'] = ['@type' => 'Person', 'name' => $meta['_event_performer_name'][0]]; }
                    $offer_schema = ['@type' => 'Offer', 'price' => $meta['_event_price'][0] ?? '0', 'priceCurrency' => $meta['_event_currency'][0] ?? 'VND', 'url' => $event_url, 'availability' => $meta['_event_offer_availability'][0] ?? 'https://schema.org/InStock'];
                    if (!empty($meta['_event_offer_valid_from'][0])) { $offer_schema['validFrom'] = $meta['_event_offer_valid_from'][0]; }
                    $event_schema['offers'] = $offer_schema;
                    $schema_items[] = array_filter($event_schema);
                    // --- End Schema Data Logic ---
                ?>
                    <div class="event-slide">
                        <div role="link" tabindex="0" class="event-notification-link" on="tap:AMP.navigateTo(url='<?php echo esc_url($event_url); ?>')">
                            <div class="sonar-icon-wrap"><span class="event-status-icon"><?php echo esc_html($icon); ?></span><span class="sonar-pulse"></span></div>
                            <p class="event-description-text"><strong><?php echo esc_html($event_title); ?>:</strong> <?php echo esc_html($description); ?></p>
                        </div>
                    </div>
                <?php 
                endwhile; 
                wp_reset_postdata();
                ?>
            </amp-carousel>
        </div>
        <?php
        wp_reset_postdata();
        $schema_output = '';
        if (!empty($schema_items)) {
            $full_schema = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Danh Sách Sự Kiện Nổi Bật', 'itemListElement' => $schema_items];
            $schema_output = '<script type="application/ld+json">' . json_encode($full_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
        }
        return ob_get_clean() . $schema_output;
    }

    /**
     * [SỬA LỖI A/B TEST V8 - FINAL] SHORTCODE CON
     * Mô phỏng logic dọn dẹp <p> từ [schema_howto_step]
     */
    public function ab_test_variant($atts, $content = null) {
        $atts = shortcode_atts(['variant' => ''], $atts, 'ab_test_variant');
        if (empty($atts['variant'])) return ''; // Lỗi, trả về rỗng

        // 1. [FIX V8] Dọn dẹp <p> BÊN TRONG shortcode con
        // (ví dụ: <p>[form_dang_ky]</p>)
        // Đây là code mô phỏng theo [schema_howto_step]
        $content_clean = str_replace( ['<p>', '</p>', '<br />', '<br>'], ['', '', "\n", "\n"], $content );
        
        // 2. [FIX V8 - THAM KHẢO HOWTO] 
        // Xử lý dấu nháy cong (wptexturize) một lần nữa
        // để đảm bảo shortcode lồng nhau (như [form_dang_ky]) không bị lỗi
        $curly_quotes = ['“', '”', '‘', '’'];
        $straight_quotes = ['"', '"', "'", "'"];
        $content_clean = str_replace( $curly_quotes, $straight_quotes, $content_clean );

        // 3. Xử lý shortcode lồng bên trong (ví dụ [form_dang_ky])
        $processed_content = do_shortcode($content_clean);
        
        // 4. Trả về HTML <div option="..."> hợp lệ của AMP
        return '<div option="' . esc_attr($atts['variant']) . '">' . $processed_content . '</div>';
    }


} // Kết thúc Class