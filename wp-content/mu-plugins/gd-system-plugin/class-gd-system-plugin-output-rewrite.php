<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Output_Rewrite
 * Support various output rewrite functions
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Output_Rewrite {

	/**
	 * Constructor
	 */
	public function __construct( ) {
		add_action( 'template_redirect', array( $this, 'rewrite_output' ) );
	}

	/**
	 * Rewrite output
	 */
	public function rewrite_output() {
		ob_start( array( $this, 'ob' ) );
	}

	/**
	 * Rewrite content.
	 * Don't edit this, hook the gd_system_html_rewrite filter.
	 * @param string $content
	 * @return string
	 */
	public function ob( $content ) {
		return apply_filters( 'gd_system_html_rewrite', $content );
	}
}
