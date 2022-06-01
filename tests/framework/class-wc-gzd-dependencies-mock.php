<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Dependencies_Mock {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $loadable = true;

	public $plugins_header = array(
		'woocommerce' => array(
			'name'           => 'WooCommerce',
			'tested'         => '',
			'requires'       => '',
			'version'        => '',
			'version_prefix' => 'woocommerce',
		),
	);

	public static function instance( $plugin = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin );
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->plugins = (array) get_option( 'active_plugins', array() );
	}

	/**
	 * Checks if WooCommerce is activated
	 *
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return true;
	}

	public function is_plugin_activated( $plugin ) {

		if ( isset( $this->plugins_header[ $plugin ]['constant'] ) ) {

			if ( ! defined( $this->plugins_header[ $plugin ]['constant'] ) ) {
				return false;
			}
		}

		if ( strpos( $plugin, '.php' ) === false ) {
			$plugin = trailingslashit( $plugin ) . $plugin . '.php';
		}

		return ( in_array( $plugin, $this->plugins, true ) || array_key_exists( $plugin, $this->plugins ) );
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

	public function get_plugin_version( $plugin_slug ) {
		$version = preg_replace( '#(\.0+)+($|-)#', '', get_option( $plugin_slug . '_version', '1.0' ) );

		return $version;
	}

	public function woocommerce_version_supports_crud() {
		return true;
	}

	public function is_loadable() {
		return true;
	}
}
