<?php
/**
 * inc/qapage/class-qapage-cpt.php
 *
 * Đăng ký Custom Post Type "Câu hỏi" (qapage_question).
 * Đây là nền tảng cho toàn bộ module QAPage.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_CPT {

    /**
     * Khởi tạo class, đăng ký hook 'init'.
     */
    public function __construct() {
        // Đăng ký CPT với priority 10 (mặc định)
        add_action( 'init', [ $this, 'register_cpt' ] );
    }

    /**
     * Hàm đăng ký Custom Post Type.
     */
    public function register_cpt() {
        
        $labels = [
            'name'                  => _x( 'Hỏi & Đáp', 'Post Type General Name', 'tuancele-amp' ),
            'singular_name'         => _x( 'Câu hỏi', 'Post Type Singular Name', 'tuancele-amp' ),
            'menu_name'             => __( 'Hỏi & Đáp (Q&A)', 'tuancele-amp' ),
            'name_admin_bar'        => __( 'Câu hỏi Q&A', 'tuancele-amp' ),
            'archives'              => __( 'Kho lưu trữ Câu hỏi', 'tuancele-amp' ),
            'attributes'            => __( 'Thuộc tính Câu hỏi', 'tuancele-amp' ),
            'parent_item_colon'     => __( 'Câu hỏi cha:', 'tuancele-amp' ),
            'all_items'             => __( 'Tất cả Câu hỏi', 'tuancele-amp' ),
            'add_new_item'          => __( 'Đặt câu hỏi mới', 'tuancele-amp' ),
            'add_new'               => __( 'Đặt mới', 'tuancele-amp' ),
            'new_item'              => __( 'Câu hỏi mới', 'tuancele-amp' ),
            'edit_item'             => __( 'Chỉnh sửa Câu hỏi', 'tuancele-amp' ),
            'update_item'           => __( 'Cập nhật Câu hỏi', 'tuancele-amp' ),
            'view_item'             => __( 'Xem Câu hỏi', 'tuancele-amp' ),
            'view_items'            => __( 'Xem các Câu hỏi', 'tuancele-amp' ),
            'search_items'          => __( 'Tìm kiếm Câu hỏi', 'tuancele-amp' ),
            'not_found'             => __( 'Không tìm thấy câu hỏi.', 'tuancele-amp' ),
            'not_found_in_trash'    => __( 'Không tìm thấy trong thùng rác.', 'tuancele-amp' ),
        ];
        
        $args = [
            'label'                 => __( 'Câu hỏi', 'tuancele-amp' ),
            'description'           => __( 'Module Hỏi & Đáp (QAPage) độc lập.', 'tuancele-amp' ),
            'labels'                => $labels,
            'supports'              => [ 'title', 'editor', 'author', 'comments', 'thumbnail' ], // BẮT BUỘC: Hỗ trợ 'author' và 'comments'
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20, // Nằm bên dưới 'Pages' (Trang)
            'menu_icon'             => 'dashicons-format-chat', // Icon phù hợp
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'qapage', // slug archive: domain.com/qapage/
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Kích hoạt REST API (hữu ích về sau)
            'rewrite'               => [ 
                'slug' => 'qapage', // slug bài viết: domain.com/qapage/tieu-de-cau-hoi/
                'with_front' => false 
            ],
        ];
        
        register_post_type( 'qapage_question', $args );
    }
}