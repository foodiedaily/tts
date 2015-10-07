<?php

/**
 * Copyright 2013 Go Daddy Operating Company, LLC. All Rights Reserved.
 */

// Make sure it's wordpress
if ( !defined( 'ABSPATH' ) )
    die( 'Forbidden' );

/**
 * Class GD_System_Plugin_Admin_Menu
 * Add the Go Daddy system admin menu
 * @version 1.0
 * @author Kurt Payne <kpayne@godaddy.com>
 */
class GD_System_Plugin_Admin_Menu {

	/**
	 * Constructor.
	 * Hook any needed actions/filters
	 * @return void
	 */
	public function __construct() {
		global $gd_system_config;

		// Don't modify these parts of the UI when there's no reseller present
		if ( false == $gd_system_config->missing_gd_config ) {

			// Add a "Flush cache" button to the admin bar
			add_action( 'admin_bar_menu', array( $this, 'admin_menu' ), 100 );
			add_action( 'admin_bar_menu', array( $this, 'add_uservoice' ), 100 );
		}

		// Propagate 'nocache' throughout wp_enqueued* resources for a smoother
		// theme editing experience when using sftp and for general use of nocache
		add_filter( 'script_loader_src', array( $this, 'add_nocache' ) );
		add_filter( 'style_loader_src', array( $this, 'add_nocache' ) );

		// Don't let the user change their domain in WordPress
		add_filter( 'sanitize_option_siteurl', array( $this, 'sanitize_siteurl' ), 10, 2 );
		add_filter( 'sanitize_option_home', array( $this, 'sanitize_home' ), 10, 2 );

		// Purge cache when files are edited through the file editor
		add_action( 'admin_init', array( $this, 'purge_cache_on_file_edits' ) );

		// Hard block any file writes through the editors unless
		// the user has consented
		add_action( 'admin_init', array( $this, 'block_unallowed_file_writes' ), -PHP_INT_MAX );

		// Let users know the file editors can be dangerous
		add_action( 'admin_print_footer_scripts', array( $this, 'file_editor_safety_net' ) );

		// Let users turn off UserVoice for themselves
		add_action( 'personal_options', array( $this, 'add_uservoice_profile_entry' ), 10, 1 );
		add_action( 'personal_options_update', array( $this, 'save_uservoice_profile_setting' ), 10, 1 );
	}

	/**
	 * Add a "Flush Cache" button the admin menu
	 * @param WP_Admin_Bar $admin_bar
	 * @return void
	 */
	public function admin_menu( $admin_bar ) {
		global $gd_system_config;
		$config = $gd_system_config->get_config();

		// Only show to admin users
		if ( is_user_logged_in() && current_user_can( 'activate_plugins' ) ) {

			// Flush cache
			$admin_bar->add_menu( array(
				'parent' => false,
				'id'     => 'gd-system-flush-cache',
				'title'  => __( 'Flush Cache', 'gd_system' ),
				'href'   => esc_url( add_query_arg( array(
					'GD_COMMAND' => 'FLUSH_CACHE',
					'GD_NONCE'   => wp_create_nonce( 'GD_FLUSH_CACHE' ),
				) ) ),
				'meta'   => array()
			) );

			// Gateway / control panel
			// Untestable ... can't reset a constant
			// @codeCoverageIgnoreStart
			$label = __( 'GoDaddy Settings', 'gd_system' );
			if ( 1 !== intval( GD_RESELLER ) ) {
				$label = __( 'Account Settings', 'gd_system' );
			}
			// @codeCoverageIgnoreEnd
			$admin_bar->add_menu( array(
				'parent' => false,
				'id'     => 'gd-system-control-panel',
				'title'  =>  $label,
				'href'   => str_replace( '%pl_id%', defined( 'GD_RESELLER' ) ? GD_RESELLER : '', $config['gateway_url'] ),
				'meta'   => array()
			) );
		}
	}

	/**
	 * Propagate nocache throughout js/css resources
	 * @param type $src
	 * @return string
	 */
	public function add_nocache( $src ) {
		if ( false !== stripos( $_SERVER['REQUEST_URI'], 'nocache' ) ) {
			if ( false === strpos( $src, '?' ) ) {
				$src .= '?nocache=1';
			} else {
				$src .= '&nocache=1';
			}
		}
		return $src;
	}

	/**
	 * Add UserVoice
	 * @return void
	 */
	public function add_uservoice() {
		$show_for_user = '1' !== get_user_meta( get_current_user_id(), '_user_hide_uservoice', true ) ? true : false;
		if ( $this->is_uservoice_active() && $show_for_user ) {
			include_once( '/web/conf/gd-uservoice.php' );
			do_action( 'gd_system_user_voice' );
		}
	}

	/**
	 * Add UserVoice option to the profile editor
	 * @return void
	 */
	public function add_uservoice_profile_entry( $user ) {
		if ( !$this->is_uservoice_active() ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$user   = get_userdata( $user->ID );
		$value  = $user->_user_hide_uservoice;

		?>
		<table class="form-table">
			<tr>
				<th><?php _e( 'UserVoice', 'gd_system' ); ?></th>
				<td>
					<input type="checkbox" name="_user_hide_uservoice" id="_user_hide_uservoice" value="1" <?php checked( '1', $value, true ); ?> />
					<label for="_user_hide_uservoice"><?php _e( 'Hide the UserVoice feedback button from the Dashboard', 'gd_system' ); ?></label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Saves the UserVoice setting when a profile is updated
	 * @param  int $user_id The UserID of the user being saved
	 * @return void
	 */
	public function save_uservoice_profile_setting( $user_id ) {
		if ( !$this->is_uservoice_active() ) {
			return;
		}

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( is_admin() && 'en_' == substr( strtolower( get_locale() ), 0, 3 ) ) {
			$user_setting = isset( $_POST['_user_hide_uservoice'] ) && '1' === $_POST['_user_hide_uservoice'] ? '1' : '0';
			update_user_meta( $user_id, '_user_hide_uservoice', $user_setting );
		}
	}

	/**
	 * Flush the cache when the user updates their theme / plugin through the WordPress file editor
	 * @return void
	 */
	public function purge_cache_on_file_edits() {
		global $gd_cache_purge;
		if ( preg_match( '/(theme|plugin)-editor\.php/i', $_SERVER['REQUEST_URI'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] && 'update' == $_REQUEST['action'] ) {
			$gd_cache_purge->ban_cache();
		}
	}

	/**
	 * Safety net on the file editor
	 * @return void
	 */
	public function file_editor_safety_net() {
		if ( ( 'theme-editor' === get_current_screen()->id || 'plugin-editor' === get_current_screen()->id ) && 'yes' !== get_site_option( 'gd_file_editor_enabled' ) ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$("div.wrap").html($("#file-editor-disabled-message").html());
					$("#file-editor-disabled-message").remove();
				})(jQuery);
			</script>
			<div id="file-editor-disabled-message">
				<p><?php _e( 'For your security, we’ve disabled WordPress’ built-in file editor by default.', 'gd_system' ); ?></p>
				<p><?php _e( 'If you enable editing, all plugin and theme files become editable.', 'gd_system' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( array( 'GD_COMMAND' => 'ENABLE_EDITORS', 'GD_NONCE' => wp_create_nonce( 'GD_ENABLE_EDITORS' ) ) ) ); ?>" class="button-primary"><?php _e( 'Enable Editing', 'gd_system' ); ?></a>
			</div>
			<?php
		}
	}

	/**
	 * This is designed to block attackers.  If the user hasn't clicked "enable editing" then do not allow it
	 * @return void
	 */
	public function block_unallowed_file_writes() {
		if ( preg_match( '/(theme|plugin)-editor\.php/i', $_SERVER['REQUEST_URI'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] && 'update' == $_REQUEST['action'] && 'yes' !== get_site_option( 'gd_file_editor_enabled' ) ) {
			wp_die( __( 'File editing is not enabled on this site', 'gd_system' ) );
		}
	}

	/**
	* Don't break your site by changing the domain in WordPress
	*
	* @param  string $value
	* @param  string $option
	*
	* @return string
	*/
	public function sanitize_home( $value, $option ) {

		global $gd_system_config;

		$config = $gd_system_config->get_config();

		// Current site
		$my_proto = is_ssl() ? 'https' : 'http';

		$my_host = ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;

		// URL to redirect to
		$url = str_replace(
			array(
				'%domain%', // Replace with HTTP host
				'%pl_id%', // Replace with Reseller ID
			),
			array(
				$my_host,
				defined( 'GD_RESELLER' ) ? GD_RESELLER : null,
			),
			! empty( $config['cname_link'] ) ? $config['cname_link'] : null
		);

		// Proposed changes
		$new_proto = strtolower( parse_url( $value, PHP_URL_SCHEME ) );

		$new_host = parse_url( $value, PHP_URL_HOST );

		// Compare
		if ( $my_proto !== $new_proto || $my_host !== $new_host ) {

			$value = get_option( $option );

			if ( function_exists( 'add_settings_error' ) ) {

				add_settings_error(
					'homeurl',
					'invalid_homeurl',
					sprintf(
						__( 'Sorry, but you have to <a href="%s">change your domain name here</a>.', 'gd_system' ),
						$url
					)
				);

			}

		}

		return $value;

	}

	/**
	* Don't break your site by changing the domain in WordPress
	*
	* @param  string $value
	* @param  string $option
	*
	* @return string
	*/
	public function sanitize_siteurl( $value, $option ) {

		global $gd_system_config;

		$config = $gd_system_config->get_config();

		// URL to redirect to
		$url = str_replace(
			array(
				'%domain%', // Replace with HTTP host
				'%pl_id%', // Replace with Reseller ID
			),
			array(
				! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null,
				defined( 'GD_RESELLER' ) ? GD_RESELLER : null,
			),
			! empty( $config['cname_link'] ) ? $config['cname_link'] : null
		);

		// Compare
		if ( $value !== get_option( $option ) ) {

			$value = get_option( $option );

			if ( function_exists( 'add_settings_error' ) ) {

				add_settings_error(
					'siteurl',
					'invalid_siteurl',
					sprintf(
						__( 'Sorry, but you have to <a href="%s">change your domain name here</a>.', 'gd_system' ),
						$url
					)
				);

			}

		}

		return $value;

	}

	/**
	 * Checks if UserVoice is set to be active
	 * @return boolean If UserVoice is set to be active and the file exists to be displayed
	 */
	public function is_uservoice_active() {
		global $gd_system_config;
		$conf = $gd_system_config->get_config();

		if ( isset( $conf['uservoice_active'] ) && 1 == $conf['uservoice_active'] && file_exists( '/web/conf/gd-uservoice.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}

		return apply_filters( 'gd_is_uservoice_active', $is_active );
	}
}
