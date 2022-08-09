<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Dependencies {

	/**
	 * This is the minimum Woo version supported by Germanized
	 *
	 * @var string
	 */
	public static $woocommerce_minimum_version_required = '3.9';

	public static function is_loadable() {
		return apply_filters( 'woocommerce_gzd_is_loadable', \Vendidero\Germanized\PluginsHelper::is_woocommerce_plugin_active() && ! self::is_woocommerce_outdated() );
	}

	public static function is_woocommerce_outdated() {
		$woo_version = \Vendidero\Germanized\PluginsHelper::get_plugin_version( 'woocommerce' );

		return \Vendidero\Germanized\PluginsHelper::compare_versions( $woo_version, self::get_woocommerce_min_version_required(), '<' );
	}

	public static function get_woocommerce_min_version_required() {
		return self::$woocommerce_minimum_version_required;
	}

	public function is_plugin_activated( $plugin_slug ) {
		return \Vendidero\Germanized\PluginsHelper::is_plugin_active( $plugin_slug );
	}

	/**
	 * This method removes accuration from $ver2 if this version is more accurate than $main_ver
	 */
	public function compare_versions( $main_ver, $ver2, $operator ) {
		$expl_main_ver = explode( '.', $main_ver );
		$expl_ver2     = explode( '.', $ver2 );

		// Check if ver2 string is more accurate than main_ver
		if ( 2 === count( $expl_main_ver ) && count( $expl_ver2 ) > 2 ) {
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
		return $this->is_plugin_activated( 'woocommerce' );
	}

	public function dependencies_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			global $dependencies;
			$dependencies = $this;

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/views/html-notice-dependencies.php';
		}
	}
}
