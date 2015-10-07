<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Metrics
 * Records two key metrics:
 *	- First login
 *  - First publish
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Metrics {

	/**
	 * Constructor.
	 * Hook any needed actions/filters
	 * @return void
	 */
	public function __construct() {

		// If we've already recorded a first publish time, just bail
		if ( '' == get_option( 'gd_system_first_publish' ) ) {
			
			// Theme change
			add_action( 'switch_theme',		array( $this, 'record_first_publish' ) );

			// Update posts
			add_action( 'publish_post',     array( $this, 'record_first_publish' ) );
			add_action( 'edit_post',        array( $this, 'record_first_publish' ) );
			add_action( 'deleted_post',     array( $this, 'record_first_publish' ) );
		}
		
		
		// Record first login
		if ( '' == get_option( 'gd_system_first_login' ) ) {
			add_action( 'admin_init', array( $this, 'record_first_login' ) );
		}
	}

	/**
	 * Record first login
	 * @return void
	 */
	public function record_first_login() {
		if ( current_user_can( 'activate_plugins' ) ) {
			update_option( 'gd_system_first_login', time() );
		}
	}
	
	/**
	 * Record a first publish
	 * @param int $post_id
	 * @return void
	 */
	public function record_first_publish( $post_id ) {

		// Get the post
		$post = get_post( $post_id );
		if ( true !== ( $post instanceof WP_Post ) ) {
			return;
		}

		// We don't care about revisions
		if ( $post->post_type == 'revision' ) {
			return;
		}
		update_option( 'gd_system_first_publish', time() );
	}
}
