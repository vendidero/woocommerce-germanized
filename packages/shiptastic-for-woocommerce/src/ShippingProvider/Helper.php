<?php

namespace Vendidero\Shiptastic\ShippingProvider;

use Vendidero\Shiptastic\Extensions;
use Vendidero\Shiptastic\Interfaces\ShippingProvider;
use Vendidero\Shiptastic\Package;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class Helper {

	/**
	 * The single instance of the class
	 *
	 * @var Helper
	 */
	protected static $_instance = null;

	/**
	 * Stores shipping providers loaded.
	 *
	 * @var Simple[]|null
	 */
	public $shipping_providers = null;

	/**
	 * @var null|\Vendidero\Shiptastic\ShippingProvider\Placeholder[]
	 */
	private $integrations = null;

	/**
	 * Stores shipping providers loaded.
	 *
	 * @var Simple[]|null
	 */
	private $available_shipping_providers = null;

	/**
	 * Main Helper Instance.
	 *
	 * Ensures only one instance of the Shipping Provider Helper is loaded or can be loaded.
	 *
	 * @return Helper Main instance
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
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '1.0.5' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
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
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipping_providers_init' );

		/**
		 * Upon plugin installation, make sure to force reloading shipping providers as
		 * the activate_plugin hook may be fired lately, e.g. after plugins_loaded hooks.
		 * In this case newly introduced shipping providers might not be available while installing plugins.
		 */
		add_action(
			'activate_plugin',
			function () {
				$this->reset_providers();
			}
		);

		add_action(
			'update_option_woocommerce_shiptastic_shipper_address_country',
			function () {
				$this->reset_providers();
			}
		);

		add_action(
			'update_option_woocommerce_default_country',
			function () {
				$this->reset_providers();
			}
		);
	}

	public function reset_providers() {
		$this->shipping_providers = null;
		\Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipping-providers' )->flush();
	}

	/**
	 * @param $name
	 *
	 * @return Placeholder|false
	 */
	public function get_shipping_provider_integration( $name ) {
		$available = $this->get_available_shipping_provider_integrations();

		return array_key_exists( $name, $available ) ? $available[ $name ] : false;
	}

	/**
	 * @return Placeholder[]
	 */
	public function get_available_shipping_provider_integrations( $inactive_only = false ) {
		if ( is_null( $this->integrations ) ) {
			$this->integrations = array();
			$available          = apply_filters( 'woocommerce_shiptastic_available_shipping_provider_integrations', array() );

			foreach ( $available as $key => $placeholder_args ) {
				$this->integrations[ $key ] = new Placeholder( 0, $placeholder_args );
			}
		}

		$filtered_integrations = array();

		foreach ( $this->integrations as $key => $integration ) {
			if ( ! $integration->is_base_country_supported() ) {
				continue;
			} elseif ( $inactive_only && ! empty( $integration->get_extension_name() ) && Extensions::is_provider_integration_active( $integration->get_original_name(), $integration->get_extension_name() ) ) {
				continue;
			}

			$filtered_integrations[ $key ] = $integration;
		}

		return $filtered_integrations;
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
			$classname = '\Vendidero\Shiptastic\ShippingProvider\Simple';

			if ( array_key_exists( $provider->shipping_provider_name, $classes ) ) {
				$classname = $classes[ $provider->shipping_provider_name ];
			}

			$classname = apply_filters( 'woocommerce_shiptastic_shipping_provider_class_name', $classname, $provider->shipping_provider_name, $provider );

			if ( ! class_exists( $classname ) ) {
				$classname = '\Vendidero\Shiptastic\ShippingProvider\Simple';
			}

			$provider = new $classname( $provider );
		}

		if ( ! $provider || ! is_a( $provider, '\Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			return false;
		}

		if ( is_null( $this->shipping_providers ) ) {
			$this->shipping_providers = array();
		}

		$this->shipping_providers[ $provider->get_name() ] = $provider;

		if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipping-providers' ) ) {
			$cache->set( $provider, $provider->get_name() );
		}
	}

	/**
	 * Shipping providers register themselves by returning their main class name through the woocommerce_stc_shipping_provider_integrations filter.
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
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipping_provider_class_names', $class_names );
	}

	public function is_shipping_provider_activated( $name ) {
		/**
		 * Make sure that the plugin has initialised, e.g. during installs of shipping provider
		 */
		if ( ! did_action( 'woocommerce_shiptastic_init' ) ) {
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
			wc_doing_it_wrong( __FUNCTION__, _x( 'Loading shipping service providers should only be triggered after the plugins_loaded action has fully been executed', 'shipments', 'woocommerce-germanized' ), '2.2.3' );
			return array();
		}

		$this->shipping_providers = array();
		$shipping_providers       = WC_Data_Store::load( 'shipping-provider' )->get_shipping_providers();
		$registered_providers     = $this->get_shipping_provider_class_names();

		foreach ( $registered_providers as $k => $provider ) {
			if ( ! array_key_exists( $k, $shipping_providers ) ) {
				$shipping_providers[ $k ] = $provider;
			}
		}

		// For the settings in the backend, and for non-shipping zone methods, we still need to load any registered classes here.
		foreach ( $shipping_providers as $provider_name => $provider_class ) {
			if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipping-providers' ) ) {
				if ( $provider = $cache->get( $provider_name ) ) {
					if ( is_a( $provider, '\Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
						$this->shipping_providers[ $provider_name ] = $provider;

						continue;
					}
				}
			}

			$this->register_shipping_provider( $provider_class );
		}

		/**
		 * This hook fires as soon as shipping providers are loaded.
		 * Additional shipping provider may be registered manually afterwards.
		 *
		 * @param Helper $providers The shipping providers instance
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_load_shipping_providers', $this );

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
	 * Returns all available shipping providers for usage.
	 *
	 * @return Simple|Auto|ShippingProvider[]
	 */
	public function get_available_shipping_providers() {
		if ( is_null( $this->available_shipping_providers ) || is_null( $this->shipping_providers ) ) {
			$this->available_shipping_providers = array();

			foreach ( $this->get_shipping_providers() as $name => $shipping_provider ) {
				if ( $shipping_provider->is_activated() ) {
					$this->available_shipping_providers[ $name ] = $shipping_provider;
				}
			}
		}

		return $this->available_shipping_providers;
	}

	/**
	 * @param string|ShippingProvider $title
	 *
	 * @return false|Simple|Auto|ShippingProvider
	 */
	public function get_shipping_provider_by_title( $title ) {
		if ( is_a( $title, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			$title = $title->get_title();
		}

		$title           = sanitize_title( $title );
		$providers       = $this->get_shipping_providers();
		$the_provider    = false;
		$alternate_match = false;

		foreach ( $providers as $provider ) {
			$provider_title = sanitize_title( $provider->get_title() );

			if ( $provider_title === $title ) {
				$the_provider = $provider;
				break;
			} elseif ( strstr( $provider_title, $title ) ) {
				$alternate_match = $provider;
			}
		}

		if ( ! $the_provider && $alternate_match ) {
			$the_provider = $alternate_match;
		}

		return $the_provider;
	}

	/**
	 * @param string|ShippingProvider $name
	 *
	 * @return false|Simple|Auto|ShippingProvider
	 */
	public function get_shipping_provider( $name ) {
		if ( is_a( $name, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			$name = $name->get_name();
		}

		$providers = $this->get_shipping_providers();

		return ( array_key_exists( $name, $providers ) ? $providers[ $name ] : false );
	}
}
