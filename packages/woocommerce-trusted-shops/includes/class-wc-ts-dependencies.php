<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_TS_Dependencies {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $loadable = true;

	public $plugins = array();

	public $plugins_required = array(
		'woocommerce' => array( 'version' => '2.4', 'version_prefix' => 'woocommerce', 'name' => 'WooCommerce' ),
	);

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, _x( 'Cheatin&#8217; huh?', 'trusted-shops', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, _x( 'Cheatin&#8217; huh?', 'trusted-shops', 'woocommerce-germanized' ), '1.0' );
	}
	
	public function __construct() {

		$this->plugins = (array) get_option( 'active_plugins', array() );
		
		if ( is_multisite() ) {
			$this->plugins = array_merge( $this->plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		foreach ( $this->plugins_required as $plugin => $data ) {

			if ( ! $this->is_plugin_activated( $plugin ) || $this->is_plugin_outdated( $plugin ) ) {
				add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
				$this->loadable = false;
			}
		}
	}

	public function get_plugin_version( $plugin_slug ) {
		return get_option( $plugin_slug . '_version' );
	}

	public function is_plugin_outdated( $plugin ) {
		$required = ( isset( $this->plugins_required[ $plugin ] ) ? $this->plugins_required[ $plugin ] : false );

		if ( ! $required ) {
			return false;
        }

		if ( version_compare( $this->get_plugin_version( $required[ 'version_prefix' ] ), $required[ 'version' ], "<" ) ) {
			return true;
        }

		return false;
	}

	public function is_plugin_activated( $plugin ) {

		if ( strpos( $plugin, '.php' ) === false ) {
			$plugin = trailingslashit( $plugin ) . $plugin . '.php';
		}

		return in_array( $plugin, $this->plugins ) || array_key_exists( $plugin, $this->plugins );
	}

	/**
	 * This method removes accuration from $ver2 if this version is more accurate than $main_ver
	 */
	public function compare_versions( $main_ver, $ver2, $operator ) {

		$expl_main_ver = explode( '.', $main_ver );
		$expl_ver2     = explode( '.', $ver2 );

		// Check if ver2 string is more accurate than main_ver
		if ( sizeof( $expl_main_ver ) == 2 && sizeof( $expl_ver2 ) > 2 ) {
			$new_ver_2 = array_slice( $expl_ver2, 0, 2 );
			$ver2      = implode( '.', $new_ver_2 );
		}

		return version_compare( $main_ver, $ver2, $operator );
	}

	/**
	 * Checks if WooCommerce is activated
	 *  
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return $this->is_plugin_activated( 'woocommerce/woocommerce.php' );
	}

	public function is_wpml_activated() {
		return ( $this->is_plugin_activated( 'sitepress-multilingual-cms/sitepress.php' ) && $this->is_plugin_activated( 'woocommerce-multilingual/wpml-woocommerce.php' ) );
	}

	public function woocommerce_version_supports_crud() {
		return ( $this->compare_versions( $this->get_plugin_version( 'woocommerce' ), '2.7', '>=' ) );
	}

	public function is_loadable() {
		return $this->loadable;
	}

	public function dependencies_notice() {
		global $dependencies;
		$dependencies = $this;

		include_once( 'admin/views/html-notice-dependencies.php' );
	}

}

WC_TS_Dependencies::instance();
