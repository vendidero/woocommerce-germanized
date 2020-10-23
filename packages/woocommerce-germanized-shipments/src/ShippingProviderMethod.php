<?php

namespace Vendidero\Germanized\Shipments;
use Exception;
use WC_Order;
use WC_Customer;
use WC_DateTime;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class ShippingProviderMethod {

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method = false;

	protected $instance_form_fields = array();

	/**
	 * @param WC_Customer $customer
	 */
	public function __construct( $method ) {
		$this->method = $method;

		$this->init();
	}

	public static function get_admin_settings() {
		/**
		 * Filter to adjust admin settings added to the shipment method instance specifically for shipping providers.
		 *
		 * @param array $settings Admin setting fields.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_admin_settings', array(
			'shipping_provider_title' => array(
				'title'       => _x( 'Shipping Provider Settings', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'title',
				'default'     => '',
				'description' => _x( 'Adjust shipping provider settings used for managing shipments.', 'shipments', 'woocommerce-germanized' ),
			),
			'shipping_provider' => array(
				'title'       => _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'select',
				/**
				 * Filter to adjust default shipping provider pre-selected within shipping provider method settings.
				 *
				 * @param string $provider_name The shipping provider name e.g. dhl.
				 *
				 * @since 3.0.6
				 * @package Vendidero/Germanized/Shipments
				 */
				'default'     => apply_filters( 'woocommerce_gzd_shipping_provider_method_default_provider', '' ),
				'options'     => wc_gzd_get_shipping_provider_select(),
				'description' => _x( 'Choose a shipping provider which will be selected by default for an eligible shipment.', 'shipments', 'woocommerce-germanized' ),
			),
		) );
	}

	protected function init() {
		$this->instance_form_fields               = $this->get_admin_settings();
		$this->get_method()->instance_form_fields = array_merge( $this->get_method()->instance_form_fields, $this->instance_form_fields );
	}

	/**
	 * Returns the Woo WC_Shipping_Method original object
	 *
	 * @return object|WC_Shipping_Method
	 */
	public function get_method() {
		return $this->method;
	}

	public function get_id() {
		return $this->method->id;
	}

	public function get_instance_id() {
		return $this->method->get_instance_id();
	}

	public function has_option( $key ) {
		$fields     = $this->instance_form_fields;
		$key        = $this->maybe_prefix_key( $key );
		$has_option = array_key_exists( $key, $fields ) ? true : false;

		/**
		 * Filter that allows checking whether a shipping provider method has a specific option or not.
		 *
		 * @param boolean                $has_option Whether or not the option exists.
		 * @param string                 $key The setting key.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_prefix', $has_option, $key, $this );
	}

	public function is_enabled( $provider ) {
		return ( $this->get_provider() === $provider ) ? true : false;
	}

	public function get_provider() {
		$provider_slug = $this->method ? $this->method->get_option( 'shipping_provider' ) : '';
		$id            = sanitize_key( $this->get_id() );

		if ( ! empty( $provider_slug ) ) {
			if ( $provider = wc_gzd_get_shipping_provider( $provider_slug ) ) {

				if ( ! $provider->is_activated() ) {
					$provider_slug = '';
				}
			}
		}

		if ( empty( $provider_slug ) ) {
			$provider_slug = wc_gzd_get_default_shipping_provider();
		}

		/**
		 * Filter that allows adjusting the shipping provider chosen for a specific shipping method.
		 *
		 * @param string                 $provider_slug The shipping provider.
		 * @param string                 $method_id The shipping method id.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		$provider_slug = apply_filters( 'woocommerce_gzd_shipping_provider_method_provider', $provider_slug, $this->get_id(), $this );

		/**
		 * Filter that allows choosing a shipping provider for a specific shipping method.
		 *
		 * The dynamic portion of this hook, `$id` refers to the shipping method id.
		 *
		 * Example hook name: `woocommerce_gzd_shipping_provider_method_flat_rate_provider`
		 *
		 * @param string                 $provider_slug The shipping provider name to be used.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "woocommerce_gzd_shipping_provider_method_{$id}_provider", $provider_slug, $this );
	}

	public function get_provider_instance() {
		$provider_slug = $this->get_provider();

		if ( ! empty( $provider_slug ) ) {
			return wc_gzd_get_shipping_provider( $provider_slug );
		}

		return false;
	}

	protected function maybe_prefix_key( $key ) {
		$fields  = $this->instance_form_fields;
		$prefix  = 'shipping_provider_';
		$new_key = $key;

		// Do only prefix if the key does not yet exist.
		if ( ! array_key_exists( $new_key, $fields ) ) {
			if ( substr( $key, 0, ( strlen( $prefix ) - 1 ) ) !== $prefix ) {
				$new_key = $prefix . $key;
			}
		}

		/**
		 * Filter that allows prefixing the setting key used for a shipping provider method.
		 *
		 * @param string                 $new_key The prefixed setting key.
		 * @param string                 $key The original setting key.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_prefix', $new_key, $key, $this );
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = $this->method->get_option( $key );

		/**
		 * Filter that allows adjusting the setting value belonging to a certain shipping provider method.
		 *
		 * @param mixed                  $option_value The option value.
		 * @param string                 $key The prefixed setting key.
		 * @param ShippingProviderMethod $method The method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_value', $option_value, $key, $this );
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {

		if ( method_exists( $this->method, $method ) ) {
			return call_user_func_array( array( $this->method, $method ), $args );
		}

		return false;
	}
}
