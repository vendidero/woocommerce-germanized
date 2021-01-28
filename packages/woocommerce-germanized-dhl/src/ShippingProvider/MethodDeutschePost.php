<?php

namespace Vendidero\Germanized\DHL\ShippingProvider;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\ShippingProviderMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class MethodDeutschePost {

	/**
	 * @var ShippingProviderMethod|null
	 */
	protected $method = null;

	protected $is_placeholder = false;

	/**
	 * ShippingProviderMethodDHL constructor.
	 *
	 * @param ShippingProviderMethod $method
	 */
	public function __construct( $method ) {
		$this->method = $method;

		if ( is_a( $this->method, '\Vendidero\Germanized\Shipments\ShippingProviderMethodPlaceholder' ) ) {
			$this->is_placeholder = true;
		}
	}

	protected function maybe_prefix_key( $key ) {
		if ( substr( $key, 0, 14 ) !== 'deutsche_post_' ) {
			$key = 'deutsche_post_' . $key;
		}

		return $key;
	}

	protected function is_placeholder() {
		return $this->is_placeholder;
	}

	public function has_option( $key ) {
		$dhl_key = $this->maybe_prefix_key( $key );

		if ( ! $this->is_placeholder() ) {
			return $this->method->has_option( $dhl_key );
		} else {
			// Check if option exists within method instance settings array
			$method_settings = array_keys( Package::get_method_settings() );

			if ( in_array( $dhl_key, $method_settings ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	protected function supports_settings() {
		$supports_settings = ( $this->method->supports( 'instance-settings' ) && $this->method->supports( 'instance-settings-modal' ) ) ? true : false;

		/**
		 * Filter that allows adjusting whether this method supports Deutsche Post custom settings or not.
		 * By default only shipping methods supporting instance-settings and instance-settings-modal are supported.
		 *
		 * @param boolean   $supports_settings Whether or not the method supports custom DHL settings.
		 * @param MethodDHL $method The method instance.
		 *
		 * @since 3.1.1
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_deutsche_post_shipping_provider_method_supports_settings', $supports_settings, $this );
	}

	public function get_option( $key ) {
		$dhl_key = $this->maybe_prefix_key( $key );

		if ( $this->has_option( $key ) ) {

			// Do only use method settings if the method is not a placeholder and method supports settings
			if ( ! $this->is_placeholder() && $this->supports_settings() ) {
				$option_value = $this->method->get_option( $dhl_key );
			} else {
				$option_value = Package::get_setting( $dhl_key );
			}

			if ( strpos( $key, 'enable' ) !== false ) {
				if ( 'yes' === $option_value && ! $this->is_deutsche_post_enabled() ) {
					$option_value = 'no';
				}
			}
		} else {
			$option_value = $this->method->get_option( $key );
		}

		return $option_value;
	}

	public function is_deutsche_post_enabled() {
		return $this->method->is_enabled( 'deutsche_post' );
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
