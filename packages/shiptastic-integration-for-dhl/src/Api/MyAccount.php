<?php

namespace Vendidero\Shiptastic\DHL\Api;

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
	 * @return void
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
}
