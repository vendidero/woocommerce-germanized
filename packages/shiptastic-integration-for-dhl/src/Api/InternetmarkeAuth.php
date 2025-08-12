<?php
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\API\Auth\OAuth;
use Vendidero\Shiptastic\DataStores\Shipment;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class InternetmarkeAuth extends OAuth {

	public function get_url() {
		return $this->get_api()->get_url();
	}

	protected function get_client_id() {
		return Package::get_dhl_com_api_key();
	}

	protected function get_client_secret() {
		return Package::get_dhl_com_api_secret();
	}

	protected function get_username() {
		return Package::get_internetmarke_username();
	}

	protected function get_password() {
		return Package::get_internetmarke_password();
	}

	public function is_connected() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function auth() {
		$response = $this->get_api()->post(
			$this->get_request_url( 'user' ),
			array(
				'client_id'     => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
				'username'      => $this->get_username(),
				'password'      => $this->get_password(),
				'grant_type'    => 'client_credentials',
			),
			array( 'Content-Type' => 'application/x-www-form-urlencoded' )
		);

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( ! empty( $body['access_token'] ) ) {
				if ( isset( $body['walletBalance'] ) ) {
					$this->get_api()->update_balance( absint( $body['walletBalance'] ) );
				}

				$this->update_access_and_refresh_token( $body );

				return true;
			} else {
				$response->set_error( new ShipmentError( 'auth', _x( 'Error while authenticating with Internetmarke', 'dhl', 'woocommerce-germanized' ) ) );
			}
		}

		if ( $response->is_error() ) {
			$this->revoke();
		}

		return $response;
	}
}
