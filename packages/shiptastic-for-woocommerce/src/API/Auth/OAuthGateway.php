<?php

namespace Vendidero\Shiptastic\API\Auth;

use Vendidero\Shiptastic\ShipmentError;

class OAuthGateway extends OAuth {

	public function get_type() {
		return 'oauth_gateway';
	}

	public function get_url() {
		return 'https://oauth.vendidero.com';
	}

	public function is_connected() {
		return $this->get_refresh_token();
	}

	public function authorize( $redirect_uri ) {
		$auth_type = $this->get_api()->get_setting_name();

		$redirect_uri = add_query_arg(
			array(
				'action'           => 'woocommerce_stc_oauth',
				'type'             => $auth_type,
				'_wpnonce'         => wp_create_nonce( "stc_oauth_{$auth_type}" ),
				'_wp_http_referer' => rawurlencode( $redirect_uri ),
			),
			admin_url( 'admin-post.php' )
		);

		$authorize_url = add_query_arg(
			rawurlencode_deep(
				array(
					'redirect_url' => $redirect_uri,
					'type'         => $auth_type,
					'nonce'        => wp_create_nonce( "stc_oauth_init_{$auth_type}" ),
				)
			),
			$this->get_request_url( 'init' )
		);

		add_filter(
			'allowed_redirect_hosts',
			function ( $hosts ) {
				$hosts[] = 'oauth.vendidero.com';
				return $hosts;
			}
		);

		wp_safe_redirect( $authorize_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit();
	}

	public function get_token( $auth_code ) {
		$response = $this->get_api()->post(
			$this->get_request_url( 'token' ),
			array(
				'code' => $auth_code,
				'type' => $this->get_api()->get_setting_name(),
			),
			array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			)
		);

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( isset( $body['access_token'] ) ) {
				$this->update_access_and_refresh_token( $body );
			} else {
				$response->set_error( new ShipmentError( 'auth', 'Error while generating access token.' ) );
			}
		}

		return $response;
	}

	protected function refresh() {
		$response = $this->get_api()->post(
			$this->get_request_url( 'refresh' ),
			array(
				'refresh_token' => $this->get_refresh_token(),
				'type'          => $this->get_api()->get_setting_name(),
			),
			array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			)
		);

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( isset( $body['access_token'] ) ) {
				$this->update_access_and_refresh_token( $body );
			} else {
				$response->set_error( new ShipmentError( 'auth', 'Error while refreshing token.' ) );
			}
		}

		return $response;
	}

	public function auth() {
		return $this->refresh();
	}
}
