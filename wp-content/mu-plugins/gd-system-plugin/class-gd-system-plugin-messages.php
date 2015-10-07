<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Messages
 * Send growl messages to users
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Messages {

	/**
	 * Construct
	 * Hook any needed actions/filters
	 * @return GD_System_Plugin_Messages
	 */
	public function __construct() {
		// If there are any system messages, show them
		add_action( 'init', array( $this, 'load_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'show_messages' ) );
	}

	/**
	 * Display any system messages to the user
	 * @return void
	 */
	public function load_styles() {

	   // Get the transient
	   if ( !is_user_logged_in() || !current_user_can( 'activate_plugins' ) ) {
		   return;
	   }
	   $transient = get_option( 'gd_system_messages' );

	   // If there are no messages, bail
	   if ( empty( $transient ) ) {
		   return;
	   }

	   // If there are messages, print our style
	   if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		   // @codeCoverageIgnoreStart
		   wp_enqueue_script( 'gd_system_gritter_js', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/js/jquery.gritter.js', array( 'jquery' ) );
		   wp_enqueue_style( 'gd_system_gritter_css', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/css/jquery.gritter.css' );
		   // @codeCoverageIgnoreEnd
	   } else {
		   wp_enqueue_script( 'gd_system_gritter_js', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/js/jquery.gritter.min.js', array( 'jquery' ) );
		   wp_enqueue_style( 'gd_system_gritter_css', GD_SYSTEM_PLUGIN_URL . 'gd-system-plugin/css/jquery.gritter.min.css' );
	   }
	}

	/**
	 * Display any system messages to the user
	 * @return void
	 */
	public function show_messages( ) {

		// Get the transient
		if ( !is_user_logged_in() || !current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$transient = get_option( 'gd_system_messages' );

		// If there are no messages, bail
		if ( empty( $transient ) ) {
			return;
		}

		// Show messages
		foreach ( (array) $transient as $message ) {
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					$.gritter.add( {
						image: "http://img1.wsimg.com/shared/img/1/success-icon.png",
						title: "<?php echo esc_js( __( 'System message', 'gd_system' ) ); ?>",
						text: "<?php echo esc_js( $message ); ?>",
						time: 10 * 1000,
					} );
				} );
			</script>
			<?php
		}

		// If there are no more messages, delete.  Otherwise, save.
		delete_option( 'gd_system_messages' );
	}

	/**
	 * Add a message to be displayed to the user
	 * @param string $message
	 * @return void
	 */
	public function add_message( $message ) {
		$transient = get_option( 'gd_system_messages' );
		if ( empty( $transient ) ) {
			$transient = array();
		}
		$transient[] = $message;
		update_option( 'gd_system_messages', $transient );
	}
}
