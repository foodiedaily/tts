<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_404
 * Stop infinite 404 loops
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_404 {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'stop_infinite_404_loops' ) );
	}

	/**
	 * Some plugins / themes fetch local resources over http.  When the resources aren't there,
	 * WordPress returns a 404 page, which causes the theme / plugin to fire again and try to
	 * fetch the same missing resource and creates an infinite 404 loop.  This intercedes and
	 * stops that behavior.  Pages that end with .htm and .html will still render correctly.
	 * @return void
	 */
	public function stop_infinite_404_loops() {
		global $wp_query;
		if ( is_404() && preg_match( '/^[^?&=]+\.(css|gif|jpeg|jpg|js|png)(\?|&)?(.*)?$/i', $wp_query->query['pagename'] ) ) {
			status_header( 404 );

			switch ( strtolower( pathinfo( $wp_query->query['pagename'], PATHINFO_EXTENSION ) ) ) { 
				case 'gif' :
					gd_system_header( 'Content-type: image/gif' );
					include( GD_SYSTEM_PLUGIN_DIR . '/images/404.gif' );
					break;
				case 'jpg' :
				case 'jpeg' :
					gd_system_header( 'Content-type: image/jpeg' );
					include( GD_SYSTEM_PLUGIN_DIR . '/images/404.jpg' );
					break;
				case 'png' :
					gd_system_header( 'Content-type: image/png' );
					include( GD_SYSTEM_PLUGIN_DIR . '/images/404.png' );
					break;
				case 'css' :
					gd_system_header( 'Content-type: text/css' );
					echo "\n";
					break;
				case 'js' :
					gd_system_header( 'Content-type: application/javascript' );
					echo "\n";
					break;
			}

			add_filter( 'wp_die_handler', 'gd_system_die_handler', 10, 1 );
			wp_die();
		}
	}
}
