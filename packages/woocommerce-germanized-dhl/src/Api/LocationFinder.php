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
			return defined( 'WC_GZD_DHL_LOCATION_FINDER_API_KEY' ) ? WC_GZD_DHL_LOCATION_FINDER_API_KEY : 'uwi1SH5bHDdMTdcWXB5JIsDCvBOyIawn';
		}
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		parent::set_header();
		unset( $this->remote_header['Authorization'] );

		$this->remote_header['DHL-API-Key'] = $this->get_api_key();
	}

	protected function get_translated_weekday( $schema ) {
		$weekdays = array(
			'http://schema.org/Monday'    => _x( 'Monday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Tuesday'   => _x( 'Tuesday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Wednesday' => _x( 'Wednesday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Thursday'  => _x( 'Thursday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Friday'    => _x( 'Friday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Saturday'  => _x( 'Saturday', 'dhl', 'woocommerce-germanized' ),
			'http://schema.org/Sunday'    => _x( 'Sunday', 'dhl', 'woocommerce-germanized' ),
		);

		if ( isset( $weekdays[ $schema ] ) ) {
			return $weekdays[ $schema ];
		}

		return false;
	}

	protected function get_time_string( $time_raw ) {
		$time_expl = explode( ':', $time_raw );

		if ( count( $time_expl ) > 2 ) {
			$time_expl = array_slice( $time_expl, 0, 2 );
		}

		return implode( ':', $time_expl );
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
		$result->gzd_type          = 'postoffice';
		$result->gzd_id            = isset( $result->location->keywordId ) ? wc_clean( $result->location->keywordId ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$result->gzd_result_id     = wc_clean( $result->url );
		$result->gzd_opening_hours = array();

		if ( isset( $result->location->type ) && array_key_exists( $result->location->type, $api_types ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$result->gzd_type = $api_types[ $result->location->type ];
		}

		$result->gzd_name = sprintf( _x( '%1$s %2$s', 'dhl location name', 'woocommerce-germanized' ), wc_clean( $result->location->keyword ), wc_clean( $result->location->keywordId ) );

		if ( isset( $result->openingHours ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $result->openingHours as $opening_data ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( ! isset( $result->gzd_opening_hours[ $opening_data->dayOfWeek ] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$result->gzd_opening_hours[ $opening_data->dayOfWeek ] = array( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'weekday'   => $this->get_translated_weekday( $opening_data->dayOfWeek ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'time_html' => $this->get_time_string( wc_clean( $opening_data->opens ) ) . ' - ' . $this->get_time_string( wc_clean( $opening_data->closes ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					);
				} else {
					$result->gzd_opening_hours[ $opening_data->dayOfWeek ]['time_html'] .= ', ' . $this->get_time_string( wc_clean( $opening_data->opens ) ) . ' - ' . $this->get_time_string( wc_clean( $opening_data->closes ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			}
		}

		$result->html_content = wc_get_template_html( 'checkout/dhl/parcel-finder-result.php', array( 'result' => $result ) );
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
				'city'     => '',
				'zip'      => '',
				'street'   => '',
				'streetNo' => '',
				'address'  => '',
				'country'  => 'DE',
			)
		);

		if ( ! empty( $address['address'] ) ) {
			$parsed = wc_gzd_split_shipment_street( $address['address'] );

			$address['street']   = $parsed['street'];
			$address['streetNo'] = $parsed['number'];
		}

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
			throw new Exception( _x( 'At least shipping city or zip is required.', 'dhl', 'woocommerce-germanized' ) );
		}

		$args = array(
			'countryCode'     => $address['country'],
			'addressLocality' => $address['city'],
			'postalCode'      => $address['zip'],
			'streetAddress'   => $address['address'],
			'limit'           => $limit,
			'radius'          => 2500,
		);

		if ( array( 'packstation' ) === $types ) {
			$args['locationType'] = 'locker';
		} else {
			$args['serviceType'] = 'parcel:pick-up-all';
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
