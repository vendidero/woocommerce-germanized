<?php
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\API\Auth\OAuth;
use Vendidero\Shiptastic\DataStores\Shipment;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class OAuthPaket extends OAuth {

	public function get_url() {
		if ( Package::is_debug_mode() ) {
			return 'https://api-sandbox.dhl.com/parcel/de/account/auth/ropc/v1/';
		} else {
			return 'https://api-eu.dhl.com/parcel/de/account/auth/ropc/v1/';
		}
	}

	protected function get_access_token() {
		$transient = get_transient( 'woocommerce_stc_dhl_paket_api_access_token' );

		if ( $transient ) {
			$decrypted = SecretBox::decrypt( $transient );

			if ( ! is_wp_error( $decrypted ) ) {
				$transient = $decrypted;
			}
		}

		return $transient;
	}

	protected function get_client_id() {
		return Package::get_dhl_com_api_key();
	}

	protected function get_client_secret() {
		return 'Qe8ZTtQiOWaEcjad';
	}

	protected function get_username() {
		return Package::get_gk_api_user( $this->get_api()->is_sandbox() );
	}

	protected function get_password() {
		return Package::get_gk_api_signature( $this->get_api()->is_sandbox() );
	}

	public function is_connected() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function auth() {
		$response = $this->get_api()->post(
			$this->get_request_url( 'token' ),
			array(
				'client_id'     => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
				'username'      => $this->get_username(),
				'password'      => $this->get_password(),
				'grant_type'    => 'password',
			),
			array( 'Content-Type' => 'application/x-www-form-urlencoded' )
		);

		if ( ! $response->is_error() ) {
			$body         = $response->get_body();
			$access_token = $body['access_token'];
			$expires_in   = absint( isset( $body['expires_in'] ) ? $body['expires_in'] : 1799 );

			if ( ! empty( $access_token ) ) {
				$encrypted = SecretBox::encrypt( $access_token );

				if ( ! is_wp_error( $encrypted ) ) {
					$access_token = $encrypted;
				}

				set_transient( 'woocommerce_stc_dhl_paket_api_access_token', $access_token, $expires_in );

				return true;
			}

			$response->set_error( new ShipmentError( 'auth', _x( 'Error while authenticating with DHL.', 'dhl', 'woocommerce-germanized' ) ) );

			return $response;
		} else {
			delete_transient( 'woocommerce_stc_dhl_paket_api_access_token' );

			return $response;
		}
	}

	public function revoke() {
		delete_transient( 'woocommerce_stc_dhl_paket_api_access_token' );
	}
}
