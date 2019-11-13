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
class ShippingMethodPlaceholder extends ShippingMethod {

	protected $id = '';

	protected $instance_id = '';

	public function __construct( $id ) {
		$this->id = $id;

		if ( ! is_numeric( $id ) ) {
			$expl        = explode( ':', $id );
			$instance_id = ( ( ! empty( $expl ) && sizeof( $expl ) > 1 ) ? (int) $expl[1] : $id );
		} else {
			$instance_id = $id;
		}

		$this->instance_id = $instance_id;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_instance_id() {
		return $this->instance_id;
	}

	protected function maybe_prefix_key( $key ) {
		return $key;
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = Package::get_setting( $key );

		if ( strpos( $key, 'enable' ) !== false ) {
			if ( 'yes' === $option_value && ! $this->is_dhl_enabled() ) {
				$option_value = 'no';
			}
		}

		return $option_value;
	}

	public function is_dhl_enabled() {
		/**
		 * Filter to adjust the whether a certain (possibly unknown) shipping method
		 * supports DHL and it's feature or not. By default, shipping methods that are not
		 * registered via the Woo shipping zones are not supported and need to be activated
		 * manually by using this filter.
		 *
		 * @param boolean                   $enable Whether to enable DHL or not.
		 * @param string                    $id The method id e.g. advanced_flat_rate_shipping.
		 * @param ShippingMethodPlaceholder $placeholder The shipping method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_enable_placeholder_shipping_method', false, $this->get_id(), $this );
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
}
