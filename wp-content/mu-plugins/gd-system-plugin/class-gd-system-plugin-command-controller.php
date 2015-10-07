<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Command_Controller
 * Handle any commands the Go Daddy system needs to send to this WP site
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Command_Controller {

	/**
	 * Constructor
	 * Add any actions / hooks
	 * @return GD_System_Plugin_Command_Controller
	 */
	public function __construct() {

		// Handle any "?GD_" commands
		add_action( 'init', array( $this, 'handle_command' ) );
	}

	/**
	 * Handle any commands passed on the query string
	 * @return void
	 */
	public function handle_command() {
		if ( !isset( $_REQUEST['GD_COMMAND'] ) ) {
			return;
		}
		switch ( $_REQUEST['GD_COMMAND'] ) {
			case 'FLUSH_CACHE' :
				if ( wp_verify_nonce( $_REQUEST['GD_NONCE'], 'GD_FLUSH_CACHE' ) ) {
					$this->flush_cache();
				}
				break;
			case 'SSO_LOGIN' :
				$this->sso_login();
				break;
			case 'ENABLE_EDITORS' :
				if ( wp_verify_nonce( $_REQUEST['GD_NONCE'], 'GD_ENABLE_EDITORS' ) ) {
					$this->enable_editors();
				}
				break;
		}
	}

	/**
	 * Initiate a cache flush
	 * @return void
	 */
	public function flush_cache() {
		global $gd_messages, $gd_cache_purge;
		if ( !is_user_logged_in() || !current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$gd_cache_purge->ban_cache();
		$gd_cache_purge->flush_transients();
		$gd_messages->add_message( __( 'Cache cleared', 'gd_system' ) );
		@wp_safe_redirect( esc_url( remove_query_arg( 'GD_COMMAND' ) ) );
		add_filter( 'wp_die_handler', 'gd_system_die_handler', 10, 1 );
		wp_die();
	}

	/**
	 * Initiate an SSO login
	 * @return void
	 */
	public function sso_login() {

		// Get SSO hash
		$hash = '';
		if ( isset( $_REQUEST['SSO_HASH'] ) ) {
			$hash = $_REQUEST['SSO_HASH'];
		}

		// @codeCoverageIgnoreStart
		// Redirect for www domains
		if ( preg_match( '/^www\./', parse_url( get_option( 'home' ), PHP_URL_HOST ) ) && !preg_match( '/^www\./', $_SERVER['HTTP_HOST'] ) ) {
				@wp_safe_redirect( home_url() . '?' . build_query( array(
						'GD_COMMAND' => 'SSO_LOGIN',
						'SSO_HASH' => $_REQUEST['SSO_HASH'],
						'SSO_USER_ID' => $_REQUEST['SSO_USER_ID'],
				) ) );
				add_filter( 'wp_die_handler', 'gd_system_die_handler', 10, 1 );
				wp_die();
		}
		// @codeCoverageIgnoreEnd

		// Get SSO user
		$user_id = 0;
		if ( isset( $_REQUEST['SSO_USER_ID'] ) && !empty( $_REQUEST['SSO_USER_ID'] ) ) {
			$user_id = $_REQUEST['SSO_USER_ID'];
		} else {
			$user = get_users( array(
				'role'   => 'administrator',
				'number' => 1
			) );
			if ( ! $user[0] instanceof WP_User ) {
				return;
			}
			$user_id = $user[0]->ID;
		}

		// Set the cookie
		if ( $this->_is_valid_sso_login( $hash ) ) {
			@wp_set_auth_cookie( $user_id );
		}

		// Redirect to the dashboard
		@wp_safe_redirect( admin_url() );
		add_filter( 'wp_die_handler', 'gd_system_die_handler', 10, 1 );
		wp_die();
	}

	/**
	 * Initiate an SSO login
	 * @return bool
	 */
	protected function _is_valid_sso_login( $hash ) {
		global $gd_system_logger, $gd_api;
		if ( is_user_logged_in() ) {
			return false;
		}

		// Validate sso hash
		$resp = $gd_api->validate_sso_hash( $hash );
		if ( is_wp_error( $resp ) ) {
			$gd_system_logger->log( GD_SYSTEM_LOG_ERROR, 'Could not fetch response from api to validate SSO hash. Error [' . $resp->get_error_code() . ']: ' . $resp->get_error_message() );
			return false;
		}

		// Done
		if ( is_string( $resp['body'] ) ) {
			return 'true' === strtolower( $resp['body'] );
		}
		return false;
	}

	/**
	 * Enable the file editors
	 * @return void
	 */
	public function enable_editors() {
		global $gd_messages;
		if ( !is_user_logged_in() || !current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( false === get_site_option( 'gd_file_editor_enabled' ) ) {
			add_site_option( 'gd_file_editor_enabled', 'yes', '', 'no' );
		} else {
			update_site_option( 'gd_file_editor_enabled', 'yes' );
		}

		$gd_messages->add_message( __( 'File editing enabled', 'gd_system' ) );
		@wp_safe_redirect( esc_url( remove_query_arg( 'GD_COMMAND' ) ) );
		add_filter( 'wp_die_handler', 'gd_system_die_handler', 10, 1 );
		wp_die();
	}
}
