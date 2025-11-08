<?php
/**
 * inc/qapage/class-qapage-walker.php
 *
 * Walker (Trình duyệt) tùy chỉnh cho wp_list_comments.
 *
 * [SỬA LỖI V11 - FIX LỖI HIỂN THỊ COMMENT CON]
 * - Đã thay đổi thẻ <span class="comment-content"> (ở depth > 0)
 * thành <div class="comment-content">.
 * - Lý do: wpautop() bọc nội dung comment trong thẻ <p>,
 * dẫn đến HTML không hợp lệ (<p> bên trong <span>).
 * - File qapage-style.css V6 sẽ xử lý CSS cho <div> này.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class AMP_QAPage_Walker_Comment extends Walker_Comment {

    /**
     * Ghi đè phương thức 'start_el' (bắt đầu một phần tử).
     * Phải là PUBLIC để khớp với class cha Walker_Comment.
     */
    public function start_el( &$output, $comment, $depth = 0, $args = [], $id = 0 ) {
        
        $post = get_post( $comment->comment_post_ID );

        // =================================================================
        // LOGIC CHO CẤP 1 (Depth 0) - Đây là một "ANSWER"
        // =================================================================
        if ( $depth === 0 ) {
            
            // --- Lấy dữ liệu Meta ---
            $is_accepted = ( get_post_meta( $post->ID, '_qapage_accepted_answer_id', true ) == $comment->comment_ID );
            $vote_score = get_comment_meta( $comment->comment_ID, '_vote_score', true ) ?: 0;
            $current_user_id = get_current_user_id();
            $comment_id_str = 'comment_' . $comment->comment_ID; // ID cho AMP-Bind

            // --- Xác định quyền ---
            $can_accept = ( current_user_can( 'manage_options' ) || $current_user_id == $post->post_author ) && ( $comment->user_id == 0 || $current_user_id != $comment->user_id );
            $can_vote = is_user_logged_in() && ( $comment->user_id == 0 || $current_user_id != $comment->user_id );
            
            // --- State (Trạng thái) AMP-Bind cho Answer này ---
            $output .= '<amp-state id="answerState' . $comment_id_str . '">';
            $output .= '<script type="application/json">' . json_encode( [
                'vote_score' => (int) $vote_score,
                'is_accepted' => (bool) $is_accepted,
            ] ) . '</script>';
            $output .= '</amp-state>';

            // --- Bắt đầu thẻ <li> với Schema Answer ---
            $output .= sprintf( '<li id="comment-%s" %s itemscope itemtype="https://schema.org/Answer" itemprop="suggestedAnswer" [class]="\'qapage-answer\' + (answerState' . $comment_id_str . '.is_accepted ? \' accepted-answer\' : \'\')">',
                $comment->comment_ID,
                comment_class( 'qapage-answer', $comment, $post->ID, false )
                );
            $output .= '<article class="comment-body">';

            // --- Cột Bỏ phiếu (Voting Column) ---
            $output .= '<div class="answer-voting" data-comment-id="' . $comment->comment_ID . '">';
            
            // --- Form Vote Up ---
            $output .= '<form method="post" target="_top" class="vote-form vote-form-up"
                              action-xhr="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"
                              on="submit-success:AMP.setState({ 
                                  answerState' . $comment_id_str . ': { 
                                      vote_score: event.response.data.new_score 
                                  } 
                              }); submit-error: AMP.setState({ 
                                answerState' . $comment_id_str . ': { 
                                    error_message: event.response.message 
                                } 
                              })">';
                $output .= '<input type="hidden" name="action" value="qapage_vote">';
                $output .= '<input type="hidden" name="_ajax_nonce" value="' . wp_create_nonce( 'qapage_vote_nonce' ) . '">';
                $output .= '<input type="hidden" name="comment_id" value="' . $comment->comment_ID . '">';
                $output .= '<input type="hidden" name="direction" value="up">';
                $output .= '<button type="submit" class="vote-button vote-up" ' . ( $can_vote ? '' : 'disabled' ) . '>▲</button>';
                $output .= '<div submit-success><template type="amp-mustache"></template></div>';
                $output .= '<div submit-error><template type="amp-mustache"><span class="vote-error" [text]="answerState' . $comment_id_str . '.error_message">{{message}}</span></template></div>';
            $output .= '</form>';

            // --- Điểm Vote (Quản lý bằng AMP-Bind) ---
            $output .= '<div class="vote-count" itemprop="upvoteCount" 
                             [text]="answerState' . $comment_id_str . '.vote_score">' 
                         . esc_html( $vote_score ) 
                         . '</div>';

            // --- Form Vote Down ---
            $output .= '<form method="post" target="_top" class="vote-form vote-form-down"
                              action-xhr="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"
                              on="submit-success:AMP.setState({ 
                                  answerState' . $comment_id_str . ': { 
                                      vote_score: event.response.data.new_score 
                                  } 
                              }); submit-error: AMP.setState({ 
                                answerState' . $comment_id_str . ': { 
                                    error_message: event.response.message 
                                } 
                              })">';
                $output .= '<input type="hidden" name="action" value="qapage_vote">';
                $output .= '<input type="hidden" name="_ajax_nonce" value="' . wp_create_nonce( 'qapage_vote_nonce' ) . '">';
                $output .= '<input type="hidden" name="comment_id" value="' . $comment->comment_ID . '">';
                $output .= '<input type="hidden" name="direction" value="down">';
                $output .= '<button type="submit" class="vote-button vote-down" ' . ( $can_vote ? '' : 'disabled' ) . '>▼</button>';
                $output .= '<div submit-error><template type="amp-mustache"><span class="vote-error" [text]="answerState' . $comment_id_str . '.error_message">{{message}}</span></template></div>';
            $output .= '</form>';
            
            // --- Dấu Tick (Quản lý bằng AMP-Bind) ---
            $output .= '<div class="accepted-tick" 
                             title="Câu trả lời được chấp nhận"
                             [hidden]="!answerState' . $comment_id_str . '.is_accepted">✔</div>';
            
            $output .= '</div>'; // .answer-voting
            
            // --- Thân Câu trả lời (Answer Body) ---
            $output .= '<div class="answer-body">';
            $output .= '<div class="comment-content" itemprop="text">' . apply_filters( 'comment_text', get_comment_text( $comment ), $comment, $args ) . '</div>';
            $output .= '<div class="answer-meta">';
            
                // Thông tin tác giả Answer
                $output .= '<div class="comment-author" itemprop="author" itemscope itemtype="https://schema.org/Person">';
                $output .= get_avatar( $comment, $args['avatar_size'] );
                $output .= '<div class="author-details">';
                $output .= '<span itemprop="name">' . get_comment_author_link( $comment ) . '</span>';
                
                $author_url = get_comment_author_url( $comment );
                if ( empty( $author_url ) && $comment->user_id == 0 ) {
                    $author_url = get_comment_link( $comment );
                }
                $output .= '<meta itemprop="url" content="' . esc_url( $author_url ) . '">';
                
                $output .= '<time class="comment-date" itemprop="dateCreated" datetime="' . get_comment_date( 'c', $comment ) . '">' . get_comment_date( '', $comment ) . '</time>';
                $output .= '</div>'; // .author-details
                $output .= '</div>'; // .comment-author

                // Các nút chức năng
                $output .= '<div class="answer-actions">';
                
                // --- Form Chấp nhận Câu trả lời ---
                if ( $can_accept ) {
                    $output .= '<form method="post" target="_top" class="accept-answer-form"
                                      action-xhr="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"
                                      on="submit-success:AMP.setState({ 
                                          answerState' . $comment_id_str . ': { 
                                              is_accepted: event.response.data.status == \'accepted\' 
                                          } 
                                      }); submit-error: AMP.setState({ 
                                        answerState' . $comment_id_str . ': { 
                                            error_message: event.response.message 
                                        } 
                                      })">';
                        $output .= '<input type="hidden" name="action" value="qapage_accept_answer">';
                        $output .= '<input type="hidden" name="_ajax_nonce" value="' . wp_create_nonce( 'qapage_accept_nonce' ) . '">';
                        $output .= '<input type="hidden" name="comment_id" value="' . $comment->comment_ID . '">';
                        
                        $output .= '<button type="submit" class="accept-answer-button" 
                                        [text]="answerState' . $comment_id_str . '.is_accepted ? \'Bỏ chấp nhận\' : \'Chấp nhận câu trả lời\'">
                                        ' . ( $is_accepted ? 'Bỏ chấp nhận' : 'Chấp nhận câu trả lời' ) . '
                                    </button>';
                        $output .= '<div submit-error><template type="amp-mustache"><span class="vote-error" [text]="answerState' . $comment_id_str . '.error_message">{{message}}</span></template></div>';
                    $output .= '</form>';
                }

                // Nút Reply (để tạo Comment cấp 2+)
                $output .= get_comment_reply_link( array_merge( $args, [
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'add_below' => 'div-comment-', // (Phù hợp với AMP)
                    'reply_text' => 'Thêm bình luận nhỏ',
                ] ) );
                
                $output .= '</div>'; // .answer-actions
            $output .= '</div>'; // .answer-meta
            $output .= '</div>'; // .answer-body
            $output .= '</article>';

        // =================================================================
        // LOGIC CHO CẤP 2+ (Depth > 0) - Đây là một "COMMENT"
        // =================================================================
        } else {
            $output .= sprintf( '<li id="comment-%s" %s>',
                $comment->comment_ID,
                comment_class( 'qapage-comment', $comment, $post->ID, false )
            );
            
            $output .= '<article itemscope itemtype="https://schema.org/Comment" itemprop="comment">';
            $output .= '<div class="comment-body">';
            
            // [SỬA LỖI V11] Thay <span> bằng <div> và thêm apply_filters
            $output .= '<div class="comment-content" itemprop="text">' . apply_filters( 'comment_text', get_comment_text( $comment ), $comment, $args ) . '</div>';
            
            $output .= ' – <span class="comment-author" itemprop="author" itemscope itemtype="https://schema.org/Person">';
            $output .= '<span itemprop="name">' . get_comment_author_link( $comment ) . '</span>';
            
            $author_url = get_comment_author_url( $comment );
            if ( empty( $author_url ) && $comment->user_id == 0 ) {
                $author_url = get_comment_link( $comment );
            }
            $output .= '<meta itemprop="url" content="' . esc_url( $author_url ) . '">';
            
            $output .= '</span>';
            $output .= '<time class="comment-date" itemprop="dateCreated" datetime="' . get_comment_date( 'c', $comment ) . '"> @ ' . get_comment_date( '', $comment ) . '</time>';
            $output .= '</div>'; // .comment-body
            $output .= '</article>';
        }
    }
}