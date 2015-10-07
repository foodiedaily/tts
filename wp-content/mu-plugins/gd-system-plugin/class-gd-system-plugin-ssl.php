<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_SSL
 * Support SSL
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_SSL {

	/**
	 * Constructor
	 */
	public function __construct( ) {

		// Rewrite content
		if ( is_ssl() || ( defined( 'FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ) || ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN ) ) {
			add_filter( 'gd_system_html_rewrite', array( $this, 'rewrite_output' ), 999 );
		}
	}

	/**
	 * Change old domain / new domain
	 * @param string $content
	 * @return string
	 */
	public function rewrite_output( $content ) {

		// Replace http://domain
		$domain = parse_url( get_option( 'home' ), PHP_URL_HOST );
		$content = preg_replace( "#http://{$domain}(/?)#", 'https://' . $domain . '$1', $content );

		// Replace http://domain
		if ( $_SERVER['HTTP_HOST'] != $domain ) {
			$domain = $_SERVER['HTTP_HOST'];
			$content = preg_replace( "#http://{$domain}(/?)#", 'https://' . $domain . '$1', $content );
		}

		// Done
		return $content;
	}
}
