<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Auto_Upgrades
 * Update all the things
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Auto_Upgrades {

	/**
	 * Constructor
	 * @return array|WP_Error
	 */
	public function __construct( ) {
		
		// Don't auto upgrade core
		add_filter( 'auto_update_core', '__return_false' );
		add_filter( 'pre_site_transient_update_core', create_function( '', 'global $wp_version; $x = new stdClass(); $x->version_checked = $wp_version; return $x;' ) );
		add_filter( 'user_has_cap', array( $this, 'block_core_upgrades' ), 10, 3 );

		// Use default WP settings for these three
		//add_filter( 'auto_update_plugin', '__return_false' );
		// add_filter( 'auto_update_theme', '__return_false' );

		// Disable e-mails 3.7 Beta -> RC
		add_filter( 'automatic_updates_send_email', '__return_false' );
		
		// Disable e-mails 3.7 RC -> Final
		add_filter( 'enable_auto_upgrade_email', '__return_false' );
		
		// No debug e-mails
		add_filter( 'automatic_updates_send_debug_email', '__return_false' );
		
		// 3.7 Final filter
		add_filter( 'auto_core_update_send_email', '__return_false' );
	}

	/**
	 * Filter for user_has_cap
	 * @param array $allcaps All the capabilities of the user
	 * @param array $cap     [0] Required capability
	 * @param array $args    [0] Requested capability
	 *                       [1] User ID
	 *                       [2] Associated object ID
	 * @return array
	 */
	public function block_core_upgrades( $allcaps, $cap, $args ) {
		$allcaps['update_core'] = false;
		return $allcaps;
	}
}
