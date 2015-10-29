<?php
global $post;
$post_id = $post;

// Do not delete these lines
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
    die (_e('Please do not load this page directly. Thanks!', 'socialchef'));

if (post_password_required()) { ?>
    <p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.', 'socialchef') ?></p>
    <?php
    return;
}
?>

<!--comments-->
<div class="comments" id="recipe_comments" itemprop="interactionCount"
     content="UserComments:<?php echo get_comments_number($post_id); ?>">
    <?php if (have_comments()) { ?>
        <h1><?php comments_number(__('No comments', 'socialchef'), __('One comment', 'socialchef'), __('% comments', 'socialchef')); ?></h1>
        <ol class="comment-list">
            <?php wp_list_comments('type=comment&callback=socialchef_comment&end-callback=socialchef_comment_end&style=ol'); ?>
        </ol>
        <?php paginate_comments_links(); ?>
    <?php } else { // this is displayed if there are no comments so far ?>
        <?php if ('open' == $post->comment_status) { ?>
            <p class="zerocomments"><?php _e('No comments yet, be the first to leave one!', 'socialchef') ?></p>
            <!-- If comments are open, but there are no comments. -->
        <?php } else { // comments are closed ?>
            <!-- If comments are closed. -->
            <p class="nocomments"></p>
        <?php } ?>
    <?php } ?>
    <?php if ('open' == $post->comment_status) { ?>
        <?php if (get_option('comment_registration') && !$user_ID) { ?>
            <p><?php echo sprintf(__('You must be <a href="%s/wp-login.php?redirect_to=%s">logged in</a> to post a comment.', 'socialchef'), esc_url(home_url()), esc_url(get_permalink())); ?></p>
        <?php } else { ?>

            <?php

            $args = array();
            $args['logged_in_as'] = "<p>" . sprintf(__('Logged in as <a href="%s/wp-admin/profile.php">%s</a>.', 'socialchef'), esc_url(home_url()), $user_identity) . ' ' . sprintf(__('<a href="%s" title="Log out of this account">Log out &raquo;</a>', 'socialchef'), wp_logout_url(get_permalink())) . '</p>';

            ob_start();
            ?>
            <p><?php _e('<strong>Note:</strong> Comments on the web site reflect the views of their respective authors, and not necessarily the views of this web portal. Members are requested to refrain from insults, swearing and vulgar expression. We reserve the right to delete any comment without notice or explanations.', 'socialchef') ?></p>
            <p><?php _e('Your email address will not be published. Required fields are signed with <span class="req">*</span>', 'socialchef') ?></p>
            <?php
            $args['comment_notes_before'] = ob_get_contents();
            ob_end_clean();

            ob_start();
            ?>
            <div class="woocommerce">
                <?php
                $commenter = wp_get_current_commenter();

                $comment_form = array(
//                    'title_reply'          => have_comments() ? __( 'Add a review', 'woocommerce' ) : __( 'Be the first to review', 'woocommerce' ) . ' &ldquo;' . get_the_title() . '&rdquo;',
                    'title_reply_to'       => __( 'Leave a Reply to %s', 'woocommerce' ),
                    'comment_notes_before' => '',
                    'comment_notes_after'  => '',
                    'fields'               => array(
                        'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name', 'woocommerce' ) . ' <span class="required">*</span></label> ' .
                            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" aria-required="true" /></p>',
                        'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'woocommerce' ) . ' <span class="required">*</span></label> ' .
                            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" aria-required="true" /></p>',
                    ),
                    'label_submit'  => __( 'Submit', 'woocommerce' ),
                    'logged_in_as'  => '',
                    'comment_field' => ''
                );

                if ( $account_page_url = wc_get_page_permalink( 'myaccount' ) ) {
                    $comment_form['must_log_in'] = '<p class="must-log-in">' .  sprintf( __( 'You must be <a href="%s">logged in</a> to post a review.', 'woocommerce' ), esc_url( $account_page_url ) ) . '</p>';
                }

                if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) {
                    $comment_form['comment_field'] = '<p class="comment-form-rating"><label for="rating">' . __( 'Your Rating', 'woocommerce' ) .'</label><select name="rating" id="recipe_rating">
							<option value="">' . __( 'Rate&hellip;', 'woocommerce' ) . '</option>
							<option value="5">' . __( 'Perfect', 'woocommerce' ) . '</option>
							<option value="4">' . __( 'Good', 'woocommerce' ) . '</option>
							<option value="3">' . __( 'Average', 'woocommerce' ) . '</option>
							<option value="2">' . __( 'Not that bad', 'woocommerce' ) . '</option>
							<option value="1">' . __( 'Very Poor', 'woocommerce' ) . '</option>
						</select></p>';
                }

                $comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . __( 'Your Review', 'woocommerce' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>';

                comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
                ?>
            </div>
            <?php

//            comment_form($args);
            ?>
            <!--//post comment form-->
        <?php } /* if (get_option('comment_registration')... */ ?>
    <?php } /* if ('open'... */ ?>
</div><!--comments-->
