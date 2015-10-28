<?php
/**
 * Register our sidebars and widgetized areas.
 *
 */
/* Modify excerpt length (grid) */

function catcre_widgets_init() {

    register_sidebar( array(
        'name' => 'Header Left Widget',
        'id' => 'head_left_widget',
        'before_widget' => '<div>',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="hlw">',
        'after_title' => '</h2>',
    ) );

    register_sidebar( array(
        'name' => 'Header Right Widget',
        'id' => 'head_right_widget',
        'before_widget' => '<div>',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="hrw">',
        'after_title' => '</h2>',
    ) );
}
add_action( 'widgets_init', 'catcre_widgets_init' );


/*guest author custom field*/

add_filter( 'the_author', 'guest_author_name' );
add_filter( 'get_the_author_display_name', 'guest_author_name' );

function guest_author_name( $name ) {
    global $post;

    $author = get_post_meta( $post->ID, 'guest-author', true );

    if ( $author )
        $name = $author;

    return $name;
}