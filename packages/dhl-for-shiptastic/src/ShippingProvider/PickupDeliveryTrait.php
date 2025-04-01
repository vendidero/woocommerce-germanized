<?php

namespace Vendidero\Shiptastic\DHL\ShippingProvider;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

trait PickupDeliveryTrait {

	public function supports_pickup_locations() {
		return true;
	}

	public function supports_pickup_location_delivery( $address, $query_args = array() ) {
		if ( ! $this->enable_pickup_location_delivery() ) {
			return false;
		}

		$query_args = $this->parse_pickup_location_query_args( $query_args );
		$address    = $this->parse_pickup_location_address_args( $address );
		$types      = $this->get_pickup_location_types();
		$supports   = in_array( $address['country'], ParcelLocator::get_supported_countries(), true ) && ! empty( $types ) && ! in_array( $query_args['payment_gateway'], ParcelLocator::get_excluded_gateways(), true );

		return $supports;
	}

	protected function fetch_single_pickup_location( $location_code, $address = array() ) {
		$address       = $this->get_address_by_pickup_location_code( $location_code, $address );
		$location_code = $this->parse_pickup_location_code( $location_code );

		if ( empty( $location_code ) ) {
			return false;
		}

		try {
			$result          = Package::get_api()->get_finder_api()->find_by_id( $location_code, $address['country'], $address['postcode'] );
			$pickup_location = $this->get_pickup_location_from_api_response( $result );
		} catch ( \Exception $e ) {
			$pickup_location = null;

			if ( 404 === $e->getCode() ) {
				$pickup_location = false;
			}
		}

		return $pickup_location;
	}

	protected function parse_pickup_location_code( $location_code ) {
		$location_code = wc_stc_parse_pickup_location_code( $location_code );
		$keyword_id    = '';

		preg_match_all( '/([A-Z]{2}-)?[0-9]+/', $location_code, $matches );

		if ( $matches && count( $matches ) > 0 ) {
			if ( isset( $matches[0][0] ) ) {
				$keyword_id = $matches[0][0];
			}
		}

		return $keyword_id;
	}

	/**
	 * @param $data
	 *
	 * @return PickupLocation|false
	 */
	protected function get_pickup_location_instance( $data ) {
		try {
			return new PickupLocation( (array) $data );
		} catch ( \Exception $e ) {
			Package::log( $e, 'error' );
			return false;
		}
	}

	protected function get_pickup_location_from_api_response( $location ) {
		$address = array(
			'company'   => $location->name,
			'country'   => $location->place->address->countryCode,
			'postcode'  => $location->place->address->postalCode,
			'address_1' => $location->place->address->streetAddress,
			'city'      => $location->place->address->addressLocality,
		);

		$supports_customer_number     = true;
		$customer_number_is_mandatory = 'locker' === $location->location->type ? true : false;

		$replacement_map = array(
			'address_1' => 'label',
			'country'   => 'country',
			'postcode'  => 'postcode',
			'city'      => 'city',
			'company'   => '',
		);

		if ( 'DE' !== $address['country'] ) {
			$replacement_map = array(
				'address_2' => 'label',
				'address_1' => 'address_1',
				'country'   => 'country',
				'postcode'  => 'postcode',
				'city'      => 'city',
				'company'   => '',
			);

			$supports_customer_number     = false;
			$customer_number_is_mandatory = false;
		}

		return $this->get_pickup_location_instance(
			array(
				'code'                         => $location->internal_id,
				'type'                         => $location->location->type,
				'label'                        => $location->internal_name,
				'latitude'                     => $location->place->geo->latitude,
				'longitude'                    => $location->place->geo->longitude,
				'supports_customer_number'     => $supports_customer_number,
				'customer_number_is_mandatory' => $customer_number_is_mandatory,
				'address'                      => $address,
				'address_replacement_map'      => $replacement_map,
			)
		);
	}

	protected function get_pickup_location_types() {
		$types = array();

		if ( ParcelLocator::is_packstation_enabled( $this->get_name() ) ) {
			$types[] = 'packstation';
		}

		if ( ParcelLocator::is_parcelshop_enabled( $this->get_name() ) ) {
			$types[] = 'parcelshop';
		}

		if ( ParcelLocator::is_postoffice_enabled( $this->get_name() ) ) {
			$types[] = 'postoffice';
		}

		return $types;
	}

	protected function fetch_pickup_locations( $address, $query_args = array() ) {
		$types     = $this->get_pickup_location_types();
		$locations = array();

		if ( $query_args['shipping_method'] ) {
			$zone = \Vendidero\Shiptastic\Package::get_shipping_zone( $address['country'], array( 'postcode' => $address['postcode'] ) );

			$config_set = $query_args['shipping_method']->get_configuration_set(
				array(
					'zone'                   => $zone,
					'shipment_type'          => 'simple',
					'shipping_provider_name' => $this->get_name(),
				)
			);

			if ( $config_set ) {
				$current_product = $config_set->get_product();

				// Do not allow parcel shops and postoffices for Warenpost
				if ( in_array( $current_product, array( 'V62WP', 'V62WPI' ), true ) ) {
					$types = array_diff( $types, array( 'parcelshop', 'postoffice' ) );
				}
			}
		}

		if ( empty( $types ) ) {
			return null;
		}

		try {
			$location_data = Package::get_api()->get_finder_api()->get_parcel_location(
				array(
					'zip'     => $address['postcode'],
					'country' => $address['country'],
					'city'    => $address['city'],
					'address' => ! empty( $address['postcode'] ) ? $address['address_1'] : '',
				),
				$types,
				$query_args['limit']
			);
		} catch ( \Exception $e ) {
			return null;
		}

		foreach ( $location_data as $location ) {
			if ( $pickup_location = $this->get_pickup_location_from_api_response( $location ) ) {
				$locations[] = $pickup_location;
			}
		}

		return $locations;
	}
}
