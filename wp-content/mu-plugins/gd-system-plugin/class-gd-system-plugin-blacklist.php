<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Blacklist
 * Prevent bad plugins from being activated.
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Blacklist {

	/**
	 * List of plugins to deactivate on shutdown
	 * @var array
	 */
	private $_plugins_to_deactivate = array();
	
	/**
	 * Constructor
	 * Hook any hooks.  Prevent bad plugins from being activated.
	 * @return GD_System_Plugin_Blacklist
	 */
	public function __construct() {

		// Block plugins from being installed through the wordpress.org directory
		add_filter( 'plugin_install_action_links', array( $this, 'disable_install_link' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'disable_activate_link' ), 10, 2 );
		add_action( 'current_screen', array( $this, 'load_styles' ) );

		// Block plugins from being activated (e.g. from an uploaded zip)
		add_action( 'activate_plugin', array( $this, 'disable_activation' ), ~PHP_INT_MAX, 2 );
	}
	
	/**
	 * Deactivate bad plugins on shutdown
	 * @return void
	 */
	public function deactivate_plugins() {
		foreach ( $this->_plugins_to_deactivate as $plugin ) {
			deactivate_plugins( $plugin );
		}
	}

	/**
	 * Load any javascript / css we need for our UI
	 * @param WP_Screen $screen
	 * @return void
	 */
	public function load_styles( WP_Screen $screen ) {
		if ( !in_array( $screen->id, array( 'plugins', 'plugin-install' ) ) ) {
			return;
		}

		// Load tiptip
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			// @codeCoverageIgnoreStart
			wp_enqueue_script( 'gd_system_tiptip_js', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/js/jquery.tiptip.js', array( 'jquery' ) );
			wp_enqueue_style( 'gd_system_tiptip_css', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/css/jquery.tiptip.css' );
			// @codeCoverageIgnoreEnd
		} else {
			wp_enqueue_script( 'gd_system_tiptip_js', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/js/jquery.tiptip.min.js', array( 'jquery' ) );
			wp_enqueue_style( 'gd_system_tiptip_css', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/css/jquery.tiptip.min.css' );
		}
	}

	/**
	 * Remove the "Install now" and "Details" links from the plugin directory listing
	 * @return array
	 */
	public function disable_install_link( $links, $plugin ) {
		if ( $this->is_plugin_blacklisted( $plugin ) ) {
			return array ( sprintf( '%1$s <a href="javascript:;" class="gd-system-plugin-tip" title="%2$s"><img src="%3$s" height="10" width="10" /></a>',
				__( 'Not available', 'gd_system' ),
				__( 'This plugin is not allowed on our system due to performance, security, or compatibility concerns. Please contact our support with any questions.', 'gd_system' ),
				GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/images/small-help-icon.gif'
			) );
		}
		return $links;
	}

	/**
	 * Remove the "Activate" link from the plugin page
	 * @return array
	 */
	public function disable_activate_link( $links, $plugin ) {	
		if ( isset( $links['activate'] ) && $this->is_plugin_blacklisted( $plugin ) ) {
			$links['activate'] = sprintf( '%1$s <a href="javascript:;" class="gd-system-plugin-tip" title="%2$s"><img src="%3$s" height="10" width="10" /></a>',
				__( 'Not available', 'gd_system' ),
				__( 'This plugin is not allowed on our system due to performance, security, or compatibility concerns. Please contact our support with any questions.', 'gd_system' ),
				GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/images/small-help-icon.gif'
			);
		}
		return $links;
	}

	/**
	 * This "blocks" any plugins from being activated.  It doesn't really block them, it just immediately
	 * deactivates them.  The activate and deactivate hooks will be run.
	 * @param string $plugin Slug (e.g. hello-dolly/hello.php
	 * @param bool $network_wide Only used for multisite.  Ignored by this function.
	 * @return void
	 */
	public function disable_activation( $plugin, $network_wide ) {
		global $gd_messages;
		if ( $this->is_plugin_blacklisted( $plugin ) ) {
			$this->_plugins_to_deactivate[] = $plugin;
			if ( false == has_action( 'shutdown', array( $this, 'deactivate_plugins' ) ) ) {
				add_action( 'shutdown', array( $this, 'deactivate_plugins' ) );
			}
			$gd_messages->add_message( sprintf( __( 'This plugin %s is not allowed on our system. It has been automatically deactivated.', 'gd_system' ), $plugin ) );
		}
	}

	/**
	 * Is a plugin blacklisted?
	 * @todo Version comparison
	 * @param string|array $plugin Plugin filename or array of plugin info (returned from WordPress' plugin api)
	 * @return bool
	 */
	public function is_plugin_blacklisted( $plugin ) {
		if ( is_array( $plugin ) ) {
			$info = $plugin;
			$_plugin = $info['slug'];
			$version = $info['version'];
		} else {
			if ( file_exists( trailingslashit( WP_PLUGIN_DIR ) . $plugin ) ) {
				$info = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $plugin );
			} elseif ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $plugin ) ) {
				$info = get_plugin_data( trailingslashit( WPMU_PLUGIN_DIR ) . $plugin );
			} else {
				return true;
			}
			$_plugin = $plugin;
			$version = $info['Version'];
		}
		if ( false !== strpos( $_plugin, '/' ) ) {
			$_plugin = dirname( $_plugin );
		}

		$blacklist = $this->get_blacklist();
		foreach ( $blacklist as $bad_plugin ) {
			if ( 0 === strcasecmp( $_plugin, $bad_plugin['name'] ) && version_compare( $version, $bad_plugin['minVersion'] ) >= 0 && version_compare($version, $bad_plugin['maxVersion']) <= 0 ) {
				return true;
			}
		}

		return false;	
	}

	/**
	 * Get a list of blacklisted plugins
	 * @return array
	 */
	public function get_blacklist() {
		global $gd_system_logger, $gd_api;
		$transient = get_site_transient( 'gd_system_blacklist' );
		if ( !empty( $transient ) ) {
			return $transient;
		}
		$json = array();
		$resp = $gd_api->get_blacklist();
		if ( is_wp_error( $resp ) ) {
			$gd_system_logger->log( GD_SYSTEM_LOG_ERROR, 'Could not fetch blacklist. Error [' . $resp->get_error_code() . ']: ' . $resp->get_error_message() );
		} else {
			$json = json_decode( $resp['body'], true );
			if ( null === $json || !is_array( $json ) ) {
				$gd_system_logger->log( GD_SYSTEM_LOG_ERROR, 'Could not decode blacklist.' );
				$json = array();
			} else {		
				$json = $json['data'];
			}
		}
		set_site_transient( 'gd_system_blacklist', $json, 300 );
		return $json;
	}
}
