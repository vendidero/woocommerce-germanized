<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Exception;
use Vendidero\Shiptastic\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ReturnRest extends PaketRest {

	public function get_title() {
		return _x( 'DHL Paket Return REST', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_paket_return_rest';
	}

	public function get_url() {
		if ( $this->is_sandbox() ) {
			return 'https://api-sandbox.dhl.com/parcel/de/shipping/returns/v1/';
		} else {
			return 'https://api-eu.dhl.com/parcel/de/shipping/returns/v1/';
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\ReturnLabel $label
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_return_label( &$label ) {
		return $this->create_return_label( $label );
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\ReturnLabel $label
	 */
	protected function get_request_args( $label ) {
		$shipment = $label->get_shipment();
		$currency = $shipment->get_order() ? $shipment->get_order()->get_currency() : 'EUR';

		if ( ! $shipment ) {
			throw new Exception( esc_html( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) ) );
		}

		$request_args = array(
			'receiverId'        => $label->get_receiver_id(),
			'customerReference' => wc_stc_dhl_get_return_label_customer_reference( $label, $shipment ),
			'shipmentReference' => '',
			'shipper'           => array(
				'name1'         => $label->get_sender_company() ? $label->get_sender_company() : $label->get_sender_formatted_full_name(),
				'name2'         => $label->get_sender_company() ? $label->get_sender_formatted_full_name() : '',
				/**
				 * By default the name3 parameter is used to transmit the additional
				 * address field to the DHL API. You may adjust the field value by using this filter.
				 *
				 * @param string                                      $value The field value.
				 * @param \Vendidero\Shiptastic\DHL\Label\ReturnLabel $label The label instance.
				 *
				 * @since 3.0.3
				 * @package Vendidero/Shiptastic/DHL
				 */
				'name3'         => apply_filters( 'woocommerce_shiptastic_dhl_return_label_api_sender_name3', $label->get_sender_address_addition(), $label ),
				'addressStreet' => $label->get_sender_street(),
				'addressHouse'  => wc_stc_dhl_get_return_label_sender_street_number( $label ),
				'postalCode'    => $label->get_sender_postcode(),
				'city'          => $label->get_sender_city(),
				'state'         => $label->get_sender_state(),
				'country'       => wc_stc_country_to_alpha3( $label->get_sender_country() ),
			),
			'itemWeight'        => array(
				'uom'   => 'kg',
				'value' => $label->get_weight(),
			),
			'itemValue'         => array(
				'currency' => $currency,
				'value'    => $shipment->get_total(),
			),
		);

		if ( Package::is_crossborder_shipment( $label->get_sender_country(), $label->get_sender_postcode() ) ) {
			$items        = array();
			$customs_data = wc_stc_dhl_get_shipment_customs_data( $label );

			foreach ( $customs_data['items'] as $customs_item ) {
				$items[] = array(
					'itemDescription'  => wc_shiptastic_substring( $customs_item['description'], 0, 50 ),
					'packagedQuantity' => $customs_item['quantity'],
					/**
					 * Total weight per row
					 */
					'itemWeight'       => array(
						'uom'   => 'kg',
						'value' => $customs_item['weight_in_kg'],
					),
					'countryOfOrigin'  => wc_stc_country_to_alpha3( $customs_item['origin_code'] ),
					'hsCode'           => $customs_item['tariff_number'],
					'itemValue'        => array(
						'currency' => in_array( strtoupper( $customs_data['currency'] ), array( 'EUR', 'GBP', 'CHF', 'USD', 'CZK', 'SGD' ), true ) ? strtoupper( $customs_data['currency'] ) : 'EUR',
						'value'    => $customs_item['value'],
					),
				);
			}

			$request_args['customsDetails'] = apply_filters(
				'woocommerce_stc_dhl_retoure_customs_data',
				array(
					'items' => $items,
				),
				$label
			);
		}

		return $request_args;
	}

	public function get_receiver_ids() {
		$response     = $this->get( 'locations' );
		$receiver_ids = array();

		if ( $response->is_error() ) {
			return $response->get_error();
		} else {
			$receivers = $response->get_body();

			if ( is_array( $receivers ) ) {
				foreach ( $receivers as $receiver ) {
					if ( ! isset( $receiver['receiverId'], $receiver['shipperCountry'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						continue;
					}

					$receiver_id      = wc_clean( $receiver['receiverId'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$receiver_country = wc_stc_country_to_alpha2( wc_clean( strtoupper( $receiver['shipperCountry'] ) ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$slug             = sanitize_key( $receiver_id . '_' . $receiver_country );

					$receiver_ids[ $slug ] = array(
						'slug'    => $slug,
						'id'      => $receiver_id,
						'country' => $receiver_country,
					);
				}
			}
		}

		return $receiver_ids;
	}

	public function create_return_label( $label ) {
		try {
			$request_args = $this->get_request_args( $label );
			Package::log( 'Call returns API: ' . wc_print_r( $request_args, true ) );

			$args = array(
				'labelType' => 'SHIPMENT_LABEL',
			);

			$endpoint = add_query_arg( $args, 'orders' );
			$response = $this->post( $endpoint, $request_args );

			if ( $response->is_error() ) {
				throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), esc_html( $response->get_code() ) );
			} else {
				$response_body = $response->get_body();

				try {
					if ( isset( $response_body['shipmentNo'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$label->set_number( $response_body['shipmentNo'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}

					$path = false;

					if ( isset( $response_body['label']['b64'] ) ) {
						$default_file = base64_decode( $response_body['label']['b64'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

						// Store the downloaded label as default file
						$path = $label->upload_label_file( $default_file );
					}

					if ( ! $path ) {
						throw new Exception( 'Error while uploading the return label' );
					}
				} catch ( Exception $e ) {
					// Delete the label dues to errors.
					$label->delete();

					throw new Exception( esc_html_x( 'Error while creating and uploading the label', 'dhl', 'woocommerce-germanized' ) );
				}

				return $label;
			}
		} catch ( Exception $e ) {
			Package::log( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}
	}
}
