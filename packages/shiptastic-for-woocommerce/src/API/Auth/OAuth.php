<?php

namespace Vendidero\Shiptastic\API\Auth;

use Vendidero\Shiptastic\SecretBox;

abstract class OAuth extends RESTAuth {

	public function get_type() {
		return 'oauth';
	}

	protected function get_access_token() {
		return $this->get_token_data( 'access', 'token' );
	}

	public function access_token_expires() {
		return $this->get_token_data( 'access', 'expires' );
	}

	protected function get_token_data( $token_type = 'access', $type = 'token' ) {
		$token_data = get_transient( "woocommerce_stc_{$this->get_api()->get_setting_name()}_{$token_type}_token" );
		$result     = '';

		if ( false !== $token_data ) {
			if ( is_array( $token_data ) ) {
				$token_data = (array) $token_data;

				if ( isset( $token_data[ $type ] ) ) {
					if ( 'token' === $type ) {
						$result = SecretBox::maybe_decrypt( $token_data[ $type ] );
					} else {
						$result = $token_data[ $type ];
					}
				}
			} elseif ( 'token' === $type ) {
				$result = $token_data;
			}
		}

		return $result;
	}

	protected function get_refresh_token() {
		return $this->get_token_data( 'refresh', 'token' );
	}

	public function refresh_token_expires() {
		return $this->get_token_data( 'refresh', 'expires' );
	}

	abstract public function get_url();

	public function get_headers() {
		$headers = array();

		if ( $this->has_auth() ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_access_token();
		}

		return $headers;
	}

	public function has_auth() {
		return $this->get_access_token() ? true : false;
	}

	protected function update_access_and_refresh_token( $token_data ) {
		$token_data = wp_parse_args(
			$token_data,
			array(
				'access_token'             => '',
				'expires_in'               => HOUR_IN_SECONDS,
				'refresh_token'            => '',
				'refresh_token_expires_in' => DAY_IN_SECONDS * 180,
			)
		);

		$expires_in         = $token_data['expires_in'];
		$refresh_expires_in = $token_data['refresh_token_expires_in'];

		$expires_in         -= MINUTE_IN_SECONDS;
		$refresh_expires_in -= DAY_IN_SECONDS;

		set_transient(
			"woocommerce_stc_{$this->get_api()->get_setting_name()}_access_token",
			array(
				'token'   => SecretBox::maybe_encrypt( wc_clean( $token_data['access_token'] ) ),
				'expires' => time() + $expires_in,
			),
			$expires_in
		);

		set_transient(
			"woocommerce_stc_{$this->get_api()->get_setting_name()}_refresh_token",
			array(
				'token'   => SecretBox::maybe_encrypt( wc_clean( $token_data['refresh_token'] ) ),
				'expires' => time() + $refresh_expires_in,
			),
			$refresh_expires_in
		);
	}

	public function revoke() {
		delete_transient( "woocommerce_stc_{$this->get_api()->get_setting_name()}_refresh_token" );
		delete_transient( "woocommerce_stc_{$this->get_api()->get_setting_name()}_access_token" );
	}
}
