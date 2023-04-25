<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;
use Exception;

defined( 'ABSPATH' ) || exit;

abstract class Rest {

	/**
	 * The request response
	 * @var array
	 */
	protected $response = null;

	/**
	 * @var mixed
	 */
	protected $id = '';

	/**
	 * @var array
	 */
	protected $remote_header = array();

	/**
	 * DHL_Api constructor.
	 *
	 * @param string $api_key, $api_secret
	 */
	public function __construct() {}

	/**
	 * Method to set id
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the id
	 *
	 * @return $id
	 */
	public function get_id() {
		return $this->id;
	}

	protected function get_auth() {
		return $this->get_basic_auth_encode( Package::get_cig_user(), Package::get_cig_password() );
	}

	public function get_request( $endpoint = '', $query_args = array(), $content_type = 'json' ) {
		$api_url = $this->get_base_url();

		$this->set_header( $this->get_auth(), 'GET', $endpoint );

		$wp_request_url     = add_query_arg( $query_args, $api_url . $endpoint );
		$wp_request_headers = $this->get_header();

		Package::log( 'GET URL: ' . $wp_request_url );

		$wp_dhl_rest_response = wp_remote_get(
			esc_url_raw( $wp_request_url ),
			array(
				'headers' => $wp_request_headers,
				'timeout' => 30,
			)
		);

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = wp_remote_retrieve_body( $wp_dhl_rest_response );

		if ( 'json' === $content_type ) {
			$response_body = json_decode( $response_body );
		}

		Package::log( 'GET Response Code: ' . $response_code );
		Package::log( 'GET Response Body: ' . print_r( $response_body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		$this->handle_get_response( $response_code, $response_body );

		return $response_body;
	}

	protected function handle_get_response( $response_code, $response_body ) {
		$response_code = absint( $response_code );

		switch ( $response_code ) {
			case 200:
			case 201:
				break;
			case 400:
				$error_message = str_replace( '/', ' / ', isset( $response_body->statusText ) ? $response_body->statusText : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				throw new Exception( _x( '400 - ', 'dhl', 'woocommerce-germanized' ) . $error_message, $response_code );
			case 401:
				throw new Exception( _x( '401 - Unauthorized Access - Invalid token or Authentication Header parameter', 'dhl', 'woocommerce-germanized' ), $response_code );
			case 408:
				throw new Exception( _x( '408 - Request Timeout', 'dhl', 'woocommerce-germanized' ), $response_code );
			case 429:
				throw new Exception( _x( '429 - Too many requests in given amount of time', 'dhl', 'woocommerce-germanized' ), $response_code );
			case 503:
				throw new Exception( _x( '503 - Service Unavailable', 'dhl', 'woocommerce-germanized' ), $response_code );
			default:
				$response_code = empty( $response_code ) ? 404 : $response_code;

				if ( empty( $response_body->statusText ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$error_message = _x( 'GET error or timeout occured. Please try again later.', 'dhl', 'woocommerce-germanized' );
				} else {
					$error_message = str_replace( '/', ' / ', $response_body->statusText ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}

				Package::log( 'GET Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( $response_code . ' - ' . $error_message, $response_code );
		}
	}

	public function get_base_url() {
		return Package::get_rest_url();
	}

	public function post_request( $endpoint = '', $query_args = array() ) {
		$api_url = $this->get_base_url();

		$this->set_header( $this->get_auth(), 'POST', $endpoint );

		$wp_request_url     = $api_url . $endpoint;
		$wp_request_headers = $this->get_header();

		Package::log( 'POST URL: ' . $wp_request_url );

		$wp_dhl_rest_response = wp_remote_post(
			esc_url_raw( $wp_request_url ),
			array(
				'headers' => $wp_request_headers,
				'timeout' => 100,
				'body'    => $query_args,
			)
		);

		$response_code = wp_remote_retrieve_response_code( $wp_dhl_rest_response );
		$response_body = json_decode( wp_remote_retrieve_body( $wp_dhl_rest_response ) );

		Package::log( 'POST Response Code: ' . $response_code );
		Package::log( 'POST Response Body: ' . print_r( $response_body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		$this->handle_post_response( $response_code, $response_body );

		return $response_body;
	}

	protected function handle_post_response( $response_code, $response_body ) {
		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			default:
				if ( empty( $response_body->detail ) ) {
					$error_message = _x( 'POST error or timeout occured. Please try again later.', 'dhl', 'woocommerce-germanized' );
				} else {
					$error_message = $response_body->detail;
				}

				Package::log( 'POST Error: ' . $response_code . ' - ' . $error_message );

				throw new Exception( $response_code . ' - ' . $error_message );
		}
	}

	protected function get_basic_auth_encode( $user, $pass ) {
		return 'Basic ' . base64_encode( $user . ':' . $pass ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		$wp_version = get_bloginfo( 'version' );
		$wc_version = defined( 'WC_Version' ) ? WC_Version : '';

		$dhl_header['Content-Type']  = 'application/json';
		$dhl_header['Accept']        = 'application/json';
		$dhl_header['Authorization'] = 'Bearer ' . $authorization;
		$dhl_header['User-Agent']    = 'WooCommerce/' . $wc_version . ' (WordPress/' . $wp_version . ') Germanized-DHL/' . Package::get_version();

		$this->remote_header = $dhl_header;
	}

	protected function get_header() {
		return $this->remote_header;
	}
}
