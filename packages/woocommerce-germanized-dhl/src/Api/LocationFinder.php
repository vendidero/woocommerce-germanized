<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

class LocationFinder extends Rest {

	protected function is_debug_mode() {
		return Package::is_debug_mode();
	}

	public function get_base_url() {
		if ( $this->is_debug_mode() ) {
			return 'https://api-sandbox.dhl.com/location-finder/v1';
		} else {
			return 'https://api.dhl.com/location-finder/v1';
		}
	}

	public function get_api_key() {
		if ( $this->is_debug_mode() ) {
			return 'demo-key';
		} else {
			return defined( 'WC_GZD_DHL_LOCATION_FINDER_API_KEY' ) ? WC_GZD_DHL_LOCATION_FINDER_API_KEY : Package::get_dhl_com_api_key();
		}
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		parent::set_header();
		unset( $this->remote_header['Authorization'] );

		$this->remote_header['DHL-API-Key'] = $this->get_api_key();
	}

	public function find_by_id( $keyword, $country, $postcode ) {
		$keyword_id = ParcelLocator::extract_pickup_keyword_id( $keyword );

		$result = $this->get_request(
			'/find-by-keyword-id',
			array(
				'keywordId'   => $keyword_id,
				'countryCode' => $country,
				'postalCode'  => $postcode,
			)
		);

		if ( ! empty( $result->url ) ) {
			$this->adjust_location_result( $result );

			return $result;
		} else {
			return false;
		}
	}

	protected function adjust_location_result( $result ) {
		$api_types = array(
			'locker'       => 'packstation',
			'servicepoint' => 'parcelshop',
			'postoffice'   => 'postoffice',
		);

		// Lets assume it is a postoffice by default
		$result->gzd_type      = 'postoffice';
		$result->gzd_id        = isset( $result->location->keywordId ) ? wc_clean( $result->location->keywordId ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$result->gzd_result_id = wc_clean( $result->url );

		if ( isset( $result->location->type ) && array_key_exists( $result->location->type, $api_types ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$result->gzd_type = $api_types[ $result->location->type ];
		}

		$result->gzd_name = sprintf( _x( '%1$s %2$s', 'dhl location name', 'woocommerce-germanized' ), wc_clean( $result->location->keyword ), wc_clean( $result->location->keywordId ) );
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

		$response = $this->get_request( '/find-by-address', $args );
		$results  = array();

		if ( isset( $response->locations ) ) {
			foreach ( $response->locations as $result ) {
				$this->adjust_location_result( $result );

				// Not supporting this type
				if ( ! in_array( $result->gzd_type, $types, true ) ) {
					continue;
				}

				if ( count( $results ) >= $limit ) {
					break;
				}

				$results[] = $result;
			}
		}

		return $results;
	}
}
