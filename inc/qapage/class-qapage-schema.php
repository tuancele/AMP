<?php
/**
 * inc/qapage/class-qapage-schema.php
 *
 * Tạo Schema QAPage, đồng thời gỡ bỏ Schema BlogPosting/Article.
 * Class này đảm bảo trang Câu hỏi & Trả lời có Schema JSON-LD chính xác.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Schema {

    /**
     * Khởi tạo class, đăng ký hook.
     */
    public function __construct() {
        // Hook 'template_redirect' chạy đủ sớm để cho phép chúng ta gỡ bỏ
        // các hook 'wp_footer' của module khác trước khi chúng được thực thi.
        add_action( 'template_redirect', [ $this, 'init_schema_hooks' ] );
    }

    /**
     * Kiểm tra xem có phải trang QAPage không và thực hiện gỡ/thêm hook.
     */
    public function init_schema_hooks() {
        // Chỉ chạy logic này nếu chúng ta đang ở trong CPT 'qapage_question'
        if ( is_singular( 'qapage_question' ) || is_post_type_archive( 'qapage_question' ) ) {
            
            // 1. Gỡ bỏ hook Schema mặc định của theme
            // Chúng ta cần tìm instance của AMP_SEO_Module để gỡ bỏ.
            $this->remove_default_schema_hooks();

            // 2. Thêm hook Schema QAPage của chúng ta vào wp_footer
            add_action( 'wp_footer', [ $this, 'generate_qapage_schema' ] );
        }
    }

    /**
     * Tìm và gỡ bỏ hàm generate_rich_schema của AMP_SEO_Module.
     * Đây là kỹ thuật "Reflection" an toàn để gỡ bỏ action của một class
     * mà không cần truy cập trực tiếp vào biến global.
     */
    private function remove_default_schema_hooks() {
        global $wp_filter;
        $tag = 'wp_footer';
        $priority = 10; // Priority mặc định của hook 'generate_rich_schema'

        if ( ! isset( $wp_filter[$tag] ) || ! isset( $wp_filter[$tag]->callbacks[$priority] ) ) {
            return;
        }

        foreach ( $wp_filter[$tag]->callbacks[$priority] as $key => $callback_details ) {
            // Kiểm tra xem hook có phải là [ instance_của_AMP_SEO_Module, 'generate_rich_schema' ]
            if ( is_array( $callback_details['function'] ) && 
                 isset( $callback_details['function'][0] ) && 
                 is_a( $callback_details['function'][0], 'AMP_SEO_Module' ) &&
                 $callback_details['function'][1] === 'generate_rich_schema' ) 
            {
                // Đã tìm thấy, gỡ bỏ nó
                remove_action( $tag, $key, $priority );
                break;
            }
        }
    }

    /**
     * Hàm chính tạo Schema QAPage cho trang 'single' và 'archive'.
     */
    public function generate_qapage_schema() {
        global $post;
        $schema_graph = [];

        // --- Logic cho Trang Single (Chi tiết Câu hỏi) ---
        if ( is_singular( 'qapage_question' ) ) {
            
            // 1. Tạo Schema @type: Question (Thực thể chính)
            $question_schema = [
                '@type'             => 'Question',
                '@id'               => get_permalink( $post->ID ) . '#question',
                'name'              => get_the_title( $post->ID ),
                'text'              => wp_strip_all_tags( $post->post_content ),
                'dateCreated'       => get_the_date( 'c', $post->ID ),
                'author'            => [
                    '@type' => 'Person',
                    'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                    'url'   => get_author_posts_url( $post->post_author ), // URL Profile (theo logic "bắt buộc đăng nhập")
                ],
                'answerCount'       => (int) get_comments_number( $post->ID ),
                'suggestedAnswer'   => [],
            ];

            // 2. Lấy tất cả bình luận (Answers & Comments)
            $comments = get_comments( [
                'post_id'   => $post->ID,
                'status'    => 'approve',
                'orderby'   => 'comment_date',
                'order'     => 'ASC',
            ] );

            $accepted_answer_id = get_post_meta( $post->ID, '_qapage_accepted_answer_id', true );
            $answer_schemas = []; // Mảng tạm để lưu các Answer (Cấp 1)

            foreach ( $comments as $comment ) {
                // Chỉ xử lý bình luận CẤP 1 (depth 0) là Answer
                if ( $comment->comment_parent == 0 ) {
                    $vote_score = get_comment_meta( $comment->comment_ID, '_vote_score', true ) ?: 0;

                    $answer_schema = [
                        '@type'         => 'Answer',
                        '@id'           => get_comment_link( $comment ) . '#answer',
                        'text'          => wp_strip_all_tags( $comment->comment_content ),
                        'dateCreated'   => get_comment_date( 'c', $comment ),
                        'upvoteCount'   => (int) $vote_score,
                        'url'           => get_comment_link( $comment ),
                        'author'        => [
                            '@type' => 'Person',
                            'name'  => $comment->comment_author,
                        ],
                        // Mảng để lưu các bình luận nhỏ (Cấp 2+)
                        'comment'       => [], 
                    ];
                    
                    // Thêm URL tác giả nếu Answer này được đăng bởi user đã đăng nhập
                    if ($comment->user_id > 0) {
                         $answer_schema['author']['url'] = get_author_posts_url( $comment->user_id );
                    }

                    // Lưu vào mảng tạm
                    $answer_schemas[ $comment->comment_ID ] = $answer_schema;
                }
            }
            
            // 3. Lồng các Comment (Cấp 2+) vào Answer cha của chúng
            foreach ( $comments as $comment ) {
                // Chỉ xử lý bình luận CẤP 2+
                if ( $comment->comment_parent > 0 && isset( $answer_schemas[ $comment->comment_parent ] ) ) {
                    $comment_schema = [
                        '@type'         => 'Comment',
                        '@id'           => get_comment_link( $comment ) . '#comment',
                        'text'          => wp_strip_all_tags( $comment->comment_content ),
                        'dateCreated'   => get_comment_date( 'c', $comment ),
                        'url'           => get_comment_link( $comment ),
                        'author'        => [
                            '@type' => 'Person',
                            'name'  => $comment->comment_author,
                        ],
                    ];
                    
                    if ($comment->user_id > 0) {
                         $comment_schema['author']['url'] = get_author_posts_url( $comment->user_id );
                    }
                    
                    // Thêm Comment này vào mảng 'comment' của Answer cha
                    $answer_schemas[ $comment->comment_parent ]['comment'][] = $comment_schema;
                }
            }

            // 4. Phân loại Accepted và Suggested Answer
            foreach ( $answer_schemas as $comment_id => $schema ) {
                if ( $comment_id == $accepted_answer_id ) {
                    $question_schema['acceptedAnswer'] = $schema;
                } else {
                    $question_schema['suggestedAnswer'][] = $schema;
                }
            }
            
            // 5. Sắp xếp suggestedAnswer theo vote cao nhất
            if ( ! empty( $question_schema['suggestedAnswer'] ) ) {
                usort( $question_schema['suggestedAnswer'], function ( $a, $b ) {
                    return $b['upvoteCount'] <=> $a['upvoteCount'];
                } );
            }

            // 6. Tạo Schema @type: QAPage (Trang chính)
            $qapage_schema = [
                '@type'         => 'QAPage',
                '@id'           => get_permalink( $post->ID ), // URL của trang QAPage
                'mainEntity'    => $question_schema, // Lồng Question vào
            ];
            
            $schema_graph[] = $qapage_schema;
        }
        
        // --- Logic cho Trang Archive (Danh sách Câu hỏi) ---
        elseif ( is_post_type_archive( 'qapage_question' ) ) {
            $archive_url = get_post_type_archive_link( 'qapage_question' );
            $main_entities = [];
            
            while ( have_posts() ) : the_post();
                // Lấy các câu hỏi trong vòng lặp hiện tại
                $main_entities[] = [
                    '@type' => 'Question',
                    'name' => get_the_title(),
                    'url' => get_permalink(),
                    'answerCount' => (int) get_comments_number(),
                    'author' => [
                        '@type' => 'Person',
                        'name' => get_the_author(),
                    ],
                ];
            endwhile;
            
            // Đặt lại vòng lặp chính (quan trọng)
            rewind_posts();

            $schema_graph[] = [
                '@type' => 'QAPage',
                '@id' => $archive_url,
                'url' => $archive_url,
                'name' => get_the_archive_title(),
                'mainEntity' => $main_entities,
            ];
        }

        // In ra @graph
        if ( ! empty( $schema_graph ) ) {
            echo '<script type="application/ld+json">' . json_encode( ['@context' => 'https://schema.org', '@graph' => $schema_graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
        }
    }
}