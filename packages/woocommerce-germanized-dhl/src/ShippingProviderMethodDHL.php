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

	/**
	 * ShippingProviderMethodDHL constructor.
	 *
	 * @param ShippingProviderMethod $method
	 */
	public function __construct( $method ) {
		$this->method = $method;
	}

	protected function maybe_prefix_key( $key ) {
		if ( substr( $key, 0, 4 ) !== 'dhl_' ) {
			$key = 'dhl_' . $key;
		}

		return $key;
	}

	public function has_option( $key ) {
		$dhl_key = $this->maybe_prefix_key( $key );

		return $this->method->has_option( $dhl_key );
	}

	public function get_option( $key ) {
		$dhl_key = $this->maybe_prefix_key( $key );

		if ( $this->method->has_option( $dhl_key ) ) {
			$option_value = $this->method->get_option( $dhl_key );

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
		return $this->method->get_option( 'dhl_parcel_pickup_packstation_enable' ) === 'yes' ? true : false;
	}

	public function is_postoffice_enabled() {
		return $this->method->get_option( 'dhl_parcel_pickup_postoffice_enable' ) === 'yes' ? true : false;
	}

	public function is_parcelshop_enabled() {
		return $this->method->get_option( 'dhl_parcel_pickup_parcelshop_enable' ) === 'yes' ? true : false;
	}

	public function get_enabled_preferred_services() {
		if ( is_null( $this->preferred_services ) ) {
			$services                 = wc_gzd_dhl_get_services();
			$this->preferred_services = array();

			foreach ( $services as $service ) {

				if ( strpos( $service, 'Preferred' ) === false ) {
					continue;
				}

				if ( $this->method->get_option( 'dhl_' . $service . '_enable' ) === 'yes' ) {
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
