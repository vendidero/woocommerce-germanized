<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Label\ReturnLabel;
use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ReturnRest extends Rest {

	public function __construct() {}

	/**
	 * @param \Vendidero\Germanized\DHL\Label\ReturnLabel $label
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_return_label( &$label ) {
		return $this->create_return_label( $label );
	}

	/**
	 * @param \Vendidero\Germanized\DHL\Label\ReturnLabel $label
	 */
	protected function get_request_args( $label ) {
		$shipment     = $label->get_shipment();
		$countries    = WC()->countries->get_countries();
		$country_name = $label->get_sender_country();

		if ( ! $shipment ) {
			throw new Exception( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) );
		}

		$order = $shipment->get_order();

		if ( isset( $countries[ $country_name ] ) ) {
			$country_name = $countries[ $country_name ];
		}

		$label->get_sender_country();

		$request_args = array(
			'receiverId'         => $label->get_receiver_id(),
			'customerReference'  => wc_gzd_dhl_get_return_label_customer_reference( $label, $shipment ),
			'shipmentReference'  => '',
			'senderAddress'      => array(
				'name1'       => $label->get_sender_company() ? $label->get_sender_company() : $label->get_sender_formatted_full_name(),
				'name2'       => $label->get_sender_company() ? $label->get_sender_formatted_full_name() : '',
				/**
				 * By default the name3 parameter is used to transmit the additional
				 * address field to the DHL API. You may adjust the field value by using this filter.
				 *
				 * @param string                                      $value The field value.
				 * @param \Vendidero\Germanized\DHL\Label\ReturnLabel $label The label instance.
				 *
				 * @since 3.0.3
				 * @package Vendidero/Germanized/DHL
				 */
				'name3'       => apply_filters( 'woocommerce_gzd_dhl_return_label_api_sender_name3', $label->get_sender_address_addition(), $label ),
				'streetName'  => $label->get_sender_street(),
				'houseNumber' => wc_gzd_dhl_get_return_label_sender_street_number( $label ),
				'postCode'    => $label->get_sender_postcode(),
				'city'        => $label->get_sender_city(),
				'country'     => array(
					'countryISOCode' => Package::get_country_iso_alpha3( $label->get_sender_country() ),
					'country'        => $country_name,
					'state'          => $label->get_sender_state(),
				),
			),
			'email'              => Package::get_setting( 'return_email' ),
			'telephoneNumber'    => Package::get_setting( 'return_phone' ),
			'weightInGrams'      => wc_get_weight( $label->get_weight(), 'g', 'kg' ),
			'value'              => $shipment->get_total(),
			'returnDocumentType' => 'SHIPMENT_LABEL',
		);

		if ( Package::is_crossborder_shipment( $label->get_sender_country(), $label->get_sender_postcode() ) ) {
			$items          = array();
			$customs_data   = wc_gzd_dhl_get_shipment_customs_data( $label );
			$shipment_items = $shipment->get_items();

			foreach ( $customs_data['items'] as $key => $customs_item ) {
				$shipment_item = $shipment_items[ $key ];

				$items[] = array(
					'positionDescription' => $customs_item['description'],
					'count'               => $customs_item['quantity'],
					/**
					 * Total weight per row
					 */
					'weightInGrams'       => intval( wc_get_weight( $customs_item['weight_in_kg'], 'g', 'kg' ) ),
					/**
					 * Total value per row
					 */
					'values'              => $customs_item['value'],
					'originCountry'       => Package::get_country_iso_alpha3( $customs_item['origin_code'] ),
					'articleReference'    => apply_filters( 'woocommerce_gzd_dhl_retoure_customs_article_reference', $customs_item['category'], $shipment_item, $label ),
					'tarifNumber'         => $customs_item['tariff_number'],
					'currency'            => in_array( strtoupper( $customs_data['currency'] ), array( 'EUR', 'GBP', 'CHF' ), true ) ? strtoupper( $customs_data['currency'] ) : 'EUR',
				);
			}

			$request_args['customsDocument'] = apply_filters(
				'woocommerce_gzd_dhl_retoure_customs_data',
				array(
					'currency'               => $order ? $order->get_currency() : 'EUR',
					'originalShipmentNumber' => $shipment->get_order_number(),
					'originalOperator'       => $shipment->get_shipping_provider(),
					'positions'              => $items,
				),
				$label
			);
		}

		return $request_args;
	}

	public function create_return_label( &$label ) {
		try {
			$request_args = $this->get_request_args( $label );
			Package::log( 'Call returns API: ' . wc_print_r( $request_args, true ) );

			$result = $this->post_request( '/returns/', wp_json_encode( $request_args ) );
		} catch ( Exception $e ) {
			Package::log( 'Response Error: ' . $e->getMessage() );
			throw $e;
		}

		return $this->update_return_label( $label, $result );
	}

	/**
	 * @param ReturnLabel $label
	 * @param $response_body
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function update_return_label( $label, $response_body ) {
		try {
			if ( isset( $response_body->shipmentNumber ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$label->set_number( $response_body->shipmentNumber ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$default_file = base64_decode( $response_body->labelData ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			// Store the downloaded label as default file
			$path = $label->upload_label_file( $default_file );

			if ( ! $path ) {
				throw new Exception( 'Error while uploading the return label' );
			}
		} catch ( Exception $e ) {
			// Delete the label dues to errors.
			$label->delete();

			throw new Exception( _x( 'Error while creating and uploading the label', 'dhl', 'woocommerce-germanized' ) );
		}

		return $label;
	}

	protected function get_retoure_auth() {
		return base64_encode( Package::get_retoure_api_user() . ':' . Package::get_retoure_api_signature() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		parent::set_header();

		if ( ! empty( $authorization ) ) {
			$this->remote_header['Authorization'] = $authorization;
		}

		$this->remote_header['DPDHL-User-Authentication-Token'] = $this->get_retoure_auth();
	}
}
