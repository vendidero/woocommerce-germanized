<?php

namespace Vendidero\TrustedShops;
use Exception;
use WC_TS_Install;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '4.0.11';

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
	    if ( ! self::has_dependencies() ) {

	        if ( ! self::is_integration() ) {
		        add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ), 20 );
	        }

		    return;
        }

		self::init_hooks();
		self::includes();
	}

	public static function install_integration() {
		self::includes();
		include_once self::get_path() . '/includes/class-wc-ts-install.php';

        WC_TS_Install::install_integration();
    }

	public static function install() {
	    self::includes();
		include_once self::get_path() . '/includes/class-wc-ts-install.php';

		WC_TS_Install::install();
	}

	public static function dependency_notice() {
		?>
		<div class="notice notice-error">
			<p><?php _ex( 'Trustbadge Reviews for WooCommerce needs at least WooCommerce version 3.1 to run.', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
		</div>
		<?php
	}

	public static function has_dependencies() {
		return class_exists(  'WooCommerce' ) && version_compare( WC()->version, '3.1', '>=' ) ? true : false;
	}

	public static function is_integration() {
		return class_exists( 'WooCommerce_Germanized' ) ? true : false;
	}

	private static function includes() {
		include_once self::get_path() . '/includes/class-wc-trusted-shops-core.php';
	}

	public static function init_hooks() {}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	private static function define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
