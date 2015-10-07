<?php
defined( 'WPINC' ) or die;

class GD_SEV_Plugin {
	private static $instance;
	private $base;
	private $page;
	private $replacements;
	private $do_replacements = false;
	const OPTION = 'sev';
	const JSON_OPTION = 'gd_sev_json';
	const NONCE = 'sev';
	const API_KEY = '48cc6407-9c63-45b8-8bc2-c85bab11cd6b';
	const API_BASE = 'https://searchenginevisibility.secureserver.net/api/';
	const INTEGRATION_BASE = 'https://searchenginevisibility.secureserver.net/integration_launch/';

	protected function __construct( $__FILE__ ) {
		self::$instance = $this;
		$this->__FILE__ = $__FILE__;
		$this->base = dirname( dirname( __FILE__ ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'gd_sev_integration_base', array( $this, 'filter_integration_for_resellers' ) );

		add_option( self::JSON_OPTION, '{}', null, false );
	}

	public static function get_instance( $__FILE__ = null ) {
		if ( ! isset( self::$instance ) ) {
			new self( $__FILE__ );
		}
		return self::$instance;
	}

	public function get_api_key() {
		return apply_filters( 'gd_sev_api_key', self::API_KEY );
	}

	public function get_integration_base() {
		return apply_filters( 'gd_sev_integration_base', self::INTEGRATION_BASE );
	}

	public function filter_integration_for_resellers( $integration ) {
		if ( $this->get_reseller_id() === 1 ) {
			$integration = 'https://searchenginevisibility.godaddy.com/integration_launch/';
		}
		return $integration;
	}

	public function get_locale() {
		$locale = get_locale();
		$locale = empty( $locale ) ? 'en_US' : $locale;
		$locale = str_replace( '_', '-', $locale );
		return $locale;
	}

	public function get_reseller_id() {
		if ( defined( 'GD_RESELLER' ) ) {
			return absint( GD_RESELLER );
		} else {
			return 0;
		}
	}

	public function get_api_base() {
		return apply_filters( 'gd_sev_api_base', self::API_BASE );
	}

	public function get_url() {
		return plugin_dir_url( $this->__FILE__ );
	}

	public function get_path() {
		return plugin_dir_path( $this->__FILE__ );
	}

	public function normalize_url($url) {

	  if( $this->startsWith($url, 'https://') ) {
      $url = substr($url, 8);
    }

    if( $this->startsWith($url, 'http://') ) {
      $url = substr($url, 7);
    }

    if( $this->startsWith($url, 'www.') ) {
      $url = substr($url, 4);
    }

    if( $this->endsWith($url, '/') ) {
      $url = substr($url, 0, (strlen($url) - 1));
    }

    return $url;
	}

	public function startsWith($haystack, $needle)
  {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public function endsWith($haystack, $needle)
  {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
  }

	public function init() {
		// Initialize translations
		load_plugin_textdomain( 'sev', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );

		if ( isset( $_GET['sev-flush-edits'] ) && wp_hash( 'sev-token' ) === $_GET['sev-flush-edits'] ) {
			nocache_headers();
			$this->update_json();
		}

		$json = $this->get_json();
		if ( $json ) {
			$this_url  = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$this_url = $this->normalize_url($this_url);
			foreach ( (array) $json->pages as $page ) {
			  $page_url = $this->normalize_url($page->url);
				if ( $page_url === $this_url ) {
					$this->page = $page;
					$this->replacements = new GD_SEV_Plugin_Replacements( $this->page->edits );
					$this->do_replacements = true;
					break;
				}
			}
		}
		if ( isset( $_GET['sev-no-edits'] ) ) {
			$this->do_replacements = false;
		}
	}

	public function get_option( $key, $default = null ) {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[$key] ) ) {
			return $default;
		} else {
			return $option[$key];
		}
	}

	public function set_option( $key, $value ) {
		$option = get_option( self::OPTION );
		is_array( $option ) || $option = array();
		$option[$key] = $value;
		update_option( self::OPTION, $option );
	}

	public function admin_menu() {
		$hook = add_dashboard_page( __( 'SEO', 'sev' ), __( 'SEO' ), 'manage_options', 'sev-optimize-seo', array( $this, 'admin_page' ) );
		add_action( 'load-' . $hook, array( $this, 'load_admin_page' ) );
	}

	public function load_admin_page() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_page_scripts' ) );
	}

	public function admin_page_scripts() {
		wp_enqueue_style( 'gd-sev-styles', $this->get_url() . 'css/sev.css', null, '0.2' );
	}

	public function admin_page() {
		include( $this->get_path() . 'tmpl/admin.php' );
		$this->update_json();
	}

	public function get_json() {
		$json = get_option( self::JSON_OPTION );
		$json = json_decode( $json );
		if ( ! json_last_error() ) {
			return $json;
		}
		return false;
	}

	public function set_json( $json ) {
		if ( false !== $json ) {
			$return = update_option( self::JSON_OPTION, $json );
			if ( $return && class_exists( 'GD_System_Plugin_Cache_Purge' ) && method_exists( 'GD_System_Plugin_Cache_Purge', 'do_ban_cache' ) ) {
				GD_System_Plugin_Cache_Purge::do_ban_cache();
			}
			return $return;
		} else {
			return false;
		}
	}

	public function update_json() {
		$json = $this->fetch_json();
		return $this->set_json( $json );
	}

	public function fetch_json() {
		$http = wp_remote_post( $this->get_api_base() . 'edits/', array(
			'body' => json_encode( (object) array(
				'domain' => parse_url( home_url(), PHP_URL_HOST ),
				'api_key' => $this->get_api_key(),
			)),
			'sslverify' => false,
		));
		if ( is_wp_error( $http ) ) {
			return false;
		} else {
			return wp_remote_retrieve_body( $http );
		}
	}

	public function template_redirect() {
		if ( $this->do_replacements ) {
		  ob_start( array( $this, 'ob' ) );
		}
	}

	public function ob( $content ) {
	  foreach ( (array) $this->replacements->get_replacements() as $r ) {
			$content = $r->replace( $content );
		}
		return $content;
	}
}
