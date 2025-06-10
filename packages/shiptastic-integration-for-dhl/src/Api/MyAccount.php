<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;

defined( 'ABSPATH' ) || exit;

class MyAccount extends PaketRest {

	public function get_title() {
		return _x( 'DHL Paket MyAccount', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_paket_myaccount';
	}

	public function get_url() {
		if ( $this->is_sandbox() ) {
			return 'https://api-sandbox.dhl.com/parcel/de/account/myaccount/v1/';
		} else {
			return 'https://api-eu.dhl.com/parcel/de/account/myaccount/v1/';
		}
	}

	/**
	 * @throws \Exception
	 */
	public function get_user() {
		$response = $this->get( 'user' );

		if ( $response->is_error() ) {
			throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), esc_html( $response->get_code() ) );
		} else {
			$body = $response->get_body();

			if ( isset( $body['user'] ) ) {
				return $body;
			} else {
				throw new \Exception( esc_html( _x( 'Unknown DHL API error.', 'dhl', 'woocommerce-germanized' ) ), 500 );
			}
		}
	}

	public function get_user_participation_numbers() {
		$participation_numbers = array();

		try {
			$user_info = $this->get_user();

			if ( ! empty( $user_info['shippingRights'] ) && ! empty( $user_info['shippingRights']['details'] ) ) {
				foreach ( $user_info['shippingRights']['details'] as $details ) {
					$details = wp_parse_args(
						$details,
						array(
							'billingNumber' => '',
							'goGreen'       => false,
							'product'       => array(),
						)
					);

					$product_id          = wc_clean( $details['product']['key'] );
					$billing_number      = wc_clean( $details['billingNumber'] );
					$internal_product_id = false;

					if ( 'dhlRetoure' === $product_id ) {
						$internal_product_id = 'return';
					} elseif ( $product = Package::get_dhl_shipping_provider()->get_product( $product_id ) ) {
						$internal_product_id = $product->get_id();
					}

					if ( $internal_product_id ) {
						if ( ! isset( $participation_numbers[ $internal_product_id ] ) ) {
							$participation_numbers[ $internal_product_id ] = array(
								'default' => '',
								'gogreen' => '',
							);
						}

						if ( true === $details['goGreen'] && empty( $participation_numbers[ $internal_product_id ]['gogreen'] ) ) {
							$participation_numbers[ $internal_product_id ]['gogreen'] = $billing_number;
						} elseif ( empty( $participation_numbers[ $internal_product_id ]['default'] ) ) {
							$participation_numbers[ $internal_product_id ]['default'] = $billing_number;
						}
					}
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $participation_numbers;
	}
}
