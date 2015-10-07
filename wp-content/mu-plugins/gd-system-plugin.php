<?php
/*
Plugin Name: System Plugin
Description:
Author:
Plugin URI:
Author URI:
Version:
Text Domain: gd_system
Domain Path: /gd-system-plugin/lang
*/

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

// System plugin dir
define( 'GD_SYSTEM_PLUGIN_DIR', trailingslashit ( realpath( dirname( __FILE__ ) ) ) . 'gd-system-plugin/' );

// GD system url
// @codeCoverageIgnoreStart
if ( is_ssl() ) {
	define( 'GD_SYSTEM_PLUGIN_URL', trailingslashit( str_ireplace( 'http://', 'https://', WPMU_PLUGIN_URL ) ) );
} else {
	define( 'GD_SYSTEM_PLUGIN_URL', trailingslashit( WPMU_PLUGIN_URL ) );
}
// @codeCoverageIgnoreEnd

// Load the language
load_muplugin_textdomain( 'gd_system', 'gd-system-plugin/lang' );

// Register the autoloader
spl_autoload_register( 'gd_system_autoload' );

// Load the config lib
$gd_system_config = new GD_System_Plugin_Config();

// Set the WP101 key
$__conf = $gd_system_config->get_config();
if ( is_array( $__conf ) && array_key_exists( 'wp101_key', $__conf ) && is_string( $__conf['wp101_key'] ) ) {
	define( 'GD_WP101_API_KEY', $__conf['wp101_key'] );
}

// Stop 404 loops on images
$gd_system_404s = new GD_System_Plugin_404();

// Load the logging lib
$gd_system_logger = new GD_System_Plugin_Logger();

// Load the admin-menu helper
$gd_admin_menu = new GD_System_Plugin_Admin_Menu();

// Load the purge helper
$gd_cache_purge = new GD_System_Plugin_Cache_Purge();

// Load the message helper
$gd_messages = new GD_System_Plugin_Messages();

// Handle commands sent from Go Daddy to this WordPress site
$gd_command_controller = new GD_System_Plugin_Command_Controller();

// Prevent blacklisted plugins from being installed
$gd_system_blacklist = new GD_System_Plugin_Blacklist();

// Check some impotrant metrics
$gd_system_metrics = new GD_System_Plugin_Metrics();

// Handle communication with GD system API
$gd_api = new GD_System_Plugin_API();

// Auto upgrade all the things
$gd_auto_upgrades = new GD_System_Plugin_Auto_Upgrades();

// Support any HTML rewrites necessary
$gd_html_rewrite = new GD_System_Plugin_Output_Rewrite();

// Support multiple domains during a domain swing
$gd_domain_changer = new GD_System_Plugin_Domain_Changer();

// Support SSL integration
$gd_ssl = new GD_System_Plugin_SSL();

// Move users off the temp CNAME
$gd_cname = new GD_System_Plugin_CName();

// Load any hotfixes
$gd_hotfixes = new GD_System_Plugin_Hotfixes();

/**
 * Load limit login attempts, but only if the user isn't currently activating it or using it themselves
 */
$gd_system_include_limit_login_attempts = true;
$gd_system_limit_login_attempts_slug = 'limit-login-attempts/limit-login-attempts.php';

// Limit-login-attempts is already active
if ( $gd_system_include_limit_login_attempts && is_array( get_option( 'active_plugins' ) ) && in_array( $gd_system_limit_login_attempts_slug, get_option( 'active_plugins', array() ) ) ) {
	$gd_system_include_limit_login_attempts = false;
}

// The pre-activation plugin sandbox (and someone isn't trying to bypass the login page with fake request vars)
if ( $gd_system_include_limit_login_attempts && false === stripos( $_SERVER['PHP_SELF'], 'wp-login.php' ) && isset( $_REQUEST['action'] ) && 'error_scrape' === $_REQUEST['action'] && $gd_system_limit_login_attempts_slug === $_REQUEST['plugin'] ) {
	$gd_system_include_limit_login_attempts = false;
}

// The plugin is currently activating (and someone isn't trying to bypass the login page with fake request vars)
if ( $gd_system_include_limit_login_attempts && false === stripos( $_SERVER['PHP_SELF'], 'wp-login.php' ) && isset( $_REQUEST['action'] ) && 'activate' === $_REQUEST['action'] && isset( $_REQUEST['plugin'] ) && $gd_system_limit_login_attempts_slug === $_REQUEST['plugin'] ) {
	$gd_system_include_limit_login_attempts = false;
}

// Okay, include limit-login-attempts
if ( $gd_system_include_limit_login_attempts ) {
	add_filter( 'load_textdomain_mofile', 'gd_system_set_limit_login_attempts_lang', 10, 2 );
	require_once( GD_SYSTEM_PLUGIN_DIR . $gd_system_limit_login_attempts_slug );	
}

// Ensure that batcache's cache group labeled as persistent.  This ensures that
// when we purge a URL, it actually happens.
if ( isset( $batcache ) ) {
	$batcache->configure_groups();
}

/**
 * Filter to return our own "die" function (instead of _default_wp_die_handler)
 * @return string
 */
function gd_system_die_handler() {
    return 'gd_system_die' ;
}

// @codeCoverageIgnoreStart
/**
 * Die, but remove your filter first.  This abstraction is necessary for compatibility
 * with both wordpress AND the unit tests.  This is ignored by code coverage because
 * it's entirely unreachable without very dark magic.
 * @return void
 */
function gd_system_die() {
    die();
}

/**
 * Autoload any GD System Plugin classes
 * Code coverage doesn't reach here, even though this code *has* to be executed.
 * @param string $className
 */
function gd_system_autoload( $className ) {
	if ( 0 === stripos( $className, 'GD_System_' ) || 'WP_Async_Task' == $className ) {
		$filename = trailingslashit( GD_SYSTEM_PLUGIN_DIR ) . 'class-' . str_replace( '_', '-', strtolower( $className ) ) . '.php';
		require_once( $filename );
	}
}

/**
 * Fix the limit login attempts textdomain since it's being loaded as a mu-plugin
 * @param string $mofile
 * @param string $domain
 * @return string
 */
function gd_system_set_limit_login_attempts_lang( $mofile, $domain ) {
	if ( 'limit-login-attempts' === $domain ) {
		return GD_SYSTEM_PLUGIN_DIR . "limit-login-attempts/$domain-" . get_locale() . '.mo';
	}
	return $mofile;
}

// Compatibility with quick setup plugin
add_action( 'gd_quicksetup_install',      'gd_system_save_settings' );
add_filter( 'gd_quicksetup_install_done', 'gd_system_restore_settings' );

/**
 * Restore the system plugin settings to the DB
 */
function gd_system_restore_settings() {
	foreach ( $GLOBALS['gd_system_plugin_settings'] as $key => $val ) {
		update_option( $key, $val );
	}
}

/**
 * Add system plugin settings to the database
 */
function gd_system_save_settings() {
	$GLOBALS['gd_system_plugin_settings'] = array();
	foreach ( array( 'gd_system_first_login', 'gd_system_first_publish' ) as $key ) {
		$val = get_option( $key );
		if ( false !== $val ) {
			$GLOBALS['gd_system_plugin_settings'][ $key ] = $val;
		}
	}
}

/**
 * Exclusion of jetpack from auto updates
 * @param string $update
 * @param string $item
 * @return string
 */
function gd_system_auto_update_specific_plugins ( $update, $item ) {
    // Array of plugin slugs to always auto-update
    $plugins = array ( 
        'jetpack'
    );
    if ( in_array( $item->slug, $plugins ) ) {
        return false; // Never update plugins in this array
    } else {
        return $update; // Else, use the normal API response to decide whether to update or not
    }
}
add_filter( 'auto_update_plugin', 'gd_system_auto_update_specific_plugins', 10, 2 );


/**
 * Localization hack
 * @param string $mofile
 * @param string $domain
 * @return string
 */
function gd_system_localization_fallback( $mofile, $domain ) {
	if ( file_exists( $mofile ) && is_readable( $mofile ) ) {
		return $mofile;
	}
	$_mofile = preg_replace('/[_-][a-zA-Z]{2}\.mo$/', '.mo', $mofile);
	if ( file_exists( $_mofile ) && is_readable( $_mofile ) ) {
		return $_mofile;
	}
	return $mofile;
}
add_filter( 'load_textdomain_mofile', 'gd_system_localization_fallback', 0, 3 );

/**
 * Wrapper for header() function that still allows unit tests to pass
 * @param string $header
 */
function gd_system_header( $header ) {
	if ( PHP_SAPI != 'cli' && !headers_sent() ) {
		header( $header );
	}
	do_action( 'gd_system_sent_header', $header );
}
// @codeCoverageIgnoreEnd
