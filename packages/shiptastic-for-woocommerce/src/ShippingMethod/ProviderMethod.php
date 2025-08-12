<?php

namespace Vendidero\Shiptastic\ShippingMethod;

use Vendidero\Shiptastic\Interfaces\LabelConfigurationSet;
use Vendidero\Shiptastic\Interfaces\ShippingProvider;
use Vendidero\Shiptastic\Labels\ConfigurationSetTrait;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

class ProviderMethod implements LabelConfigurationSet {

	use ConfigurationSetTrait;

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method = null;

	protected $id = '';

	protected $instance_id = 0;

	protected $is_placeholder = false;

	/**
	 * @var null|ShippingProvider
	 */
	protected $provider = null;

	/**
	 * @param WC_Shipping_Method|mixed $method
	 */
	public function __construct( $method ) {
		if ( is_a( $method, 'WC_Shipping_Method' ) ) {
			$this->method      = $method;
			$this->id          = $this->method->id;
			$this->instance_id = $this->method->get_instance_id();
		} elseif ( is_array( $method ) ) {
			$method = wp_parse_args(
				$method,
				array(
					'id'          => '',
					'instance_id' => 0,
				)
			);

			$this->is_placeholder = true;
			$this->id             = $method['id'];
			$this->instance_id    = $method['instance_id'];
		}
	}

	public function get_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->id;
		} else {
			return '';
		}
	}

	public function get_instance_id() {
		if ( ! $this->is_placeholder() ) {
			return $this->method->get_instance_id();
		} else {
			return 0;
		}
	}

	/**
	 * Returns the Woo WC_Shipping_Method original object
	 *
	 * @return WC_Shipping_Method|null
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * @return false|ShippingProvider
	 */
	public function get_shipping_provider_instance() {
		if ( $this->is_builtin_method() ) {
			return $this->method->get_shipping_provider();
		}

		if ( is_null( $this->provider ) ) {
			$provider = $this->get_shipping_provider();

			if ( ! empty( $provider ) ) {
				$this->provider = wc_stc_get_shipping_provider( $provider );
			}
		}

		return $this->provider ? $this->provider : false;
	}

	public function is_builtin_method() {
		if ( is_a( $this->method, '\Vendidero\Shiptastic\ShippingMethod\ShippingMethod' ) ) {
			return true;
		}

		return false;
	}

	public function get_shipping_provider() {
		if ( $this->is_builtin_method() ) {
			$provider_slug = $this->method->get_shipping_provider()->get_name();
		} else {
			$provider_slug = $this->get_prop( 'shipping_provider' );
		}

		/**
		 * Filter that allows adjusting the shipping provider chosen for a specific shipping method.
		 *
		 * @param string $provider_slug The shipping provider.
		 * @param string $method_id The shipping method id.
		 * @param ProviderMethod $method The method instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipping_provider_method_provider', $provider_slug, $this->get_id(), $this );
	}

	public function set_shipping_provider( $shipping_provider_name ) {
		$this->set_prop( 'shipping_provider', $shipping_provider_name );
		$this->provider = null;
	}

	public function has_shipping_provider( $shipping_provider_name ) {
		if ( ! is_array( $shipping_provider_name ) ) {
			$shipping_provider_name = array( $shipping_provider_name );
		}

		return in_array( $this->get_shipping_provider(), $shipping_provider_name, true );
	}

	public function get_prop( $key, $context = 'view' ) {
		$default = '';

		if ( 'configuration_sets' === $key ) {
			$default = array();
		} elseif ( 'shipping_provider' === $key ) {
			$default = wc_stc_get_default_shipping_provider();
		}

		if ( ! $this->is_placeholder() && ! MethodHelper::method_is_excluded( $this->get_id() ) ) {
			$value = $this->supports_instance_settings() ? $this->method->get_instance_option( $key, $default ) : $this->method->get_option( $key, $default );
		} else {
			$value = $default;
		}

		if ( is_array( $default ) ) {
			$value = array_filter( (array) $value );
		}

		return $value;
	}

	public function set_prop( $key, $value ) {
		if ( ! $this->is_placeholder() && ! MethodHelper::method_is_excluded( $this->get_id() ) ) {
			if ( $this->supports_instance_settings() ) {
				if ( empty( $this->method->instance_settings ) ) {
					$this->method->init_instance_settings();
				}

				if ( 'configuration_sets' === $key ) {
					$this->method->instance_settings[ $key ] = array_filter( (array) $value );
				} else {
					$this->method->instance_settings[ $key ] = $value;
				}
			} else {
				if ( empty( $this->method->settings ) ) {
					$this->method->init_settings();
				}

				if ( 'configuration_sets' === $key ) {
					$this->method->settings[ $key ] = array_filter( (array) $value );
				} else {
					$this->method->settings[ $key ] = $value;
				}
			}
		}
	}

	protected function get_configuration_set_setting_type() {
		return 'shipping_method';
	}

	protected function supports_instance_settings() {
		if ( $this->is_placeholder() ) {
			return false;
		} else {
			$supports_settings = ( $this->method->supports( 'instance-settings' ) ) ? true : false;

			return apply_filters( 'woocommerce_shiptastic_shipping_provider_method_supports_instance_settings', $supports_settings, $this );
		}
	}

	public function is_placeholder() {
		return true === $this->is_placeholder;
	}

	protected function get_hook_prefix() {
		$prefix = 'woocommerce_shiptastic_shipping_provider_method_';

		return $prefix;
	}

	public function get_option( $key ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::get_option()', '3.0.0' );

		return $this->get_prop( $key );
	}

	public function set_provider( $shipping_provider_name ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::set_provider()', '3.0.0' );

		$this->set_shipping_provider( $shipping_provider_name );
	}

	public function get_provider() {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::get_provider()', '3.0.0' );

		return $this->get_shipping_provider();
	}

	public function has_option( $key ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::has_option()', '3.0.0' );

		return false;
	}

	public function is_provider_enabled( $provider ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::is_provider_enabled()', '3.0.0' );

		return ( $this->get_provider() === $provider ) ? true : false;
	}

	public function setting_belongs_to_provider( $setting_key, $provider = '' ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::setting_belongs_to_provider()', '3.0.0' );

		return false;
	}

	public static function get_admin_settings() {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::get_admin_settings()', '3.0.0' );

		return array();
	}

	public function get_provider_instance() {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::get_provider_instance()', '3.0.0' );

		return $this->get_shipping_provider_instance();
	}

	public function get_fallback_setting_value( $setting_key ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\ShippingProvider\Method::get_fallback_setting_value()', '3.0.0' );

		return '';
	}
}
