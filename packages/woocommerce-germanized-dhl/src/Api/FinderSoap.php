<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

class FinderSoap extends Soap {

	protected $types = array();

	protected $limit = 15;

    public function __construct( ) {
        try {
            parent::__construct( Package::get_parcel_finder_api_url() );
        } catch ( Exception $e ) {
            throw $e;
        }
    }

	public function get_access_token() {
		return $this->get_auth_api()->get_access_token();
	}

	protected function get_translated_weekday( $number ) {
    	$weekdays = array(
		    _x( 'Monday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Tuesday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Wednesday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Thursday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Friday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Saturday', 'dhl', 'woocommerce-germanized' ),
		    _x( 'Sunday', 'dhl', 'woocommerce-germanized' ),
	    );

    	if ( isset( $weekdays[ $number -1 ] ) ) {
    		return $weekdays[ $number - 1 ];
	    }

    	return false;
	}

	public function get_parcel_location( $address, $types = array(), $limit = false ) {

    	$address = wp_parse_args( $address, array(
		    'city'     => '',
		    'zip'      => '',
		    'street'   => '',
		    'streetNo' => '',
		    'address'  => '',
	    ) );

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

    	$results     = array();
		$api_results = false;
		$this->types = empty( $types ) ? $default_types : $types;
		$this->limit = is_numeric( $limit ) ? $limit : ParcelLocator::get_max_results();

		if ( empty( $address['city'] ) && empty( $address['zip'] ) ) {
			throw new Exception( _x( 'At least shipping city or zip is required.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( $this->types == array( 'packstation' ) ) {
			$api_results = $this->get_packstations( $address );
		} else {
			$api_results = $this->get_packstations_filiale_direkt( $address );
		}

		if ( is_array( $api_results ) ) {
			foreach( $api_results as $result ) {

				// Lets assume it is a postoffice by default
				$result->gzd_type          = 'postoffice';
				$result->gzd_id            = isset( $result->depotServiceNo ) ? $result->depotServiceNo : '';
				$result->gzd_result_id     = $result->id;
				$result->gzd_opening_hours = array();

				if ( isset( $result->branchTypePF ) && 'dhlpaketshop' === $result->branchTypePF ) {
					$result->gzd_type = 'parcelshop';
				}

				if ( isset ( $result->packstationId ) && ! empty( $result->packstationId ) ) {
					$result->gzd_type = 'packstation';
					$result->gzd_id   = $result->packstationId;
				}

				$result->gzd_name = sprintf( _x( '%s %s', 'dhl location name', 'woocommerce-germanized' ), wc_gzd_dhl_get_pickup_type( $result->gzd_type ), $result->gzd_id );

				if ( isset( $result->timeinfos ) ) {
					foreach( $result->timeinfos->timeinfo as $time ) {

						// Opening hours have type 1
						if ( 1 !== $time->type ) {
							continue;
						}

						if ( ! isset( $result->gzd_opening_hours[ $time->dayTo ] ) ) {
							$result->gzd_opening_hours[ $time->dayTo ] = array(
								'weekday'   => $this->get_translated_weekday( $time->dayTo ),
								'time_html' => $time->timeFrom . ' - ' . $time->timeTo,
							);
						} else {
							$result->gzd_opening_hours[ $time->dayTo ]['time_html'] .= ', ' . $time->timeFrom . ' - ' . $time->timeTo;
						}
					}

					ksort( $result->gzd_opening_hours );
				}

				// Not supporting this type
				if ( ! in_array( $result->gzd_type, $this->types ) ) {
					continue;
				}

				$result->html_content = wc_get_template_html( 'checkout/dhl/parcel-finder-result.php', array( 'result' => $result ) );

				if ( sizeof( $results ) >= $this->limit ) {
					break;
				}

				$results[] = $result;
			}
		}

		return $results;
    }

    protected function get_packstations_filiale_direkt( $address ) {
	    try {
		    $soap_request = array(
		    	'address' => $address,
			    'key'     => '',
		    );

		    $soap_client  = $this->get_access_token();

		    Package::log( '"getPackstationsFilialeDirektByAddress" called with: ' . print_r( $address, true ) );
		    $response_body = $soap_client->getPackstationsFilialeDirektByAddress( $soap_request );

		    return $response_body->packstation_filialedirekt;

	    } catch ( Exception $e ) {
		    Package::log( 'Response Error: ' . $e->getMessage(), 'error' );
		    throw $e;
	    }
    }

    protected function get_packstations( $address ) {
	    try {
		    $soap_request = array(
			    'address' => $address,
			    'key'     => '',
		    );

		    $soap_client  = $this->get_access_token();

		    Package::log( '"getPackstationsByAddress" called with: ' . print_r( $address, true ) );
		    $response_body = $soap_client->getPackstationsByAddress( $soap_request );

		    return $response_body->packstation;

	    } catch ( Exception $e ) {
		    Package::log( 'Response Error: ' . $e->getMessage(), 'error' );
		    throw $e;
	    }
    }

    protected function get_paketboxes() {

    }

    protected function get_request( $args ) {}
}
