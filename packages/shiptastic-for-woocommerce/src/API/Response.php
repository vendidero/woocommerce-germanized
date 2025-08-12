<?php

namespace Vendidero\Shiptastic\API;

use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Response {

	protected $body = '';

	protected $code = '';

	protected $error = null;

	protected $headers = array();

	protected $dom = null;

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

	/**
	 * @return \DOMDocument|false
	 */
	public function get_xml() {
		if ( ! is_null( $this->dom ) ) {
			return $this->dom;
		}

		$xml = $this->get_body_raw();

		if ( '<?xml' !== substr( $xml, 0, 5 ) ) {
			$this->dom = false;

			return $this->dom;
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			$this->dom = false;

			return $this->dom;
		}

		$xml = trim( $xml );

		if ( empty( $xml ) ) {
			$this->dom = false;

			return $this->dom;
		}

		libxml_use_internal_errors( true );
		$dom                      = new \DOMDocument( '1.0', 'utf-8' );
		$dom->preserveWhiteSpace  = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->formatOutput        = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->strictErrorChecking = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( ! defined( 'LIBXML_HTML_NOIMPLIED' ) ) {
			$this->dom = false;

			return $this->dom;
		}

		/**
		 * Load without HTML wrappers (html, body). Force UTF-8 encoding.
		 */
		@$dom->loadXML( $xml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		// Explicitly force utf-8 encoding
		$dom->encoding = 'UTF-8';

		libxml_clear_errors();

		if ( ! $dom->hasChildNodes() ) {
			return false;
		}

		$this->dom = $dom;

		return $this->dom;
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
