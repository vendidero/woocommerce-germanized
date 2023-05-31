<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;
use Vendidero\Germanized\Shipments\Package;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class Helper {

	/**
	 * The single instance of the class
	 *
	 * @var Helper
	 * @since 1.0.5
	 */
	protected static $_instance = null;

	/**
	 * Stores shipping providers loaded.
	 *
	 * @var Simple[]|null
	 */
	public $shipping_providers = null;

	/**
	 * Main Helper Instance.
	 *
	 * Ensures only one instance of the Shipping Provider Helper is loaded or can be loaded.
	 *
	 * @return Helper Main instance
	 * @since 1.0.5
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '1.0.5' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, 'Unserializing instances of this class is forbidden.', '1.0.5' );
	}

	/**
	 * Initialize.
	 */
	public function __construct() {
		/**
		 * This action fires as soon as the shipping provider wrapper instance is loaded.
		 *
		 * @since 1.0.5
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipping_providers_init' );
	}

	/**
	 * Register a shipping provider.
	 *
	 * @param ShippingProvider|string $provider Either the name of the provider's class, or an instance of the provider's class.
	 *
	 * @return bool|void
	 */
	public function register_shipping_provider( $provider ) {
		$classes = $this->get_shipping_provider_class_names();

		if ( ! is_object( $provider ) ) {
			if ( ! class_exists( $provider ) ) {
				return false;
			}

			$provider = new $provider();
		} else {
			$classname = '\Vendidero\Germanized\Shipments\ShippingProvider\Simple';

			if ( array_key_exists( $provider->shipping_provider_name, $classes ) ) {
				$classname = $classes[ $provider->shipping_provider_name ];
			}

			$classname = apply_filters( 'woocommerce_gzd_shipping_provider_class_name', $classname, $provider->shipping_provider_name, $provider );

			if ( ! class_exists( $classname ) ) {
				$classname = '\Vendidero\Germanized\Shipments\ShippingProvider\Simple';
			}

			$provider = new $classname( $provider );
		}

		if ( ! $provider || ! is_a( $provider, '\Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			return false;
		}

		if ( is_null( $this->shipping_providers ) ) {
			$this->shipping_providers = array();
		}

		$this->shipping_providers[ $provider->get_name() ] = $provider;
	}

	/**
	 * Shipping providers register themselves by returning their main class name through the woocommerce_gzd_shipping_provider_integrations filter.
	 *
	 * @return array
	 */
	public function get_shipping_provider_class_names() {
		$class_names = array();

		/**
		 * This filter may be used to register additional shipping providers
		 * by adding a unique name as key and the classname to be loaded as value of the array.
		 *
		 * @param array $shipping_providers The shipping provider array
		 *
		 * @since 1.0.5
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_class_names', $class_names );
	}

	public function is_shipping_provider_activated( $name ) {
		/**
		 * Make sure that the plugin has initialised, e.g. during installs of shipping provider
		 */
		if ( ! did_action( 'woocommerce_gzd_shipments_init' ) ) {
			Package::init();
		}

		return WC_Data_Store::load( 'shipping-provider' )->is_activated( $name );
	}

	/**
	 * Loads all shipping providers which are hooked in.
	 *
	 * @return ShippingProvider[]
	 */
	public function load_shipping_providers() {
		if ( ! did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
			wc_doing_it_wrong( __FUNCTION__, _x( 'Loading shipping providers should only be triggered after the plugins_loaded action has fully been executed', 'shipments', 'woocommerce-germanized' ), '2.2.3' );
			return array();
		}

		$this->shipping_providers = array();

		$shipping_providers   = WC_Data_Store::load( 'shipping-provider' )->get_shipping_providers();
		$registered_providers = $this->get_shipping_provider_class_names();

		foreach ( $registered_providers as $k => $provider ) {
			if ( ! array_key_exists( $k, $shipping_providers ) ) {
				$shipping_providers[ $k ] = $provider;
			}
		}

		// For the settings in the backend, and for non-shipping zone methods, we still need to load any registered classes here.
		foreach ( $shipping_providers as $provider_name => $provider_class ) {
			$this->register_shipping_provider( $provider_class );
		}

		/**
		 * This hook fires as soon as shipping providers are loaded.
		 * Additional shipping provider may be registered manually afterwards.
		 *
		 * @param Helper $providers The shipping providers instance
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_load_shipping_providers', $this );

		// Return loaded methods.
		return $this->get_shipping_providers();
	}

	/**
	 * Returns all registered shipping providers for usage.
	 *
	 * @return Simple|Auto|ShippingProvider[]
	 */
	public function get_shipping_providers() {
		if ( is_null( $this->shipping_providers ) ) {
			$this->load_shipping_providers();
		}

		if ( is_null( $this->shipping_providers ) ) {
			return array();
		}

		return $this->shipping_providers;
	}

	/**
	 * @param $name
	 *
	 * @return false|Simple|Auto|ShippingProvider
	 */
	public function get_shipping_provider( $name ) {
		$providers = $this->get_shipping_providers();

		return ( array_key_exists( $name, $providers ) ? $providers[ $name ] : false );
	}
}
