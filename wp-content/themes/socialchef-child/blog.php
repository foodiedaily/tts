<?php
/* Template Name: Blog index page */
/*
 * The template for displaying the blog index page (list of blog posts)
 * @package WordPress
 * @subpackage SocialChef
 * @since SocialChef 1.0
 */
get_header('buddypress');
SocialChef_Theme_Utils::breadcrumbs();
get_sidebar('under-header');

if (get_post_meta($post->ID, 'progression_category_slug', true)): ?><?php else: ?>

    <div id="page-title">
        <div class="width-container">
            <?php if (function_exists('bcn_display')): ?>
                <div id="bread-crumb"><?php bcn_display() ?></div><?php endif; ?>
            <h1><?php the_title(); ?></h1>

            <div class="clearfix"></div>
        </div>
    </div><!-- close #page-title -->
<?php endif; ?>

    <div id="main">
        <div class="width-container bg-sidebar-pro">
            <div id="sidebar-border">
                <?php
                $rss = fetch_feed('http://foodiedaily.com/author/johanna-rupp/feed/');


                if (!is_wp_error($rss)) :

                    $max_items = $rss->get_item_quantity(6);
                    $rss_items = $rss->get_items(0, $max_items);
                endif;
                ?>
                <?php function get_first_image_url($html)
                {
                    if (preg_match('/<img.+?src="(.+?)"/', $html, $matches)) {
//                        var_dump($matches);
                        return $matches[1];
                    } else {
                        return false;
                    }
                }

                ?>
                <?php
                function shorten($string, $length)
                {
                    $suffix = '&hellip;';

                    $short_desc = trim(str_replace(array("/r", "/n", "/t"), ' ', strip_tags($string)));
                    $desc = trim(substr($short_desc, 0, $length));
                    $lastchar = substr($desc, -1, 1);
                    if ($lastchar == '.' || $lastchar == '!' || $lastchar == '?') $suffix = '';
                    $desc .= $suffix;
                    return $desc;
                }

                ?>
                <ul class="rss-items" id="wow-feed">
                    <?php
                    if ($max_items == 0)
                        echo '<li>No items.</li>';
                    else
                        foreach ($rss_items as $item) : ?>
                            <li class="item">
     <span class="rss-image">
         <?php $first_character = get_first_image_url($item->get_content())[0];
         if (get_first_image_url($item->get_content()) != NULL && $first_character == 'h') {
             echo '<img src="' . get_first_image_url($item->get_content()) . '"/>';
         } else if (get_first_image_url($item->get_content()) != NULL && $first_character != 'h') {
             echo '<img src="http://foodiedaily.com/' . get_first_image_url($item->get_content()) . '"/>';
         }
         ?>

     </span>
        <span class="data">
         <h3>
             <a href='<?php echo esc_url($item->get_permalink()); ?>'
                title='<?php echo esc_html($item->get_title()); ?>'><?php echo esc_html($item->get_title()); ?></a>
         </h3>
 <span class="date-image">&nbsp;</span><small><?php echo $item->get_date('F Y'); ?> </small>
 <span class="comment-image">&nbsp;</span><small><?php $comments = $item->get_item_tags('http://purl.org/rss/1.0/modules/slash/', 'comments'); ?><?php $number = $comments[0]['data']; ?>
                <?php if ($number == '1') {
                    echo $number . "&nbsp;" . "Comment";
                } else {
                    echo $number . "&nbsp;" . "Comments";
                } ?></small>        </span>


                                <p><?php echo shorten($item->get_content(), '300'); ?></p>


                            </li>
                        <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php
get_footer('buddypress');
?>