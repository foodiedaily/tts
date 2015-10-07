<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Hotfixes
 * Handle any hotfixes
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Hotfixes {

	/**
	 * Constructor.
	 * Hook any needed actions/filters
	 * @return void
	 */
	public function __construct() {

		// Enable sampling for WP Popular Posts, this makes it perform much better especially on high traffic sites
		add_filter( 'wpp_data_sampling', '__return_true' );
		
		// Clean up limit login attempts
		$flag = ( mt_rand( 0, 50 ) == 47 );
		if ( apply_filters( 'gd_system_clean_limit_login_attempts', $flag ) ) {
			add_action( 'muplugins_loaded', array( $this, 'clean_limit_login_attempts' ) );
		}
	}
	
	/**
	 * Clean up limit login attempts options.  On social sites, these can get to be
	 * huge arrays that turn into huge strings and break MySQL because of packet size
	 * limitations
	 * @return void
	 */
	public function clean_limit_login_attempts() {
		foreach( array( 'limit_login_retries_valid', 'limit_login_retries', 'limit_login_logged' ) as $opt ) {
			$val = get_option( $opt );
			if ( !empty( $val ) && is_array( $val ) && count( $val ) > 250 ) {
				uasort( $val, array( $this, '__sort' ) );
				$val = array_slice( $val, -200 );
				update_option( $opt, $val );
			}
		}
	}

	/**
	 * Sort function for limit login attempts options
	 * @param mixed $a
	 * @param mixed $b
	 * @return int
	 */
	public function __sort( $a, $b ) {
		if ( is_array( $b ) ) {
			if ( count( $a ) == count( $b ) ) {
				return 0;
			} else {
				return ( count( $a ) < count( $b ) ) ? - 1 : 1;
			}
		} else {
			if ( $a == $b ) {
				return 0;
			} else {
				return ( $a < $b ) ? -1 : 1;
			}
		}
	}
}
