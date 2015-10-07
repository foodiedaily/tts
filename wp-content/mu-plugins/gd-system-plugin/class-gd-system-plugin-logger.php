<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

// Log levels
define( 'GD_SYSTEM_LOG_ERROR',   'ERROR' );
define( 'GD_SYSTEM_LOG_WARNING', 'WARNING' );
define( 'GD_SYSTEM_LOG_INFO',    'INFO' );

/**
 * Class GD_System_Plugin_Logger
 * Handle any logging.  Currently just a stub.
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Logger {

	/**
	 * Log a message
	 * @param string $type
	 * @param string $message
	 * @return void
	 */
	public function log( $type, $message ) {
		do_action( 'gd_system_log', $type, $message );
		// @todo
	}
}
