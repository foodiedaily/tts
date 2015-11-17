<?php

//add styles
function theme_enqueue_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
   wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/css/theme-orange.css' );
}

add_action( 'wp_enqueue_style', 'theme_enqueue_styles' );

//add scripts
function my_scripts_method() {
	wp_enqueue_script(
		'custom-script',
		get_stylesheet_directory_uri() . '/js/custom-script.js',
		array( 'jquery' )
	);
	wp_enqueue_script(
		'single-product',
		get_stylesheet_directory_uri() . '/js/single-product.js',
		array( 'jquery' )
	);
}

add_action('wp_logout','go_home');
function go_home(){
	$url = home_url();
	wp_redirect($url . "/login/");
	exit();
}

add_action('wp_login','login_url');
function login_url(){
	$url = home_url();
	wp_redirect($url . "/members/" . $_POST['log']);
	exit();
}

add_action( 'wp_enqueue_scripts', 'my_scripts_method' );

//hide admin bar
show_admin_bar( false );
