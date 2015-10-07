<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_API
 * Handle interactions with Go Daddy system REST API
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_API {

	/**
	 * Get blacklist
	 * @return array|WP_Error
	 */
	public function get_blacklist( ) {
		return $this->make_call( 'blacklistapi/' );
	}

	/**
	 * Validate an SSO hash
	 * @param string $hash
	 * @return array|WP_Error
	 */
	public function validate_sso_hash( $hash ) {
		return $this->make_call( 'ssoauthenticationapi/' . $hash . '?AllowSsoLogin', '"' . DB_NAME . '"', 'POST' );
	}
	
	/**
	 * See if a user has changed their domain
	 * @param string $domain
	 * @return array|WP_Error
	 */
	public function domain_changed( $domain ) {
		return $this->make_call( 'domains/' . $domain );
	}

	/**
	 * Get the arguments to pass into wp_remote_get or wp_remote_post
	 * @return array
	 */
	protected function get_args() {
		return array(
			'headers' => array(
				'Content-type' => 'application/json'
			)
		);
	}

	/**
	 * Talk to the API endpoint
	 * @param string $method
	 * @param array $args
	 * @param string $verb
	 * @return array|WP_Error
	 */
	public function make_call( $method, $args = array(), $verb = 'GET' ) {
		global $gd_system_config;

		$config = $gd_system_config->get_config();
		$max_retries = 1;
		$retries     = 0;
		if ( !in_array( $verb, array( 'GET', 'POST' ) ) ) {
			return new WP_Error( 'gd_system_api_bad_verb', sprintf( __( 'Unknown verb: %s. Try GET or POST', 'gd_system' ), $verb ) );
		}
		while ( $retries <= $max_retries ) {
			$retries++;
			if ( 'GET' === $verb ) {
				$url = $config['api_url'] . $method;
				if ( !empty( $args ) ) {
					$url .= '?' . build_query( $args );
				}
				add_filter( 'https_ssl_verify', '__return_false' );
				$result = wp_remote_get( $url, $this->get_args() );
				remove_filter( 'https_ssl_verify', '__return_false' );
			} elseif ( 'POST' === $verb ) {
				$_args = $this->get_args();
				$_args['body'] = $args;
				add_filter( 'https_ssl_verify', '__return_false' );
				$result = wp_remote_post( $config['api_url'] . $method, $_args );
				remove_filter( 'https_ssl_verify', '__return_false' );
			}
			if ( is_wp_error( $result ) ) {
				break;
			} elseif ( self::_is_retryable_error( $result ) ) {	
				
				// The service is in a known maintenance condition, give a sec to recover
				sleep( apply_filters( 'gd_system_api_retry_delay', 1 ) );
				continue;
			} else {
				break;
			}
		}

		do_action( 'gd_system_api_debug_request', $config['api_url'] . $method, $this->get_args() );
		do_action( 'gd_system_api_debug_response', array( 'result' => $result ) );

		if ( !is_wp_error( $result ) && '200' != $result['response']['code'] ) {
			return new WP_Error( 'gd_system_api_bad_status', sprintf( __( 'API returned bad status: %d: %s', 'gd_system' ), $result['response']['code'], $result['response']['message'] ) );
		}

		return $result;
	}
	
	/**
	 * Check if the result of a wp_remote_* call is an error and should be retried
	 * @param array $result
	 * @return bool
	 */
	protected static function _is_retryable_error( $result ) {
		if ( is_wp_error( $result ) ) {
			return false;
		}
		if ( !isset( $result['response'] ) || !isset( $result['response']['code'] ) || 503 != $result['response']['code'] ) {
			return false;
		}
		$json = json_decode( $result['body'], true );
		if ( isset( $json['status'] ) && 503 == $json['status'] && isset( $json['type'] ) && 'error' == $json['type'] && isset( $json['code'] ) && 'RetryRequest' == $json['code'] ) {
			return true;
		}
		return false;
	}
}
