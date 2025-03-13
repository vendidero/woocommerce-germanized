<?php

namespace Vendidero\Shiptastic\API;

use Vendidero\Shiptastic\Interfaces\ApiAuth;

defined( 'ABSPATH' ) || exit;

abstract class Api implements \Vendidero\Shiptastic\Interfaces\Api {

	protected $auth = null;

	protected $is_sandbox = false;

	public function is_sandbox() {
		return $this->is_sandbox;
	}

	public function set_is_sandbox( $is_sandbox ) {
		$this->is_sandbox = $is_sandbox;
	}

	/**
	 * @return ApiAuth
	 */
	abstract protected function get_auth_instance();

	public function get_setting_name() {
		return $this->get_name() . ( $this->is_sandbox() ? '_sandbox' : '' );
	}

	/**
	 * @return ApiAuth
	 */
	public function get_auth_api() {
		if ( is_null( $this->auth ) ) {
			$this->auth = $this->get_auth_instance();
		}

		return $this->auth;
	}

	/**
	 * @return bool
	 */
	protected function is_auth_request( $url ) {
		$auth_url = $this->get_auth_api()->get_url();

		if ( empty( $auth_url ) ) {
			return false;
		}

		return strstr( $url, $auth_url );
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( ! strstr( $endpoint, 'http://' ) && ! strstr( $endpoint, 'https://' ) ) {
			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}

	protected function clean_request( $request ) {
		foreach ( $request as $k => $v ) {
			if ( is_array( $v ) ) {
				$request[ $k ] = $this->clean_request( $v );
			} elseif ( ! is_string( $v ) ) {
				$request[ $k ] = wp_json_encode( $v );
			}

			if ( '' === $v ) {
				unset( $request[ $k ] );
			}
		}

		return $request;
	}
}
