<?php

namespace Vendidero\Shiptastic\API;

use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Response {

	protected $body = '';

	protected $code = '';

	protected $error = null;

	protected $headers = array();

	/**
	 * @param $code
	 * @param $body
	 * @param array $headers
	 * @param \WP_Error|ShipmentError|null $error
	 */
	public function __construct( $code, $body, $headers = array(), $error = null ) {
		$this->code    = absint( $code );
		$this->body    = $body;
		$this->headers = is_a( $headers, '\WpOrg\Requests\Utility\CaseInsensitiveDictionary' ) ? $headers->getAll() : (array) $headers;

		$this->set_error( $error );
	}

	/**
	 * @param \WP_Error|ShipmentError|null $error
	 *
	 * @return void
	 */
	public function set_error( $error ) {
		if ( is_wp_error( $error ) ) {
			$error = ShipmentError::from_wp_error( $error );
		}

		$this->error = $error;
	}

	public function get_body_raw() {
		return $this->body;
	}

	public function get_body( $as_associative = true ) {
		return json_decode( $this->get_body_raw(), $as_associative );
	}

	public function set_body( $body ) {
		$this->body = $body;
	}

	/**
	 * @return array
	 */
	public function get_headers() {
		return $this->headers;
	}

	public function get( $prop ) {
		$body = $this->get_body();

		return isset( $body[ $prop ] ) ? $body[ $prop ] : null;
	}

	public function get_code() {
		return $this->code;
	}

	public function set_code( $code ) {
		$this->code = absint( $code );
	}

	public function is_error() {
		return $this->get_error() && ! $this->is_soft_error() ? true : false;
	}

	public function is_soft_error() {
		return $this->get_error() ? $this->get_error()->is_soft_error() : false;
	}

	/**
	 * @return ShipmentError|null
	 */
	public function get_soft_error() {
		return $this->is_soft_error() ? $this->get_error() : null;
	}

	/**
	 * @return null|ShipmentError
	 */
	public function get_error() {
		return $this->error;
	}
}
