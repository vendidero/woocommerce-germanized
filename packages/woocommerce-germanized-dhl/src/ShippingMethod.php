<?php

namespace Vendidero\Germanized\DHL;
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
class ShippingMethod {

	/**
	 * The actual method object
	 *
	 * @var WC_Shipping_Method
	 */
	protected $method;

	protected $instance_form_fields = array();

	protected $preferred_services = null;

	/**
	 * @param WC_Customer $customer
	 */
	public function __construct( $method ) {
		$this->method = $method;

		$this->init();
	}

	protected function init() {
		$this->instance_form_fields = include Package::get_path() . '/includes/admin/views/settings-shipping-method.php';

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

	public function has_option( $key ) {
		$fields = $this->instance_form_fields;
		$key    = $this->maybe_prefix_key( $key );

		return array_key_exists( $key, $fields ) ? true : false;
	}

	protected function maybe_prefix_key( $key ) {
		if ( substr( $key, 0, 4 ) !== 'dhl_' ) {
			$key = 'dhl_' . $key;
		}

		return $key;
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = $this->method->get_option( $key );

		if ( strpos( $key, 'enable' ) !== false ) {
			if ( 'yes' === $option_value && ! $this->is_dhl_enabled() ) {
				$option_value = 'no';
			}
		}

		return $option_value;
	}

	public function is_packstation_enabled() {
		return $this->get_option( 'parcel_pickup_packstation_enable' ) === 'yes' ? true : false;
	}

	public function is_postoffice_enabled() {
		return $this->get_option( 'parcel_pickup_postoffice_enable' ) === 'yes' ? true : false;
	}

	public function is_parcelshop_enabled() {
		return $this->get_option( 'parcel_pickup_parcelshop_enable' ) === 'yes' ? true : false;
	}

	public function is_dhl_enabled() {
		return $this->method->get_option( 'dhl_enable' ) === 'yes' ? true : false;
	}

	public function get_enabled_preferred_services() {
		if ( is_null( $this->preferred_services ) ) {
			$services                 = wc_gzd_dhl_get_services();
			$this->preferred_services = array();

			foreach ( $services as $service ) {

				if ( strpos( $service, 'Preferred' ) === false ) {
					continue;
				}

				if ( $this->get_option( $service . '_enable' ) === 'yes' ) {
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
