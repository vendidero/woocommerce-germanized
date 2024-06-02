<?php

namespace Vendidero\Germanized\DHL\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class PickupLocation extends \Vendidero\Germanized\Shipments\ShippingProvider\PickupLocation {

	/**
	 * @param $customer_number
	 *
	 * @return bool|\WP_Error
	 */
	public function customer_number_is_valid( $customer_number ) {
		$customer_number = preg_replace( '/[^0-9]/', '', $customer_number );
		$is_valid        = parent::customer_number_is_valid( $customer_number );

		if ( $is_valid ) {
			$customer_number_len = strlen( $customer_number );

			if ( $customer_number_len < 6 || $customer_number_len > 12 ) {
				$is_valid = false;
			}
		}

		return $is_valid;
	}

	public function supports_dimensions( $dimensions ) {
		$supports_dimensions = parent::supports_dimensions( $dimensions );
		$dimensions          = wp_parse_args(
			$dimensions,
			array(
				'length' => 0.0,
				'width'  => 0.0,
				'height' => 0.0,
			)
		);

		if ( 'locker' === $this->get_type() ) {
			$locker_max_supported_dimensions = array(
				'length' => 75.0,
				'width'  => 60.0,
				'height' => 40.0,
			);

			foreach ( $dimensions as $dim => $dim_val ) {
				if ( isset( $locker_max_supported_dimensions[ $dim ] ) && (float) $dim_val > $locker_max_supported_dimensions[ $dim ] ) {
					$supports_dimensions = false;
					break;
				}
			}
		}

		return $supports_dimensions;
	}

	public function supports_weight( $weight ) {
		$weight = wc_get_weight( (float) $weight, 'kg', wc_gzd_get_packaging_weight_unit() );

		return (float) $weight <= 31.5;
	}

	public function get_customer_number_field_label() {
		return _x( 'Customer Number (Post Number)', 'dhl', 'woocommerce-germanized' );
	}
}
