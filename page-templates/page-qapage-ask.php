<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // Thêm dòng này ?>
<?php
/**
 * Template Name: QAPage - Trang Đặt Câu Hỏi
 *
 * Template tùy chỉnh cho trang "Đặt Câu Hỏi Mới".
 * - Hiển thị form đăng câu hỏi (cho user đã đăng nhập).
 * - Hiển thị form Đăng ký + Đặt câu hỏi (cho khách).
 *
 * [UPDATE] Thêm vòng lặp WP_Query để hiển thị các câu hỏi gần đây
 * ngay bên dưới form.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

// Lấy cài đặt reCaptcha (để biết có bật hay không)
$recaptcha_options = get_option( 'tuancele_recaptcha_settings', [] );
$is_recaptcha_enabled = isset( $recaptcha_options['enable_recaptcha'] ) && $recaptcha_options['enable_recaptcha'] === 'on';
$recaptcha_site_key = $recaptcha_options['recaptcha_v3_site_key'] ?? '';
$show_recaptcha = $is_recaptcha_enabled && ! empty( $recaptcha_site_key );

// Lấy URL bài viết/page hiện tại (nếu có tham số) để làm context
$context_url = isset( $_GET['context_url'] ) ? esc_url( $_GET['context_url'] ) : '';
$context_post_id = $context_url ? url_to_postid( $context_url ) : 0;
?>

<header class="page-header">
    <h1 class="page-title"><?php the_title(); ?></h1>
</header>

<?php
if ( function_exists( 'tuancele_amp_display_breadcrumbs' ) ) {
    tuancele_amp_display_breadcrumbs();
}
?>

<div class="qapage-ask-container">
    <article id="post-<?php the_ID(); ?>">
        <div class="content">
            <?php 
            // Hiển thị nội dung của trang (ví dụ: hướng dẫn, quy định đặt câu hỏi)
            if ( have_posts() ) : while ( have_posts() ) : the_post();
                the_content();
            endwhile; endif; 
            ?>
        </div>
    </article>

    <?php if ( is_user_logged_in() ) : ?>
        
        <form class="qapage-ask-form"
              method="post"
              target="_top"
              action-xhr="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
            
            <input type="hidden" name="action" value="qapage_ask">
            <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce( 'qapage_ask_nonce' ); ?>">
            
            <div class="form-title">Đặt câu hỏi của bạn</div>

            <?php // Hiển thị ngữ cảnh nếu có ?>
            <?php if ( $context_post_id > 0 ) : ?>
                <div class="form-row qapage-context-notice">
                    <label>Liên quan đến bài viết:</label>
                    <strong><?php echo esc_html( get_the_title( $context_post_id ) ); ?></strong>
                    <input type="hidden" name="context_url" value="<?php echo esc_url( $context_url ); ?>">
                </div>
            <?php endif; ?>

            <div class="form-row">
                <label for="q_title">Tiêu đề câu hỏi <span style="color:red;">*</span></label>
                <input id="q_title" type="text" name="question_title" required placeholder="Ví dụ: Làm cách nào để...">
                <div visible-when-invalid="valueMissing" validation-for="q_title" class="validation-error">Vui lòng nhập tiêu đề.</div>
            </div>

            <div class="form-row">
                <label for="q_content">Nội dung chi tiết <span style="color:red;">*</span></label>
                <textarea id="q_content" name="question_content" required placeholder="Mô tả chi tiết vấn đề của bạn..."></textarea>
                <div visible-when-invalid="valueMissing" validation-for="q_content" class="validation-error">Vui lòng nhập nội dung.</div>
            </div>

            <?php // Tích hợp reCaptcha (cho cả user đã đăng nhập) ?>
            <?php if ( $show_recaptcha ) : ?>
                <div class="form-row qapage-recaptcha">
                    <amp-recaptcha-input
                        layout="nodisplay"
                        name="g-recaptcha-response"
                        data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"
                        data-action="qapage_ask_form">
                    </amp-recaptcha-input>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <button type="submit" class="submit-button">
                    <span class="button-text">Gửi câu hỏi</span>
                    <div class="loader"></div>
                </button>
            </div>
            
            <div submit-success><template type="amp-mustache"><div class="form-feedback form-success">Đăng câu hỏi thành công! Đang chuyển hướng...</div></template></div>
            <div submit-error><template type="amp-mustache"><div class="form-feedback form-error">Lỗi: {{message}}</div></template></div>
        </form>

    <?php else : ?>

        <form class="qapage-ask-form"
              method="post"
              target="_top"
              action-xhr="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
            
            <input type="hidden" name="action" value="qapage_register_and_ask">
            <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce( 'qapage_ask_nonce' ); ?>">
            
            <div class="form-title">Đặt câu hỏi (Bạn sẽ tạo tài khoản mới)</div>

            <?php // Hiển thị ngữ cảnh nếu có ?>
            <?php if ( $context_post_id > 0 ) : ?>
                <div class="form-row qapage-context-notice">
                    <label>Liên quan đến bài viết:</label>
                    <strong><?php echo esc_html( get_the_title( $context_post_id ) ); ?></strong>
                    <input type="hidden" name="context_url" value="<?php echo esc_url( $context_url ); ?>">
                </div>
            <?php endif; ?>

            <div class="form-row">
                <label for="q_title_guest">Tiêu đề câu hỏi <span style="color:red;">*</span></label>
                <input id="q_title_guest" type="text" name="question_title" required placeholder="Ví dụ: Làm cách nào để...">
                <div visible-when-invalid="valueMissing" validation-for="q_title_guest" class="validation-error">Vui lòng nhập tiêu đề.</div>
            </div>

            <div class="form-row">
                <label for="q_content_guest">Nội dung chi tiết <span style="color:red;">*</span></label>
                <textarea id="q_content_guest" name="question_content" required placeholder="Mô tả chi tiết vấn đề của bạn..."></textarea>
                <div visible-when-invalid="valueMissing" validation-for="q_content_guest" class="validation-error">Vui lòng nhập nội dung.</div>
            </div>
            
            <hr style="border: 0; border-top: 1px dashed #ccc; margin: 25px 0;">
            <p style="font-weight: 600;">Tạo tài khoản mới (Bắt buộc):</p>

            <div class="form-row">
                <label for="q_name_guest">Tên hiển thị <span style="color:red;">*</span></label>
                <input id="q_name_guest" type="text" name="user_name" required>
                <div visible-when-invalid="valueMissing" validation-for="q_name_guest" class="validation-error">Vui lòng nhập tên.</div>
            </div>

            <div class="form-row">
                <label for="q_email_guest">Email <span style="color:red;">*</span></label>
                <input id="q_email_guest" type="email" name="user_email" required>
                <div visible-when-invalid="valueMissing" validation-for="q_email_guest" class="validation-error">Vui lòng nhập email.</div>
                <div visible-when-invalid="typeMismatch" validation-for="q_email_guest" class="validation-error">Email không hợp lệ.</div>
            </div>

            <div class="form-row">
                <label for="q_pass_guest">Mật khẩu <span style="color:red;">*</span></label>
                <input id="q_pass_guest" type="password" name="user_pass" required minlength="6">
                <div visible-when-invalid="valueMissing" validation-for="q_pass_guest" class="validation-error">Vui lòng nhập mật khẩu.</div>
                <div visible-when-invalid="tooShort" validation-for="q_pass_guest" class="validation-error">Mật khẩu phải có ít nhất 6 ký tự.</div>
            </div>

            <?php // Tích hợp reCaptcha (Bắt buộc cho khách) ?>
            <?php if ( $show_recaptcha ) : ?>
                <div class="form-row qapage-recaptcha">
                    <amp-recaptcha-input
                        layout="nodisplay"
                        name="g-recaptcha-response"
                        data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"
                        data-action="qapage_ask_form">
                    </amp-recaptcha-input>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <button type="submit" class="submit-button">
                    <span class="button-text">Đăng ký & Gửi câu hỏi</span>
                    <div class="loader"></div>
                </button>
            </div>
            
            <div submit-success><template type="amp-mustache"><div class="form-feedback form-success">Đăng ký và gửi câu hỏi thành công! Đang chuyển hướng...</div></template></div>
            <div submit-error><template type="amp-mustache"><div class="form-feedback form-error">Lỗi: {{message}}</div></template></div>
        </form>

    <?php endif; ?>

</div> <?php // Đóng thẻ .qapage-ask-container ?>


<?php 
// =================================================================
// [THÊM MỚI] KHU VỰC HIỂN THỊ CÁC CÂU HỎI GẦN ĐÂY
// =================================================================

// 1. Chuẩn bị Query
$recent_questions_query = new WP_Query([
    'post_type'      => 'qapage_question',
    'posts_per_page' => 3, // Hiển thị 3 câu hỏi mới nhất
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// 2. Kiểm tra và Hiển thị
if ( $recent_questions_query->have_posts() ) : 
?>
    <section class="qapage-recent-list">
        <h2 class="qapage-recent-title">Các câu hỏi gần đây</h2>
        
        <?php // Tái sử dụng layout grid của theme ?>
        <div class="posts-grid-container">
            <?php 
            while ( $recent_questions_query->have_posts() ) : $recent_questions_query->the_post();
                // Tái sử dụng template card
                get_template_part( 'template-parts/content-card' );
            endwhile; 
            ?>
        </div>
    </section>
<?php
endif;

// 3. Reset Query
wp_reset_postdata(); 
?>


<?php
get_footer();
?>