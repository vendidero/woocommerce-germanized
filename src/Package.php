<?php

namespace Vendidero\Germanized;

use Vendidero\Germanized\Registry\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * @var \WooCommerce_Germanized
	 */
	private static $gzd_instance = null;

	/**
	 * @param \WooCommerce_Germanized $gzd_instance
	 *
	 * @return void
	 */
	public static function init( $gzd_instance ) {
		self::$gzd_instance = $gzd_instance;

		self::container()->get( Bootstrap::class );
	}

	public static function get_version() {
		return self::$gzd_instance->version;
	}

	/**
	 * Loads the dependency injection container for woocommerce blocks.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance.
	 *                       Note: this means all dependencies will be
	 *                       reconstructed.
	 */
	public static function container( $reset = false ) {
		static $container;
		if (
			! $container instanceof Container
			|| $reset
		) {
			$container = new Container();

			// register Bootstrap.
			$container->register(
				Bootstrap::class,
				function ( $container ) {
					return new Bootstrap(
						$container
					);
				}
			);
		}
		return $container;
	}

	public static function get_path( $rel_path = '' ) {
		return self::$gzd_instance->plugin_path( $rel_path );
	}

	public static function get_url( $rel_url = '' ) {
		return self::$gzd_instance->plugin_url( $rel_url );
	}

	public static function get_language_path() {
		return self::$gzd_instance->language_path();
	}

	public static function get_template_path() {
		return self::$gzd_instance->template_path();
	}

	public static function is_pro() {
		return self::$gzd_instance->is_pro();
	}

	public static function load_blocks() {
		$woo_version = \Vendidero\Germanized\PluginsHelper::get_plugin_version( 'woocommerce' );

		return version_compare( $woo_version, '8.2.0', '>=' );
	}
}
