<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Exception;
use Vendidero\Shiptastic\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ParcelServices extends PaketRest {

	public function get_title() {
		return _x( 'DHL Paket Parcel Services', 'dhl', 'woocommerce-germanized' );
	}

	public function get_url() {
		return $this->is_sandbox() ? 'https://cig.dhl.de/services/sandbox/rest' : 'https://cig.dhl.de/services/production/rest';
	}

	public function get_name() {
		return 'dhl_paket_parcel_services';
	}

	protected function get_auth_instance() {
		return new BasicAuthParcelServices( $this );
	}

	/**
	 * @param $args
	 *
	 * @return \Vendidero\Shiptastic\API\Response
	 * @throws Exception
	 */
	public function get_services( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'postcode'   => '',
				'start_date' => '',
			)
		);

		if ( empty( $args['postcode'] ) ) {
			throw new Exception( esc_html_x( 'Please provide the receiver postnumber.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( empty( $args['start_date'] ) ) {
			throw new Exception( esc_html_x( 'Please provide the shipment start date.', 'dhl', 'woocommerce-germanized' ) );
		}

		$response = $this->get( "checkout/{$args['postcode']}/availableServices", array( 'startDate' => $args['start_date'] ) );

		if ( $response->is_error() ) {
			throw new Exception( $response->get_error()->get_error_message(), (int) $response->get_error()->get_error_code() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		} else {
			return $response->get_body( false );
		}
	}
}
