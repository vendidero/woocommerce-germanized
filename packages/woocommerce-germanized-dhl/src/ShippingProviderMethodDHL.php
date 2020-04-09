<?php

namespace Vendidero\Germanized\DHL;

use Vendidero\Germanized\Shipments\ShippingProviderMethod;
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
class ShippingProviderMethodDHL {

	/**
	 * @var ShippingProviderMethod|null
	 */
	protected $method = null;

	protected $preferred_services = null;

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
		if ( substr( $key, 0, 4 ) !== 'dhl_' ) {
			$key = 'dhl_' . $key;
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
		 * Filter that allows adjusting whether this method supports DHL custom settings or not.
		 * By default only shipping methods supporting instance-settings and instance-settings-modal are supported.
		 *
		 * @param boolean                   $supports_settings Whether or not the method supports custom DHL settings.
		 * @param ShippingProviderMethodDHL $method The method instance.
		 *
		 * @since 3.1.1
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_shipping_provider_method_supports_settings', $supports_settings, $this );
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
				if ( 'yes' === $option_value && ! $this->is_dhl_enabled() ) {
					$option_value = 'no';
				}
			}
		} else {
			$option_value = $this->method->get_option( $key );
		}

		return $option_value;
	}

	public function is_dhl_enabled() {
		return $this->method->is_enabled( 'dhl' );
	}

	public function is_packstation_enabled() {
		return $this->get_option( 'dhl_parcel_pickup_packstation_enable' ) === 'yes' ? true : false;
	}

	public function is_postoffice_enabled() {
		return $this->get_option( 'dhl_parcel_pickup_postoffice_enable' ) === 'yes' ? true : false;
	}

	public function is_parcelshop_enabled() {
		return $this->get_option( 'dhl_parcel_pickup_parcelshop_enable' ) === 'yes' ? true : false;
	}

	public function get_enabled_preferred_services() {
		if ( is_null( $this->preferred_services ) ) {
			$services                 = wc_gzd_dhl_get_services();
			$this->preferred_services = array();

			foreach ( $services as $service ) {

				if ( strpos( $service, 'Preferred' ) === false ) {
					continue;
				}

				if ( $this->get_option( 'dhl_' . $service . '_enable' ) === 'yes' ) {
					$this->preferred_services[] = $service;
				}
			}
		}

		return $this->preferred_services;
	}

	public function is_preferred_service_enabled( $service ) {
		$services = $this->get_enabled_preferred_services();

		return in_array( $service, $services ) && $this->is_dhl_enabled();
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
