<?php
/**
 * 404.php
 * Template cho trang báo lỗi 404 (Không tìm thấy trang).
 * Cung cấp thông báo rõ ràng và các lựa chọn điều hướng hữu ích cho người dùng.
 */

get_header();
?>

<div class="container error-404-container">

    <div class="error-404-code">404</div>

    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'Oops! Không tìm thấy trang', 'amp-custom-theme' ); ?></h1>
    </header>

    <div class="page-content">
        <p><?php esc_html_e( 'Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc không bao giờ tồn tại.', 'amp-custom-theme' ); ?></p>
        
        <?php // Tạo một form tìm kiếm đơn giản, tuân thủ AMP ?>
        <form role="search" method="get" class="error-404-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" target="_top">
            <label>
                <span class="screen-reader-text"><?php esc_html_e( 'Tìm kiếm:', 'amp-custom-theme' ); ?></span>
                <input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Thử tìm kiếm nội dung...', 'amp-custom-theme' ); ?>" value="" name="s" />
            </label>
            <input type="submit" class="search-submit" value="<?php esc_attr_e( 'Tìm kiếm', 'amp-custom-theme' ); ?>" />
        </form>
        
        <p><?php esc_html_e( 'Hoặc bạn có thể quay trở lại trang chủ.', 'amp-custom-theme' ); ?></p>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button-back-home">
            Về Trang Chủ
        </a>
    </div>

</div>

<?php
get_footer();