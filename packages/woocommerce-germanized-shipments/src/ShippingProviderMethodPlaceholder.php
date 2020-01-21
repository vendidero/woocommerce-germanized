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
class ShippingProviderMethodPlaceholder extends ShippingProviderMethod {

	protected $id = '';

	protected $instance_id = '';

	public function __construct( $id ) {

		if ( is_a( $id, 'WC_Shipping_Rate' ) ) {
			$instance_id = $id->get_instance_id();
			$id          = $id->get_id();

			if ( strpos( $id, ':' ) === false ) {
				$id = $id . ':' . $instance_id;
			}
		}

		if ( ! is_numeric( $id ) ) {
			$expl        = explode( ':', $id );
			$instance_id = ( ( ! empty( $expl ) && sizeof( $expl ) > 1 ) ? $expl[1] : 0 );
			$id          = ( ( ! empty( $expl ) && sizeof( $expl ) > 1 ) ? $expl[0] : $id );
		} else {
			$instance_id = $id;
		}

		$this->id          = $id;
		$this->instance_id = $instance_id;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_instance_id() {
		return $this->instance_id;
	}

	public function get_option( $key ) {
		$key          = $this->maybe_prefix_key( $key );
		$option_value = '';

		/**
		 * This filter is documented in src/shipping-provider-method.php
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_setting_value', $option_value, $key, $this );
	}
}
