<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Domain_Changer
 * Support a domain swing
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Domain_Changer {

	/**
	 * Old domain
	 * @var string
	 */
	protected $_old_domain = '';
	
	/**
	 * New domain
	 * @var string
	 */
	protected $_new_domain = '';

	/**
	 * Constructor
	 * @return array|WP_Error
	 */
	public function __construct( ) {
		if ( false !== get_option( 'gd_system_multi_domain' ) ) {

			// New domain
			$this->_new_domain = $_SERVER['HTTP_HOST'];

			// Old domain
			$this->_old_domain = parse_url( get_option( 'siteurl' ), PHP_URL_HOST );

			// Fix home & siteurl options
			add_filter( 'option_home', array( $this, 'change_domain' ) );
			add_filter( 'option_siteurl', array( $this, 'change_domain' ) );

			// Rewrite content
			add_filter( 'gd_system_html_rewrite', array( $this, 'rewrite_output' ) );
		}
	}

	/**
	 * Change the URL in siteurl and home options
	 * @param string $url
	 * @return string
	 */
	public function change_domain( $url ) {
		$url = str_replace( '//' . $this->_old_domain, '//' . $this->_new_domain, $url );
		return $url;
	}

	/**
	 * Change old domain / new domain
	 * @param string $content
	 * @return string
	 */
	public function rewrite_output( $content ) {
		return preg_replace( "#(https?)://{$this->_old_domain}(/?)#", '$1://' . $this->_new_domain . '$2', $content );
	}
}
