<?php

namespace Vendidero\Shiptastic\API\Auth;

use Vendidero\Shiptastic\API\Api;
use Vendidero\Shiptastic\Interfaces\ApiAuth;

abstract class Auth implements ApiAuth {

	/**
	 * @var Api
	 */
	protected $api = null;

	/**
	 * @param $api Api
	 */
	public function __construct( $api ) {
		$this->api = $api;
	}

	/**
	 * @return Api
	 */
	public function get_api() {
		return $this->api;
	}

	public function is_unauthenticated_response( $code ) {
		return in_array( (int) $code, array( 401, 403 ), true );
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( ! strstr( $endpoint, 'http://' ) && ! strstr( $endpoint, 'https://' ) ) {
			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}
}
