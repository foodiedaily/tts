<?php
/**
 * Plugin Name: WooCommerce Brands
 * Plugin URI: http://woothemes.com/products/brands/
 * Description: Add brands to your products, as well as widgets and shortcodes for displaying your brands.
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Developer: WooThemes
 * Developer URI: http://woothemes.com/
 * Requires at least: 3.3.0
 * Tested up to: 4.3.0
 * Version: 1.4.0
 * Text Domain: wc_brands
 * Domain Path: /languages/
 *
 * Copyright (c) 2015 WooThemes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '8a88c7cbd2f1e73636c331c7a86f818c', '18737' );

if ( is_woocommerce_active() ) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'wc_brands', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * WC_Brands classes
	 **/
	require_once( 'classes/class-wc-brands.php' );

	if ( is_admin() ) {
		require_once( 'classes/class-wc-brands-admin.php' );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links' );
	}

	register_activation_hook( __FILE__, array( 'WC_Brands', 'init_taxonomy' ), 10 );
	register_activation_hook( __FILE__, 'flush_rewrite_rules', 20 );

	/**
	 * Add custom action links on the plugin screen.
	 *
	 * @param	mixed $actions Plugin Actions Links
	 * @return	array
	 */
	function plugin_action_links( $actions ) {

		$custom_actions = array();

		// documentation url if any
		$custom_actions['docs'] = sprintf( '<a href="%s">%s</a>', 'http://docs.woothemes.com/document/wc-brands/', __( 'Docs', 'wc_brands' ) );

		// support url
		$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', 'http://support.woothemes.com/', __( 'Support', 'wc_brands' ) );

		// changelog link
		$custom_actions['changelog'] = sprintf( '<a href="%s" target="_blank">%s</a>', 'http://www.woothemes.com/changelogs/extensions/woocommerce-brands/changelog.txt', __( 'Changelog', 'wc_brands' ) );

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}

	/**
	 * Helper function :: get_brand_thumbnail_url function.
	 *
	 * @access public
	 * @return string
	 */
	function get_brand_thumbnail_url( $brand_id, $size = 'full' ) {
		$thumbnail_id = get_woocommerce_term_meta( $brand_id, 'thumbnail_id', true );

		if ( $thumbnail_id )
			$thumb_src = wp_get_attachment_image_src( $thumbnail_id, $size );
			if ( ! empty( $thumb_src ) ) {
				return current( $thumb_src );
			}
	}

	/**
	 * get_brands function.
	 *
	 * @access public
	 * @param int $post_id (default: 0)
	 * @param string $sep (default: ')
	 * @param mixed '
	 * @param string $before (default: '')
	 * @param string $after (default: '')
	 * @return void
	 */
	function get_brands( $post_id = 0, $sep = ', ', $before = '', $after = '' ) {
		global $post;

		if ( ! $post_id )
			$post_id = $post->ID;

		return get_the_term_list( $post_id, 'product_brand', $before, $sep, $after );
	}
}
