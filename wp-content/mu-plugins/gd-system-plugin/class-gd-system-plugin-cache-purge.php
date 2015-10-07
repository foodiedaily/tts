<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Cache_Purge
 * Clear the cache at appropriate times.
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 * @see https://github.com/techcrunch/wp-async-task#quick-start
 */
class GD_System_Plugin_Cache_Purge extends WP_Async_Task {

	/**
	 * Arbitrary action, required for async
	 * @var string
	 */
	protected $action = 'flush_cache';

	/**
	 * Array of URLs to purge
	 * @var array
	 */
	protected $_urls_to_purge = array();

	/**
	 * Constructor
	 * Hook actions / filters
	 * @return GD_System_Plugin_Cache_Purge
	 */
	public function __construct() {
		parent::__construct();

		// Theme change
		add_action( 'switch_theme', array( $this, 'ban_cache' ) );

		// Plugin activate/deactivate
		add_action( 'deactivated_plugin', array( $this, 'ban_cache' ) );
		add_action( 'activated_plugin',   array( $this, 'ban_cache' ) );

		// Core update
		add_action( '_core_updated_successfully', array( $this, 'ban_cache' ) );

		// Plugin / theme update
		add_action( 'upgrader_process_complete', array( $this, 'ban_cache' ) );

		// Permalink change
		add_action( 'update_option_permalink_structure', array( $this, 'ban_cache' ) );

		// Update posts
		add_action( 'publish_post',     array( $this, 'purge_cache' ) );
		add_action( 'edit_post',        array( $this, 'purge_cache' ) );
		add_action( 'deleted_post',     array( $this, 'purge_cache' ) );
		add_action( 'clean_post_cache', array( $this, 'purge_cache' ) );

		// Update comments
		add_action( 'comment_post',          array( $this, 'purge_comment' ) );
		add_action( 'wp_set_comment_status', array( $this, 'purge_comment' ) );
		add_action( 'edit_comment',          array( $this, 'purge_comment' ) );

		// Theme customizer
		add_action( 'customize_save',	array( $this, 'ban_cache' ) );

		// Changed widgets
		add_action( 'update_option_sidebars_widgets', array( $this, 'ban_cache' ) );
	}

	/**
	 * Trigger a cache ban (purge all) before the page reloads
	 * @return void
	 */
	public function ban_cache() {
		if ( !has_action( 'shutdown', array( $this, 'do_ban_cache' ), 100 ) ) {
			add_action( 'shutdown', array( $this, 'do_ban_cache' ), 100 );
		}
	}

	/**
	 * Purge (a single URL) the cache
	 * @return void
	 */
	public function do_purge_cache() {
		do_action( $this->action, array( 'ban' => 0, 'urls' => array_unique( $this->_urls_to_purge ) ) );
	}

	/**
	 * Ban (purge all) the cache
	 * @return void
	 */
	public function do_ban_cache() {

		// Don't use $this->action because some themes/plugins call this statically
		do_action( 'flush_cache', array( 'ban' => 1, 'urls' => array() ) );
	}

	/**
	 * Purge a page from cache when there's a comment
	 * @param int $comment_id
	 * @return void
	 */
	public function purge_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );
		$this->purge_cache( $post );
	}

	/**
	 * Purge a single page from the cache
	 * @param int $post_id
	 * @return void
	 */
	public function purge_cache( $post_id ) {

		// If it's not posted publicly, bail
		$post = get_post( $post_id );
		if ( true !== ( $post instanceof WP_Post ) ) {
			return;
		}
		if ( $post->post_type == 'revision' || !in_array( get_post_status( $post_id ), array( 'trash', 'publish' ) ) ) {
			return;
		}
		if ( false == parse_url( $post->guid, PHP_URL_PATH ) ) {
			return;
		}

		// Purge from batcache and varnish
		$urls = array(
			get_permalink( $post_id ),
			trailingslashit( get_option( 'home' ) ),
			get_option( 'home' ),
		);
		foreach ( $urls as $url ) {
			$this->_urls_to_purge[] = $url;
		}

		// Hook shutdown
		if ( !has_action( 'shutdown', array( $this, 'do_purge_cache' ), 200 ) ) {
			add_action( 'shutdown', array( $this, 'do_purge_cache' ), 200 );
		}
	}

	/**
	 * Batcache helper function
	 * @param string $url
	 * @return bool
	 */
	public function batcache_clear_url( $url ) {
		global $batcache;
		if ( empty( $batcache ) || !( $batcache instanceof batcache ) ) {
			return;
		}
		if ( empty( $url ) ) {
			return false;
		}
		$url_key = md5( $url );
		wp_cache_add( "{$url_key}_version", 0, $batcache->group );
		return wp_cache_incr( "{$url_key}_version", 1, $batcache->group );
	}

    /**
     * Prepare data for the asynchronous request
     *
     * @throws Exception If for any reason the request should not happen
     *
     * @param array $data An array of data sent to the hook
     *
     * @return array
     */
    protected function prepare_data( $data ) {
		return $data[0];
	}

    /**
     * Run the async task action
     */
    protected function run_action() {

		$ban  = $_POST['ban'];
		$urls = $_POST['urls'];

		// BAN everything
		if ( $ban ) {
			$url = get_home_url();
			if ( preg_match( '/http[s]?:\/\/([^\/]+)/i', $url, $matches ) ) {
				$_url = str_replace( $matches[1], GD_VIP, $url );
				$_url = preg_replace( '/https:/i', 'http:', $_url );
				$ret = wp_remote_request( $_url, array(
					'method' => 'BAN',
					'headers' => array(
						'Host'   => $matches[1]
					)
				) );
			}

		// PURGE selectively
		} else {
			foreach ( $urls as $url ) {
				if ( preg_match( '/http[s]?:\/\/([^\/]+)/i', $url, $matches ) ) {
					$_url = str_replace( $matches[1], GD_VIP, $url );
					$_url = preg_replace( '/https:/i', 'http:', $_url );
					$ret = wp_remote_request( $_url, array(
						'method' => 'PURGE',
						'headers' => array(
							'Host'   => $matches[1]
						)
					) );
				}
				$this->batcache_clear_url( $url );
			}
		}

		// Flush object cache
		wp_cache_flush();

		// Trigger a flush on all php processes
		// USUALLY this is handled in the object cache completely
		// but if this is invoked from a CLI context and there
		// is no APC loaded, then this will still send
		// the signal out to all the child procs
		update_option( 'gd_system_last_cache_flush', time() );
	}

	/**
	 * Delete all transient data from the options table
	 *
	 * WordPress only deletes expired transients when something tries
	 * to call that transient key again. This means over time there could
	 * be many thousands of transient option rows polluting the database,
	 * which can result in noticable performance impact.
	 *
	 * This method should be called when the customer is explicitly
	 * clearing their site's cache. Since transients are a form of cache,
	 * we will flush them all away regardless of TTL status.
	 *
	 * @see HOSTAPPS-3157
	 */
	public function flush_transients() {

		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'" );

	}

}

// @codeCoverageIgnoreStart
if ( defined( 'WP_CLI' ) && WP_CLI ) :

/**
 * Class GD_System_Plugin_Purge_Command
 * WP-CLI hook.
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Purge_Command extends WP_CLI_Command {

	/**
	 * Purge the object cache and varnish cache
	 *
	 *   wp purge
	 */
	public function __invoke( $args, $assoc_args ) {

		$url = get_home_url();
		if ( preg_match( '/http[s]?:\/\/([^\/]+)/i', $url, $matches ) ) {
			$_url = str_replace( $matches[1], GD_VIP, $url );
			$_url = preg_replace( '/https:/i', 'http:', $_url );
			$ret = wp_remote_request( $_url, array(
				'method' => 'BAN',
				'headers' => array(
					'Host'   => $matches[1]
				)
			) );
		}
		wp_cache_flush();
		global $gd_cache_purge;
		$gd_cache_purge->flush_transients();
		update_option( 'gd_system_last_cache_flush', time() );
		WP_CLI::success( __( 'Cache cleared', 'gd_system' ) );
	}
}

WP_CLI::add_command( 'purge', 'GD_System_Plugin_Purge_Command' );
endif;
// @codeCoverageIgnoreEnd
