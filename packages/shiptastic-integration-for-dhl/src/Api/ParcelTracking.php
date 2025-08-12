<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;
use Vendidero\Shiptastic\Tracking\ShipmentStatus;

defined( 'ABSPATH' ) || exit;

class ParcelTracking extends \Vendidero\Shiptastic\API\REST {

	public function get_title() {
		return _x( 'DHL DE Parcel Tracking', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_de_parcel_tracking';
	}

	public function get_url() {
		return $this->is_sandbox() ? 'https://api-sandbox.dhl.com/parcel/de/tracking/v0/' : 'https://api-eu.dhl.com/parcel/de/tracking/v0/';
	}

	protected function get_auth_instance() {
		return new BasicAuthParcelTracking( $this );
	}

	/**
	 * @param Shipment[] $shipments
	 *
	 * @return ShipmentStatus[]|ShipmentError
	 */
	public function get_bulk_statuses( $shipments ) {
		return $this->get_statuses( $shipments );
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return ShipmentStatus|ShipmentError
	 */
	public function get_status( $shipment ) {
		$statuses = $this->get_statuses( $shipment );

		if ( ! is_wp_error( $statuses ) ) {
			if ( array_key_exists( $shipment->get_tracking_id(), $statuses ) ) {
				return $statuses[ $shipment->get_tracking_id() ];
			}
		} else {
			return $statuses;
		}

		return new ShipmentError( 500, _x( 'Could not resolve remote shipment status.', 'dhl', 'woocommerce-germanized' ) );
	}

	protected function get_headers( $headers = array() ) {
		$headers = parent::get_headers( $headers );

		$headers['Content-Type'] = 'text/xml';
		$headers['Accept']       = '*/*';
		$headers['DHL-API-Key']  = Package::get_dhl_com_api_key();

		return $headers;
	}

	protected function parse_response( $response_code, $response_body, $response_headers ) {
		$response_obj = parent::parse_response( $response_code, $response_body, $response_headers );

		if ( ! $response_obj->is_error() ) {
			if ( $dom = $response_obj->get_xml() ) {
				$list = $dom->documentElement; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				if ( $list ) {
					if ( $error = $list->getAttribute( 'error' ) ) {
						$code = $list->getAttribute( 'code' );

						if ( ! empty( $error ) ) {
							$response_obj->set_error( new \WP_Error( $code, wc_clean( $error ) ) );
						}
					}
				} else {
					$response_obj->set_error( new \WP_Error( 100, _x( 'No xml element found.', 'dhl', 'woocommerce-germanized' ) ) );
				}
			}
		}

		return $response_obj;
	}

	/**
	 * @param Shipment[]|Shipment $shipments
	 *
	 * @note: Sandbox testing does only work for certain tracking ids:
	 *
	 * 00340434161094042557
	 * 00340434161094038253
	 * 00340434161094032954
	 * 00340434161094027318
	 * 00340434161094022115
	 * 00340434161094015902
	 *
	 * @return ShipmentStatus[]|ShipmentError
	 */
	protected function get_statuses( $shipments ) {
		$shipment_list = array();
		$shipments     = ! is_array( $shipments ) ? array( $shipments ) : $shipments;
		$postcode      = 1 === count( $shipments ) ? $shipments[0]->get_postcode() : '';
		$status_list   = array();
		$errors        = new ShipmentError();

		foreach ( $shipments as $shipment ) {
			if ( $shipment->get_tracking_id() ) {
				$shipment_list[ $shipment->get_tracking_id() ] = $shipment;
			}
		}

		if ( ! empty( $shipment_list ) ) {
			$chunks = array_chunk( $shipment_list, 20, true ); // DHL API supports submitting 20 shipments per request

			foreach ( $chunks as $chunked_shipments ) {
				$xml      = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><data appname="' . esc_attr( $this->get_auth_api()->get_xml_username() ) . '" language-code="de" zip-code="' . esc_attr( $postcode ) . '" password="' . esc_attr( $this->get_auth_api()->get_xml_password() ) . '" piece-code="' . esc_attr( implode( ';', array_keys( $chunked_shipments ) ) ) . '" request="d-get-piece-detail" />';
				$response = $this->get( 'shipments', array( 'xml' => rawurlencode( $xml ) ) );

				if ( $response->is_error() ) {
					$errors->add( $response->get_error()->get_error_code(), $response->get_error()->get_error_message() );
					Package::log( sprintf( 'Error while retrieving remote shipment statuses: %s', $response->get_error()->get_error_message() ) );
				} else {
					$dom  = $response->get_xml();
					$list = $dom->documentElement; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					if ( $list ) {
						foreach ( $list->childNodes as $piece_shipment ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							if ( 'piece-shipment' === $piece_shipment->getAttribute( 'name' ) ) {
								$piece_code       = wc_clean( $piece_shipment->getAttribute( 'piece-code' ) );
								$status_timestamp = wc_clean( $piece_shipment->getAttribute( 'status-timestamp' ) );
								$status           = wc_clean( $piece_shipment->getAttribute( 'status' ) );
								$status_short     = wc_clean( $piece_shipment->getAttribute( 'short-status' ) );
								$delivery_flag    = wc_clean( $piece_shipment->getAttribute( 'delivery-event-flag' ) );
								$ice_flag         = wc_clean( $piece_shipment->getAttribute( 'ice' ) ); // see https://developer.dhl.com/api-reference/dhl-paket-de-sendungsverfolgung-post-paket-deutschland#downloads-section

								try {
									$status_datetime = new \WC_DateTime( $status_timestamp, new \DateTimeZone( 'Europe/Berlin' ) );
									$status_datetime->setTimezone( new \DateTimeZone( 'UTC' ) );
								} catch ( \Exception $e ) {
									$status_datetime = null;
								}

								if ( array_key_exists( $piece_code, $shipment_list ) ) {
									$is_delivered  = (bool) $delivery_flag;
									$is_in_transit = ! $is_delivered;

									/**
									 * PARCV = PAN Received by Carrier (elektronische Übermittlung)
									 */
									if ( in_array( $ice_flag, array( 'PARCV' ), true ) ) {
										$is_in_transit = false;
									}

									$status_list[ $piece_code ] = new ShipmentStatus(
										$shipment_list[ $piece_code ],
										array(
											'status'       => $status_short,
											'status_description' => $status,
											'is_delivered' => $is_delivered,
											'is_in_transit' => $is_in_transit,
											'last_updated' => $status_datetime,
											'ice'          => $ice_flag,
											'delivered_at' => true === (bool) $delivery_flag ? $status_datetime : null,
										)
									);
								}
							}
						}
					}
				}
			}
		}

		if ( empty( $status_list ) && $errors->has_errors() ) {
			return $errors;
		} else {
			return $status_list;
		}
	}
}
