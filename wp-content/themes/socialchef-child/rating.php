<?php

require_once('../../../wp-load.php');
$rating=$_GET['rating'];
$args = array(
    'number' => '1',
    'orderby' => 'comment_id',
    'order' => 'DESC'
);
$comment_id = 0;
$comments = get_comments($args);
foreach($comments as $comment) :
    $comment_id = $comment->comment_ID;
endforeach;
add_comment_meta($comment_id + 1, "rating", $rating);


/**
 * Created by PhpStorm.
 * User: Islam
 * Date: 10/29/2015
 * Time: 12:48 PM
 */