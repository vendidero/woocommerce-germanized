<?php

namespace Vendidero\Germanized\Shipments\API;

use Vendidero\Germanized\Shipments\Interfaces\RESTAuth;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShipmentError;

defined( 'ABSPATH' ) || exit;

abstract class REST {

	protected $auth = null;

	abstract public function get_url();

	/**
	 * @return RESTAuth
	 */
	abstract protected function get_auth_instance();

	/**
	 * @return RESTAuth
	 */
	protected function get_auth() {
		if ( is_null( $this->auth ) ) {
			$this->auth = $this->get_auth_instance();
		}

		return $this->auth;
	}

	/**
	 * @return bool
	 */
	protected function is_auth_request( $url ) {
		$auth_url = $this->get_auth()->get_url();

		if ( empty( $auth_url ) ) {
			return false;
		}

		return strstr( $url, $auth_url );
	}

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
	 *
	 * @return Response
	 */
	protected function get_response( $url, $type = 'GET', $body_args = array(), $headers = array() ) {
		$response        = false;
		$is_auth_request = false;

		if ( $this->is_auth_request( $url ) ) {
			$is_auth_request = true;
		} elseif ( ! $this->get_auth()->has_auth() ) {
			$auth_response = $this->get_auth()->auth();
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
				return new Response( 500, array(), array(), $response );
			}

			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_body    = wp_remote_retrieve_body( $response );
			$response_headers = wp_remote_retrieve_headers( $response );
			$response_obj     = new Response( $response_code, $response_body, $response_headers );

			if ( $response_obj->get_code() >= 300 ) {
				if ( ! $is_auth_request && ! isset( $body_args['is_retry'] ) && $this->get_auth()->is_unauthenticated_response( $response_obj->get_code() ) ) {
					$this->get_auth()->revoke();
					$body_args['is_retry'] = true;

					return $this->get_response( $url, $type, $body_args, $headers );
				}

				$response = $this->parse_error( $response_obj );

				return $response;
			}

			return new Response( $response_code, $response_body, $response_headers );
		}

		return new Response( 500, array(), array(), new ShipmentError( 'rest-error', sprintf( _x( 'Error while trying to perform REST request to %s', 'shipments', 'woocommerce-germanized' ), $url ) ) );
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
				'User-Agent'   => 'Germanized/' . Package::get_version(),
			)
		);

		$headers = array_replace_recursive( $headers, $this->get_auth()->get_headers() );

		return $headers;
	}
}
