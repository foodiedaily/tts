<?php
defined( 'WPINC' ) or die;

class GD_SEV_Version_Check {
	private static $instance;

	protected function __construct() {
		self::$instance = $this;
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			new self;
		}
		return self::$instance;
	}

	public function passes() {
		return version_compare( get_bloginfo( 'version' ), '3.9', '>=' );
	}

	public function plugins_loaded() {
		if ( ! $this->passes() ) {
			remove_action( 'init', array( GD_SEV_Plugin::get_instance(), 'init' ) );
  		if ( current_user_can( 'activate_plugins' ) ) {
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}
	}

	public function admin_init() {
		deactivate_plugins( plugin_basename( dirname( dirname( __FILE__ ) ) . '/sev.php' ) );
	}

	public function admin_notices() {
		echo '<div class="updated error"><p>' . __('<strong>Search Engine Visibility</strong> requires WordPress 3.9 or higher, and has thus been <strong>deactivated</strong>. Please update your install and then try again!', 'sev' ) . '</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
