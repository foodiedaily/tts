<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Config
 * Handle reading system and reseller configurations
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Config {

	/**
	 * Config items
	 * @var array
	 */
	var $config = array();

	/**
	 * Is this account missing a gd-config.php file?
	 * @var bool
	 */
	var $missing_gd_config = false;

	// @codeCoverageIgnoreStart
	/**
	 * Constructor
	 */
	public function __construct() {
		if ( !defined( 'GD_RESELLER') && !defined( 'GD_VARNISH_SERVERS' ) && !function_exists( 'is_mobile_user_agent' ) ) {
			if ( file_exists( ABSPATH . 'gd-config.php' ) && is_readable( ABSPATH . 'gd-config.php' ) ) {
				require_once( ABSPATH . 'gd-config.php' );
			} else {
				$this->missing_gd_config = true;
			}
		}
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Get config
	 *
	 * @return array
	 */
	public function get_config() {

		if ( ! empty( $this->config ) ) {

			return $this->config;

		}

		$defaults = $this->_get_config( '/web/conf/gd-wordpress.conf' );

		$resellers = $this->_get_config( '/web/conf/gd-resellers.conf' );

		$reseller = ( defined( 'GD_RESELLER' ) && isset( $resellers[ GD_RESELLER ] ) ) ? $resellers[ GD_RESELLER ] : array();

		$this->config = array_merge( (array) $defaults, (array) $reseller );

		return $this->config;

	}

	/**
	 * Read a config file
	 * @param string $path
	 * @return array
	 */
	protected function _get_config( $path ) {
		$conf = array();
		if ( file_exists( $path ) && is_readable( $path ) && is_file( $path ) ) {
			$conf = @parse_ini_file( $path, true );
			if ( false === $conf ) {
				$conf = array();
			}
		}
		return $conf;
	}

}
