<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_CName
 * Urge the user not to stay on the temporary cname and to switch to
 * a permanent domain
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_CName {
	
	/**
	 * Constructor.
	 * Hook any needed actions/filters
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Initialize the script
	 */
	public function init() {
		global $gd_system_config;
		$config = $gd_system_config->get_config();

		// Don't modify these parts of the UI when there's no reseller present
		if ( true == $gd_system_config->missing_gd_config ) {
			return;
		}

		// Only for admins
		if ( current_user_can( 'activate_plugins' ) ) {

			// See if we're on a temporary domain
			$flag = false;
			if ( isset( $config['cname_domains'] ) && is_array( $config['cname_domains'] ) ) {
				foreach( $config['cname_domains'] as $domain ) {
					if ( 0 === strcasecmp( substr( $_SERVER['HTTP_HOST'], 0 - strlen( $domain ) ), $domain ) ) {
						$flag = true;
						break;
					}
				}
			}
			$flag = ( $flag && !$this->_user_changed_domain() );

			// If we're on a temporary domain, then show a notice to the user
			if ( $flag ) {
				add_action( 'admin_notices', array( $this, 'show_notice'), -PHP_INT_MAX );
			}
		}
	}

	/**
	 * Check the API, see if the user has changed their domain, but it isn't reflected here yet because we're waiting on the DNS TTL to take effect.
	 * @return bool
	 */
	private function _user_changed_domain() {
		global $gd_api, $gd_system_logger, $gd_system_config;
		$config = $gd_system_config->get_config();

		// See if we're on a temporary domain
		$domain = '';
		if ( isset( $config['cname_domains'] ) && is_array( $config['cname_domains'] ) ) {
			foreach( $config['cname_domains'] as $domain ) {
				if ( 0 === strcasecmp( substr( $_SERVER['HTTP_HOST'], 0 - strlen( $domain ) ), $domain ) ) {
					$domain = $_SERVER['HTTP_HOST'];
					break;
				}
			}
		}
		
		// If we didn't match a domain, then they're good
		if ( empty( $domain ) ) {
			return true;
		}

		// Check the transient
		$transient = get_site_transient( 'gd_system_domain_changed' );
		if ( false !== $transient ) {
			return ( 'Y' === $transient );
		}

		// Check if the domain has been changed in the db, but dns hasn't propagated yet
		$json = array();
		$resp = $gd_api->domain_changed( $domain );
		if ( is_wp_error( $resp ) ) {
			$gd_system_logger->log( GD_SYSTEM_LOG_ERROR, 'Could not fetch response from api to check if domain was changed. Error [' . $resp->get_error_code() . ']: ' . $resp->get_error_message() );
		} else {
			$json = json_decode( $resp['body'], true );
			if ( null === $json ) {
				$gd_system_logger->log( GD_SYSTEM_LOG_ERROR, 'Could not decode domain changed api response.' );
			}
		}

		// Done
		$ret = false;
		if ( isset( $json['domainChanged'] ) ) {
			$ret = $json['domainChanged'];
		}
		set_site_transient( 'gd_system_domain_changed' , $ret ? 'Y' : 'N', isset( $conf['cname_timeout'] ) ? $conf['cname_timeout'] : 300 );
		return $ret;
	}

	/**
	 * Show a message prompting the customer to update change domain to not use a temporary CNAME
	 */
	public function show_notice() {
		global $gd_system_config;
		$config = $gd_system_config->get_config();
		$url = '';
		if ( isset( $config['cname_link'] ) ) {
			$url = str_replace( '%domain%', $_SERVER['HTTP_HOST'], $config['cname_link'] );
			$url = str_replace( '%pl_id%', defined( 'GD_RESELLER' ) ? GD_RESELLER : '', $url );
		}
		?>
		<div class="updated error">
			<p><?php echo sprintf( __( '<strong>Note:</strong> You\'re using the temporary domain <strong>%s</strong>. <a href="%s" target="_blank">Change domain</a>', 'gd_system' ),
				$_SERVER['HTTP_HOST'],
				esc_attr( $url )
			); ?></p>
		</div>
		<?php
	}
}
