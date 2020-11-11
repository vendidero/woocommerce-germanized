<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Dependencies {

	/**
	 * Single instance
	 *
	 * @var WC_GZD_Dependencies
	 */
	protected static $_instance = null;

	/**
	 * Whether the plugin is loadable or not.
	 *
	 * @var bool
	 */
	protected $loadable = true;

	/**
	 * @var WooCommerce_Germanized|null
	 */
	public $plugin = null;

	/**
	 * Lazy initiated activated plugins list
	 *
	 * @var null|array
	 */
	protected $active_plugins = null;

	/**
	 * This is the minimum Woo version supported by Germanized
	 *
	 * @var string
	 */
	public $wc_minimum_version_required = '3.9';

	public static function instance( $plugin = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct( $plugin = null ) {

		if ( ! $plugin ) {
			$plugin = WC_germanized();
		}

		$this->plugin = $plugin;

		// Check if WooCommerce is enabled and does not violate min version.
		if ( ! $this->is_woocommerce_activated() || $this->is_woocommerce_outdated() ) {
			$this->loadable = false;

			add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
		}
	}

	public function is_plugin_activated( $plugin_slug ) {
		if ( is_null( $this->active_plugins ) ) {
			$this->active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$this->active_plugins = array_merge( $this->active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}

		if ( strpos( $plugin_slug, '.php' ) === false ) {
			$plugin_slug = trailingslashit( $plugin_slug ) . $plugin_slug . '.php';
		}

		return ( in_array( $plugin_slug, $this->active_plugins ) || array_key_exists( $plugin_slug, $this->active_plugins ) );
	}

	public function get_wc_min_version_required() {
		return $this->wc_minimum_version_required;
	}

	public function get_plugin_version( $plugin_slug ) {
		$version = $this->parse_version( get_option( $plugin_slug . '_version', '1.0' ) );

		return $version;
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

	protected function parse_version( $version ) {
		$version = preg_replace( '#(\.0+)+($|-)#', '', $version );

		// Remove/ignore beta, alpha, rc status from version strings
		$version = trim( preg_replace( '#(beta|alpha|rc)#', ' ', $version ) );

		// Make sure version has at least 2 signs, e.g. 3 -> 3.0
		if ( strlen( $version ) === 1 ) {
			$version = $version . '.0';
		}

		return $version;
	}

	/**
	 * Checks if WooCommerce is activated
	 *
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return $this->is_plugin_activated( 'woocommerce/woocommerce.php' );
	}

	public function is_woocommerce_outdated() {
		$woo_version = get_option( 'woocommerce_db_version' );

		/**
		 * Fallback to default Woo version to prevent issues
		 * for installations which failed the last Woo DB update.
		 */
		if ( ! $woo_version || empty( $woo_version ) ) {
			$woo_version = get_option( 'woocommerce_version' );
		}

		return $this->compare_versions( $this->parse_version( $woo_version ), $this->get_wc_min_version_required(), '<' );
	}

	public function is_element_pro_activated() {
		return $this->is_plugin_activated( 'elementor-pro/elementor-pro.php' );
	}

	public function is_loadable() {
		return $this->loadable;
	}

	public function dependencies_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			global $dependencies;
			$dependencies = $this;

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/views/html-notice-dependencies.php';
		}
	}
}
