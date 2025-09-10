<?php

namespace Vendidero\Shiptastic\API;

use Vendidero\Shiptastic\Interfaces\Api;
use Vendidero\Shiptastic\Interfaces\RESTAuth;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

abstract class REST extends \Vendidero\Shiptastic\API\Api {

	/**
	 * @return RESTAuth
	 */
	abstract protected function get_auth_instance();

	protected function get_timeout( $request_type = 'GET' ) {
		return 'GET' === $request_type ? 30 : 100;
	}

	protected function get_content_type() {
		return 'application/json';
	}

	protected function maybe_encode_body( $body_args, $content_type = '' ) {
		if ( empty( $content_type ) ) {
			$content_type = $this->get_content_type();
		}

		if ( is_array( $body_args ) ) {
			$body_args = $this->encode_body( $body_args );
		}

		if ( 'application/json' === $content_type ) {
			return wp_json_encode( $body_args, JSON_PRETTY_PRINT );
		} elseif ( 'application/x-www-form-urlencoded' === $content_type ) {
			return http_build_query( $body_args );
		}

		return $body_args;
	}

	/**
	 * @param $url
	 * @param $type
	 * @param $body_args
	 * @param $headers
	 * @param bool $is_retry
	 *
	 * @return Response
	 */
	protected function get_response( $url, $type = 'GET', $body_args = array(), $headers = array(), $is_retry = false ) {
		$response        = false;
		$is_auth_request = false;

		if ( $this->is_auth_request( $url ) ) {
			$is_auth_request = true;
		} elseif ( $this->get_auth_api()->is_connected() && ! $this->get_auth_api()->has_auth() ) {
			$auth_response = $this->get_auth_api()->auth();
		}

		/**
		 * Need to build-up headers after (potentially) performing the auth request
		 * to make sure new auth headers are set.
		 */
		$headers = $this->get_headers( $headers );

		if ( 'GET' === $type ) {
			$response = wp_remote_get(
				esc_url_raw( $url ),
				array(
					'headers' => $headers,
					'timeout' => $this->get_timeout( $type ),
				)
			);
		} elseif ( 'POST' === $type ) {
			$response = wp_remote_post(
				esc_url_raw( $url ),
				array(
					'headers' => $headers,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $headers['Content-Type'] ),
				)
			);
		} elseif ( 'PUT' === $type ) {
			$response = wp_remote_request(
				esc_url_raw( $url ),
				array(
					'headers' => $headers,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $headers['Content-Type'] ),
					'method'  => 'PUT',
				)
			);
		} elseif ( 'DELETE' === $type ) {
			$response = wp_remote_request(
				esc_url_raw( $url ),
				array(
					'headers' => $headers,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $headers['Content-Type'] ),
					'method'  => 'DELETE',
				)
			);
		}

		if ( false !== $response ) {
			if ( is_wp_error( $response ) ) {
				if ( Package::is_debug_mode() ) {
					Package::log( sprintf( '%s error during REST (%s) call to %s:', $response->get_error_code(), $type, $url ), 'info', $this->get_title() );
					Package::log( wc_print_r( $response->get_error_messages(), true ), 'info', $this->get_title() );
					Package::log( 'Body:', 'info', $this->get_title() );
					Package::log( wc_print_r( $body_args, true ), 'info', $this->get_title() );
				}

				return new Response( 500, array(), array(), $response );
			}

			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_body    = wp_remote_retrieve_body( $response );
			$response_headers = wp_remote_retrieve_headers( $response );
			$response_obj     = $this->parse_response( $response_code, $response_body, $response_headers );

			if ( $response_obj->get_code() >= 300 ) {
				if ( ! $is_auth_request && ! $is_retry && $this->get_auth_api()->is_unauthenticated_response( $response_obj->get_code() ) ) {
					if ( is_callable( array( $this->get_auth_api(), 'invalidate' ) ) ) {
						$this->get_auth_api()->invalidate();
					} else {
						$this->get_auth_api()->revoke();
					}

					return $this->get_response( $url, $type, $body_args, $headers, true );
				}

				$response = $this->parse_error( $response_obj );

				if ( $response->is_error() && Package::is_debug_mode() ) {
					Package::log( sprintf( '%s error during REST (%s) call to %s:', $response->get_code(), $type, $url ), 'info', $this->get_title() );
					Package::log( wc_print_r( $response->get_error()->get_error_messages(), true ), 'info', $this->get_title() );
					Package::log( 'Body:', 'info', $this->get_title() );
					Package::log( wc_print_r( $body_args, true ), 'info', $this->get_title() );
				}

				return $response;
			}

			return $response_obj;
		}

		return new Response( 500, array(), array(), new ShipmentError( 'rest-error', sprintf( _x( 'Error while trying to perform REST request to %s', 'shipments', 'woocommerce-germanized' ), $url ) ) );
	}

	/**
	 * @param $response_code
	 * @param $response_body
	 * @param $response_headers
	 *
	 * @return Response
	 */
	protected function parse_response( $response_code, $response_body, $response_headers ) {
		return new Response( $response_code, $response_body, $response_headers );
	}

	/**
	 * @param Response $response
	 *
	 * @return Response
	 */
	protected function parse_error( $response ) {
		$error = new ShipmentError();
		$body  = $response->get_body();
		if ( isset( $body['message'] ) ) {
			$error->add( $response->get_code(), wp_kses_post( $body['message'] ) );
		} else {
			$error->add( $response->get_code(), _x( 'There was an unknown error calling the API.', 'shipments', 'woocommerce-germanized' ) );
		}

		$response->set_error( $error );

		return $response;
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( ! strstr( $endpoint, 'http://' ) && ! strstr( $endpoint, 'https://' ) ) {
			if ( '/' === substr( $endpoint, 0, 1 ) ) {
				$endpoint = substr( $endpoint, 1 );
			}

			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return Response
	 */
	public function get( $endpoint = '', $query_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint, $query_args ), 'GET', array(), $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return Response
	 */
	public function post( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'POST', $body_args, $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return Response
	 */
	public function put( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'PUT', $body_args, $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return Response
	 */
	public function delete( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'DELETE', $body_args, $header );
	}

	protected function get_headers( $headers = array() ) {
		$headers = wp_parse_args(
			$headers,
			array(
				'Content-Type' => $this->get_content_type(),
				'Accept'       => 'application/json',
				'User-Agent'   => 'Shiptastic/' . Package::get_version(),
			)
		);

		$headers = array_replace_recursive( $headers, $this->get_auth_api()->get_headers() );

		return $headers;
	}

	protected function decode( $str ) {
		return function_exists( 'mb_convert_encoding' ) ? mb_convert_encoding( $str, 'UTF-8', mb_detect_encoding( $str ) ) : $str;
	}

	/**
	 * @param string $str
	 *
	 * @return string
	 */
	protected function encode( $str ) {
		return wc_shiptastic_decode_html( $str );
	}

	/**
	 * Encode body args by converting html entities (e.g. &amp;) to utf-8.
	 *
	 * @param array|string $body_args
	 *
	 * @return array|string
	 */
	protected function encode_body( $body_args ) {
		if ( is_array( $body_args ) ) {
			return array_map( array( $this, 'encode_body' ), $body_args );
		} elseif ( is_scalar( $body_args ) ) {
			return $this->encode( $body_args );
		} else {
			return $body_args;
		}
	}
}
