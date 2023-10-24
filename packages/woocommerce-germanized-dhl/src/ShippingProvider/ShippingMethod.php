<?php

namespace Vendidero\Germanized\DHL\ShippingProvider;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
 */
class ShippingMethod {

	/**
	 * @var \Vendidero\Germanized\Shipments\ShippingProvider\Method null
	 */
	protected $method = null;

	protected $preferred_services = null;

	/**
	 * ShippingProviderMethodDHL constructor.
	 *
	 * @param \Vendidero\Germanized\Shipments\ShippingProvider\Method $method
	 */
	public function __construct( $method ) {
		$this->method = $method;
	}

	public function is_dhl_enabled() {
		return $this->method->is_provider_enabled( 'dhl' );
	}

	public function is_deutsche_post_enabled() {
		return $this->method->is_provider_enabled( 'deutsche_post' );
	}

	public function is_packstation_enabled() {
		if ( $this->is_deutsche_post_enabled() ) {
			return apply_filters( 'woocommerce_gzd_enable_packstation_deutsche_post', ParcelLocator::is_packstation_enabled( false ) );
		} else {
			return $this->method->get_option( 'dhl_parcel_pickup_packstation_enable' ) === 'yes' ? true : false;
		}
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

			if ( $this->is_dhl_enabled() ) {
				foreach ( $services as $service ) {
					if ( strpos( $service, 'Preferred' ) === false ) {
						continue;
					}

					if ( $this->method->get_option( 'dhl_' . $service . '_enable' ) === 'yes' ) {
						$this->preferred_services[] = $service;
					}
				}
			}
		}

		return $this->preferred_services;
	}

	public function is_preferred_service_enabled( $service ) {
		$services = $this->get_enabled_preferred_services();

		return in_array( $service, $services, true ) && $this->is_dhl_enabled();
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
