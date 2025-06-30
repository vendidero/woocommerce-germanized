<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Exception;
use Vendidero\Shiptastic\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

class LocationFinder extends \Vendidero\Shiptastic\API\REST {

	public function get_title() {
		return _x( 'DHL LocationFinder', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_location_finder';
	}

	public function get_url() {
		if ( $this->is_sandbox() ) {
			return 'https://api-sandbox.dhl.com/location-finder/v1';
		} else {
			return 'https://api.dhl.com/location-finder/v1';
		}
	}

	protected function get_auth_instance() {
		return new ApiKeyAuth( $this );
	}

	/**
	 * @param $keyword
	 * @param $country
	 * @param $postcode
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function find_by_id( $keyword, $country, $postcode ) {
		$keyword_id = ParcelLocator::extract_pickup_keyword_id( $keyword );

		$response = $this->get(
			'find-by-keyword-id',
			array(
				'keywordId'   => $keyword_id,
				'countryCode' => $country,
				'postalCode'  => $postcode,
			)
		);

		if ( ! $response->is_error() ) {
			$result = $response->get_body( false );

			if ( ! empty( $result->url ) ) {
				$this->adjust_location_result( $result );

				return $result;
			}
		}

		throw new Exception( $response->get_error()->get_error_message(), (int) $response->get_error()->get_error_code() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	protected function adjust_location_result( $result ) {
		$api_types = array(
			'locker'       => 'packstation',
			'servicepoint' => 'parcelshop',
			'postoffice'   => 'postoffice',
		);

		// Lets assume it is a postoffice by default
		$result->internal_type      = 'postoffice';
		$result->internal_id        = isset( $result->location->keywordId ) ? wc_clean( $result->location->keywordId ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$result->internal_result_id = wc_clean( $result->url );

		if ( isset( $result->location->type ) && array_key_exists( $result->location->type, $api_types ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$result->internal_type = $api_types[ $result->location->type ];
		}

		$result->internal_name = sprintf( _x( '%1$s %2$s', 'dhl location name', 'woocommerce-germanized' ), wc_clean( $result->location->keyword ), $result->internal_id );
	}

	/**
	 * Prevent arrays from being passed as array but use same keys instead as expected by the API endpoint.
	 *
	 * @see https://developer.dhl.com/api-reference/location-finder-unified#reference-docs-section/
	 *
	 * @param $endpoint
	 * @param $query_args
	 */
	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		$request_url = parent::get_request_url( $endpoint, $query_args );
		$request_url = preg_replace( '/\%5B\d+\%5D/', '', $request_url );

		return $request_url;
	}

	/**
	 * @param $address
	 * @param $types
	 * @param $limit
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_parcel_location( $address, $types = array(), $limit = false ) {
		$address = wp_parse_args(
			$address,
			array(
				'city'    => '',
				'zip'     => '',
				'address' => '',
				'country' => 'DE',
			)
		);

		$address_esc = strtolower( $address['address'] );

		if ( strstr( $address_esc, 'packstation' ) || strstr( $address_esc, 'postfiliale' ) ) {
			$address['address'] = '';
		}

		/**
		 * Somehow the API returns wrong locations in case the address is missing, e.g.
		 * a search for the postcode 12203 yields results for the center of Berlin.
		 * Remove the city in case a zip is provided as a tweak too.
		 */
		$address['address'] = empty( $address['address'] ) ? 'xxx' : $address['address'];
		$address['city']    = ! empty( $address['zip'] ) ? '' : $address['city'];

		$default_types = array();

		if ( ParcelLocator::is_packstation_enabled() ) {
			$default_types[] = 'packstation';
		}

		if ( ParcelLocator::is_parcelshop_enabled() ) {
			$default_types[] = 'parcelshop';
		}

		if ( ParcelLocator::is_postoffice_enabled() ) {
			$default_types[] = 'postoffice';
		}

		$types = empty( $types ) ? $default_types : $types;
		$limit = is_numeric( $limit ) ? $limit : ParcelLocator::get_max_results();

		if ( empty( $address['city'] ) && empty( $address['zip'] ) ) {
			throw new Exception( esc_html_x( 'At least shipping city or zip is required.', 'dhl', 'woocommerce-germanized' ) );
		}

		$args = array(
			'countryCode'     => $address['country'],
			'addressLocality' => $address['city'],
			'providerType'    => 'parcel',
			'postalCode'      => $address['zip'],
			'streetAddress'   => $address['address'],
			'serviceType'     => array( 'parcel:pick-up-all' ),
			'locationType'    => array(),
			'limit'           => $limit,
			'radius'          => 5000,
		);

		foreach ( $types as $type ) {
			if ( 'postoffice' === $type ) {
				$args['locationType'][] = 'postoffice';
				$args['locationType'][] = 'postbank';
			} elseif ( 'packstation' === $type || 'locker' === $type ) {
				$args['locationType'][] = 'locker';
			} elseif ( 'parcelshop' === $type ) {
				$args['locationType'][] = 'servicepoint';
			}
		}

		$response = $this->get( 'find-by-address', $args );
		$results  = array();

		if ( ! $response->is_error() ) {
			$body = $response->get_body( false );

			if ( isset( $body->locations ) ) {
				foreach ( $body->locations as $result ) {
					/**
					 * Exclude certain types, e.g. postbox
					 */
					if ( isset( $result->location->type ) && ! in_array( $result->location->type, $args['locationType'], true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						continue;
					}

					/**
					 * Some Postfiliale seem to miss a keywordId?
					 */
					if ( ! isset( $result->location->keywordId ) || empty( $result->location->keywordId ) ) {
						continue;
					}

					$this->adjust_location_result( $result );

					// Not supporting this type
					if ( ! in_array( $result->internal_type, $types, true ) ) {
						continue;
					}

					if ( count( $results ) >= $limit ) {
						break;
					}

					$results[] = $result;
				}
			}
		} else {
			throw new Exception( $response->get_error()->get_error_message(), (int) $response->get_error()->get_error_code() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $results;
	}
}
