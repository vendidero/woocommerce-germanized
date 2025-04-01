<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\Label;
use Vendidero\Shiptastic\DHL\ParcelLocator;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\Labels\Factory;
use Vendidero\Shiptastic\PDFMerger;
use Vendidero\Shiptastic\PDFSplitter;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class LabelRest extends PaketRest {

	public function get_title() {
		return _x( 'DHL Paket Label REST', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_paket_label_rest';
	}

	public function get_url() {
		if ( $this->is_sandbox() ) {
			return 'https://api-sandbox.dhl.com/parcel/de/shipping/v2/';
		} else {
			return 'https://api-eu.dhl.com/parcel/de/shipping/v2/';
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\DHL $label
	 *
	 * @throws \Exception
	 */
	public function get_label( $label ) {
		return $this->create_label( $label );
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\DHL $label
	 *
	 * @return boolean|ShipmentError
	 * @throws \Exception
	 */
	public function create_label( $label ) {
		$result       = true;
		$shipment     = $label->get_shipment();
		$dhl_provider = Package::get_dhl_shipping_provider();

		if ( ! $shipment ) {
			throw new \Exception( esc_html( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) ) );
		}

		$currency            = $shipment->get_order() ? $shipment->get_order()->get_currency() : 'EUR';
		$billing_number_args = array(
			'api_type'   => 'dhl.com',
			'services'   => $label->get_services(),
			'is_sandbox' => $this->is_sandbox(),
		);

		$account_number = wc_stc_dhl_get_billing_number( $label->get_product_id(), $billing_number_args );
		$services       = array();
		$bank_data      = array();

		foreach ( $label->get_services() as $service ) {
			$service_name = lcfirst( $service );

			if ( in_array( $service, array( 'GoGreen', 'dhlRetoure' ), true ) ) {
				continue;
			}

			switch ( $service ) {
				case 'AdditionalInsurance':
					$services[ $service_name ] = array(
						'currency' => $currency,
						'value'    => apply_filters( 'woocommerce_shiptastic_dhl_label_api_insurance_amount', $label->get_insurance_amount(), $shipment, $label ),
					);
					break;
				case 'IdentCheck':
					$services[ $service_name ] = array(
						'firstName'   => wc_shiptastic_substring( $shipment->get_first_name(), 0, 35 ),
						'lastName'    => wc_shiptastic_substring( $shipment->get_last_name(), 0, 35 ),
						'dateOfBirth' => $label->get_ident_date_of_birth(),
						'minimumAge'  => $label->get_ident_min_age(),
					);
					break;
				case 'CashOnDelivery':
					$bank_data_map = array(
						'bank_holder' => 'accountHolder',
						'bank_name'   => 'bankName',
						'bank_iban'   => 'iban',
						'bank_bic'    => 'bic',
						'bank_ref'    => 'transferNote1',
						'bank_ref_2'  => 'transferNote2',
					);

					$ref_replacements = wc_stc_dhl_get_label_payment_ref_placeholder( $shipment );

					foreach ( $bank_data_map as $key => $value ) {
						if ( $setting_value = Package::get_setting( $key ) ) {
							$bank_data[ $value ] = $setting_value;

							if ( in_array( $key, array( 'bank_ref', 'bank_ref_2' ), true ) ) {
								$bank_data[ $value ] = str_replace( array_keys( $ref_replacements ), array_values( $ref_replacements ), $bank_data[ $value ] );
							}
						}
					}

					$services[ $service_name ] = array(
						'amount'        => array(
							'currency' => $currency,
							'value'    => $label->get_cod_total(),
						),
						'bankAccount'   => array_diff_key(
							$bank_data,
							array(
								'transferNote1' => '',
								'transferNote2' => '',
							)
						),
						'transferNote1' => $bank_data['transferNote1'],
						'transferNote2' => $bank_data['transferNote2'],
					);
					break;
				case 'PreferredDay':
					$services[ $service_name ] = $label->get_preferred_day();
					break;
				case 'VisualCheckOfAge':
					$services[ $service_name ] = $label->get_visual_min_age();
					break;
				case 'PreferredLocation':
					$services[ $service_name ] = $label->get_preferred_location();
					break;
				case 'PreferredNeighbour':
					$services[ $service_name ] = $label->get_preferred_neighbor();
					break;
				case 'ParcelOutletRouting':
					$services[ $service_name ] = wc_stc_dhl_get_parcel_outlet_routing_email_address( $shipment );
					break;
				case 'CDP':
					$services['closestDropPoint'] = true;
					break;
				case 'PDDP':
					$services['postalDeliveryDutyPaid'] = true;
					break;
				case 'Endorsement':
					$services[ $service_name ] = wc_stc_dhl_get_label_endorsement_type( $label, $shipment, 'dhl.com' );
					break;
				default:
					$services[ $service_name ] = true;
			}
		}

		if ( $label->has_inlay_return() ) {
			$services['dhlRetoure'] = array(
				'billingNumber' => wc_stc_dhl_get_billing_number( 'return', $billing_number_args ),
				'refNo'         => wc_stc_dhl_get_inlay_return_label_reference( $label, $shipment ),
				'returnAddress' => array(
					'name1'         => wc_shiptastic_substring( $label->get_return_company() ? $label->get_return_company() : $label->get_return_formatted_full_name(), 0, 50 ),
					'name2'         => wc_shiptastic_substring( $label->get_return_company() ? $label->get_return_formatted_full_name() : '', 0, 50 ),
					'addressStreet' => wc_shiptastic_substring( $label->get_return_street() . ' ' . $label->get_return_street_number(), 0, 50 ),
					'postalCode'    => $label->get_return_postcode(),
					'city'          => $label->get_return_city(),
					'state'         => wc_shiptastic_substring( wc_stc_dhl_format_label_state( $label->get_return_state(), $label->get_return_country() ), 0, 20 ),
					'contactName'   => wc_shiptastic_substring( $label->get_return_formatted_full_name(), 0, 80 ),
					'phone'         => $label->get_return_phone(),
					'email'         => $label->get_return_email(),
					'country'       => wc_stc_country_to_alpha3( $label->get_return_country() ),
				),
			);
		}

		$shipment_request = array(
			'product'       => $label->get_product_id(),
			'billingNumber' => $account_number,
			'refNo'         => wc_shiptastic_substring( wc_stc_dhl_get_label_customer_reference( $label, $shipment ), 0, 35 ),
			'shipDate'      => Package::get_date_de_timezone( 'Y-m-d' ),
			'shipper'       => array(),
			'consignee'     => array(),
			'details'       => array(
				'weight' => array(
					'uom'   => 'kg',
					'value' => $label->get_weight(),
				),
			),
		);

		if ( ! empty( $services ) ) {
			$shipment_request['services'] = $services;
		}

		if ( $label->has_dimensions() ) {
			$height_in_mm = wc_format_decimal( wc_get_dimension( $label->get_height(), 'mm', 'cm' ), 0 );
			$width_in_mm  = wc_format_decimal( wc_get_dimension( $label->get_width(), 'mm', 'cm' ), 0 );
			$length_in_mm = wc_format_decimal( wc_get_dimension( $label->get_length(), 'mm', 'cm' ), 0 );

			/**
			 * Somehow new DHL REST API fails in case the officially
			 * provided max length (35,3 cm) for Warenpost is used in mm precision. Round down.
			 */
			if ( in_array( $label->get_product_id(), array( 'V62WP', 'V66WPI', 'V62KP' ), true ) ) {
				$height_in_mm = round( $height_in_mm, -1, PHP_ROUND_HALF_DOWN );
				$width_in_mm  = round( $width_in_mm, -1, PHP_ROUND_HALF_DOWN );
				$length_in_mm = round( $length_in_mm, -1, PHP_ROUND_HALF_DOWN );
			}

			$shipment_request['details']['dim'] = array(
				'uom'    => 'mm',
				'height' => $height_in_mm,
				'width'  => $width_in_mm,
				'length' => $length_in_mm,
			);
		}

		/**
		 * This filter allows using a ShipperReference configured in the GKP instead of transmitting
		 * the shipper data from the DHL settings. Use this filter carefully and make sure that the
		 * reference exists.
		 *
		 * @param string $shipper_reference The shipper reference from the GKP.
		 * @param Label\DHL  $label The label instance.
		 *
		 * @since 3.0.5
		 * @package Vendidero/Shiptastic/DHL
		 */
		$shipper_reference = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_reference', $dhl_provider->has_custom_shipper_reference() ? $dhl_provider->get_label_custom_shipper_reference() : '', $label );

		if ( ! empty( $shipper_reference ) ) {
			$shipment_request['shipper']['shipperRef'] = $shipper_reference;
		} else {
			$name1   = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_name1', trim( $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name() ), $label );
			$name2   = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_name2', trim( $shipment->get_sender_company() ? $shipment->get_formatted_sender_full_name() : '' ), $label );
			$name3   = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_name3', trim( $shipment->get_sender_address_2() ), $label );
			$street  = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_street', $shipment->get_sender_address_1(), $label );
			$zip     = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_zip', $shipment->get_sender_postcode(), $label );
			$city    = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_city', $shipment->get_sender_city(), $label );
			$email   = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_email', $shipment->get_sender_email(), $label );
			$country = apply_filters( 'woocommerce_shiptastic_dhl_label_api_shipper_country', $shipment->get_sender_country(), $label );

			$fields_necessary = array(
				'street'    => $street,
				'full_name' => $name1,
				'postcode'  => $zip,
				'city'      => $city,
			);

			$address_fields         = wc_stc_get_shipment_setting_default_address_fields();
			$missing_address_fields = array();

			foreach ( $fields_necessary as $field => $value ) {
				if ( empty( $value ) && array_key_exists( $field, $address_fields ) ) {
					$missing_address_fields[] = $address_fields[ $field ];
				}
			}

			if ( ! empty( $missing_address_fields ) ) {
				throw new \Exception( wp_kses_post( sprintf( _x( 'Your shipper address is incomplete (%1$s). Please validate your <a href="%2$s">settings</a> and try again.', 'dhl', 'woocommerce-germanized' ), implode( ', ', $missing_address_fields ), esc_url( Settings::get_settings_url( 'general', 'business_information' ) ) ) ) );
			}

			$shipment_request['shipper'] = array(
				'name1'         => wc_shiptastic_substring( $name1, 0, 50 ),
				'name2'         => wc_shiptastic_substring( $name2, 0, 50 ),
				'name3'         => wc_shiptastic_substring( $name3, 0, 50 ),
				'addressStreet' => wc_shiptastic_substring( $street, 0, 50 ),
				'postalCode'    => $zip,
				'city'          => wc_shiptastic_substring( $city, 0, 40 ),
				'country'       => wc_stc_country_to_alpha3( $country ),
				'email'         => $email,
				'contactName'   => wc_shiptastic_substring( trim( $shipment->get_formatted_sender_full_name() ), 0, 80 ),
			);
		}

		if ( 'DE' === $shipment->get_country() && $shipment->send_to_external_pickup() ) {
			if ( $shipment->send_to_external_pickup( 'locker' ) ) {
				$shipment_request['consignee'] = array(
					'name'       => $shipment->get_formatted_full_name(),
					'lockerID'   => (int) wc_stc_parse_pickup_location_code( $shipment->get_pickup_location_code() ),
					'postNumber' => $shipment->get_pickup_location_customer_number(),
					'city'       => $shipment->get_city(),
					'postalCode' => $shipment->get_postcode(),
					'country'    => wc_stc_country_to_alpha3( $shipment->get_country() ),
				);
			} else {
				$shipment_request['consignee'] = array(
					'name'       => $shipment->get_formatted_full_name(),
					'retailID'   => (int) $shipment->get_pickup_location_code(),
					'city'       => $shipment->get_city(),
					'postalCode' => $shipment->get_postcode(),
					'country'    => wc_stc_country_to_alpha3( $shipment->get_country() ),
				);

				if ( ! empty( $shipment->get_pickup_location_customer_number() ) ) {
					$shipment_request['consignee']['postNumber'] = $shipment->get_pickup_location_customer_number();
				} else {
					$shipment_request['consignee']['email'] = $shipment->get_email();
				}
			}
		} else {
			$street_number   = $shipment->get_address_street_number();
			$street_addition = $shipment->get_address_street_addition();
			$address_1       = $shipment->get_address_1();
			$address_2       = $shipment->get_address_2();

			if ( empty( $street_number ) && ! empty( $address_2 ) ) {
				$address_1_tmp   = wc_stc_split_shipment_street( $address_1 . ' ' . $address_2 );
				$address_1       = $address_1_tmp['street'] . ' ' . $address_1_tmp['number'];
				$address_2       = '';
				$street_addition = $address_1_tmp['addition'];
			}

			$shipment_request['consignee'] = array(
				'name1'                         => wc_shiptastic_substring( $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name(), 0, 50 ),
				'name2'                         => wc_shiptastic_substring( $shipment->get_company() ? $shipment->get_formatted_full_name() : '', 0, 50 ),
				/**
				 * By default the name3 parameter is used to transmit the additional
				 * address field to the DHL API. You may adjust the field value by using this filter.
				 *
				 * @param string $value The field value.
				 * @param Label\DHL  $label The label instance.
				 *
				 * @since 3.0.3
				 * @package Vendidero/Shiptastic/DHL
				 */
				'name3'                         => wc_shiptastic_substring( apply_filters( 'woocommerce_shiptastic_dhl_label_api_receiver_name3', $address_2, $label ), 0, 50 ),
				'addressStreet'                 => $address_1,
				'additionalAddressInformation1' => wc_shiptastic_substring( $street_addition, 0, 60 ),
				'postalCode'                    => $shipment->get_postcode(),
				'city'                          => $shipment->get_city(),
				'state'                         => wc_shiptastic_substring( wc_stc_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() ), 0, 20 ),
				'country'                       => wc_stc_country_to_alpha3( $shipment->get_country() ),
				/**
				 * Choose whether to transmit the full name of the shipment receiver as contactPerson
				 * while creating a label.
				 *
				 * @param string $name The name of the shipmen receiver.
				 * @param Label\DHL  $label The label instance.
				 *
				 * @since 3.0.5
				 * @package Vendidero/Shiptastic/DHL
				 */
				'contactName'                   => wc_shiptastic_substring( apply_filters( 'woocommerce_shiptastic_dhl_label_api_communication_contact_person', $shipment->get_formatted_full_name(), $label ), 0, 80 ),
				/**
				 * Choose whether to transfer the phone number to DHL on creating a label.
				 * By default the phone number is not transmitted.
				 *
				 * @param string $phone The phone number.
				 * @param Label\DHL  $label The label instance.
				 *
				 * @since 3.0.3
				 * @package Vendidero/Shiptastic/DHL
				 */
				'phone'                         => apply_filters( 'woocommerce_shiptastic_dhl_label_api_communication_phone', '', $label ),
				/**
				 * Choose whether to transfer the email to DHL on creating a label.
				 * By default the email is only transmitted if the customer opted in.
				 *
				 * This email address is not used to notify the customer via DHL. It is only
				 * meant for communicaton purposes.
				 *
				 * @param string $email The email.
				 * @param Label\DHL  $label The label instance.
				 *
				 * @since 3.0.3
				 * @package Vendidero/Shiptastic/DHL
				 */
				'email'                         => apply_filters( 'woocommerce_shiptastic_dhl_label_api_communication_email', $label->has_email_notification() || isset( $services['closestDropPoint'] ) ? $shipment->get_email() : '', $label ),
			);

			/**
			 * Force email notification for pickup location deliveries to third-party countries.
			 */
			if ( $shipment->send_to_external_pickup() ) {
				$shipment_request['consignee']['email'] = $shipment->get_email();
			}
		}

		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			if ( count( $shipment->get_items() ) > 30 ) {
				throw new \Exception( esc_html( sprintf( _x( 'Only %1$s shipment items can be processed, your shipment has %2$s items.', 'dhl', 'woocommerce-germanized' ), 30, count( $shipment->get_items() ) ) ) );
			}

			$customs_label_data = wc_stc_dhl_get_shipment_customs_data( $label );
			$customs_items      = array();

			foreach ( $customs_label_data['items'] as $item_id => $item_data ) {
				$customs_items[] = array(
					'itemDescription'  => $item_data['description'],
					'countryOfOrigin'  => wc_stc_country_to_alpha3( $item_data['origin_code'] ),
					'hsCode'           => $item_data['tariff_number'],
					'packagedQuantity' => $item_data['quantity'],
					'itemValue'        => array(
						'currency' => $customs_label_data['currency'],
						'value'    => $item_data['single_value'],
					),
					'itemWeight'       => array(
						'uom'   => 'kg',
						'value' => $item_data['single_weight_in_kg'],
					),
				);
			}

			/**
			 * In case the customs item total weight is greater than label weight (e.g. due to rounding issues) replace it
			 */
			if ( $customs_label_data['item_total_weight_in_kg'] > $label->get_weight() ) {
				$shipment_request['details']['weight']['value'] = $customs_label_data['item_total_weight_in_kg'] + wc_get_weight( $shipment->get_packaging_weight(), 'kg', $shipment->get_weight_unit() );
			}

			$export_type = $this->get_export_type( $customs_label_data, $label );

			$customs_data = array(
				'shippingConditions' => $label->get_duties(),
				'postalCharges'      => array(
					'value'    => $customs_label_data['additional_fee'],
					'currency' => $customs_label_data['currency'],
				),
				'exportDescription'  => wc_shiptastic_substring( $customs_label_data['export_reason_description'], 0, 80 ),
				'officeOfOrigin'     => $customs_label_data['place_of_commital'],
				'items'              => $customs_items,
				'exportType'         => strtoupper( $export_type ),
				/**
				 * Filter to allow adjusting the export invoice number.
				 *
				 * @param string $invoice_number The invoice number.
				 * @param Label\Label $label The label instance.
				 *
				 * @since 3.3.4
				 * @package Vendidero/Shiptastic/DHL
				 */
				'invoiceNo'          => wc_shiptastic_substring( apply_filters( 'woocommerce_shiptastic_dhl_label_api_export_invoice_number', $customs_label_data['invoice_number'], $label ), 0, 35 ),
			);

			if ( ! empty( $customs_label_data['export_reference_number'] ) ) {
				$customs_data['hasElectronicExportNotification'] = true;
				$customs_data['MRN']                             = wc_shiptastic_substring( preg_replace( '/[^A-Za-z0-9]/', '', $customs_label_data['export_reference_number'] ), 0, 18 );
			}

			$shipment_request['customs'] = apply_filters( 'woocommerce_shiptastic_dhl_label_rest_api_customs_data', $customs_data, $label );
		}

		$shipment_request = apply_filters( 'woocommerce_shiptastic_dhl_label_rest_api_create_label_request', $shipment_request, $label, $shipment, $this );
		$shipment_request = $this->walk_recursive_remove( $shipment_request );

		$request = array(
			'profile'   => $this->get_profile(),
			'shipments' => array(
				$shipment_request,
			),
		);

		$label_custom_format        = wc_stc_dhl_get_custom_label_format( $label );
		$label_custom_return_format = wc_stc_dhl_get_custom_label_format( $label, 'inlay_return' );

		$args = array(
			'combine'    => 'true',
			'mustEncode' => $label->codeable_address_only() ? 'true' : 'false',
		);

		if ( ! empty( $label_custom_format ) ) {
			$args['printFormat'] = $label_custom_format;
		}

		if ( ! empty( $label_custom_return_format ) ) {
			$args['retourePrintFormat'] = $label_custom_return_format;
		}

		$endpoint = add_query_arg( $args, 'orders' );
		$response = $this->post( $endpoint, $request );

		if ( $response->is_error() ) {
			throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), esc_html( $response->get_code() ) );
		} else {
			$body = $response->get_body();

			try {
				if ( isset( $body['items'] ) ) {
					$shipment_data = $body['items'][0];

					if ( ! isset( $shipment_data['shipmentNo'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						throw new \Exception( _x( 'There was an error generating the label. Please try again or consider switching to sandbox mode.', 'dhl', 'woocommerce-germanized' ) );
					}

					$label->set_number( $shipment_data['shipmentNo'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$label->save();

					$default_file = base64_decode( $shipment_data['label']['b64'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					if ( isset( $shipment_data['returnShipmentNo'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$return_label = $label->get_inlay_return_label();

						if ( ! $return_label ) {
							if ( $return_label = Factory::get_label( 0, $label->get_shipping_provider(), 'inlay_return' ) ) {
								$return_label->set_parent_id( $label->get_id() );
								$return_label->set_shipment_id( $label->get_shipment_id() );
								$return_label->set_shipping_provider( $label->get_shipping_provider() );

								if ( $shipment = $label->get_shipment() ) {
									$return_label->set_sender_address( $shipment->get_address() );
								}
							}
						}

						if ( $return_label ) {
							$return_label->set_number( $shipment_data['returnShipmentNo'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

							$splitter = new PDFSplitter( $default_file, true );
							$pdfs     = $splitter->split();

							if ( $pdfs && ! empty( $pdfs ) && count( $pdfs ) > 1 ) {
								$return_file = $pdfs[1];
							}

							if ( $return_file ) {
								$return_label->upload_label_file( $return_file );
							}

							$return_label->save();
						}
					}

					$default_path = $label->upload_label_file( $default_file, 'default' );
					$label->save();

					if ( isset( $shipment_data['customsDoc'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$customs_file = base64_decode( $shipment_data['customsDoc']['b64'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

						if ( $label->upload_label_file( $customs_file, 'export' ) ) {
							// Merge files
							$merger = new PDFMerger();
							$merger->add( $label->get_default_file() );
							$merger->add( $label->get_export_file() );

							$filename_label = $label->get_filename();
							$file           = $merger->output( $filename_label, 'S' );

							$label->upload_label_file( $file );
						}

						$label->save();
					} else {
						$label->set_path( $default_path );
					}
				}

				if ( isset( $shipment_data['validationMessages'] ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$result = new ShipmentError();

					foreach ( $shipment_data['validationMessages'] as $message ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$result->add_soft_error( 'label-soft-error', $message['validationMessage'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}

				if ( in_array( 'AdditionalInsurance', $label->get_services(), true ) && $label->get_insurance_amount() <= 500 ) {
					if ( ! is_a( $result, 'Vendidero\Shiptastic\ShipmentError' ) ) {
						$result = new ShipmentError();
					}

					$result->add_soft_error( 'label-soft-error', _x( 'You\'ve explicitly booked the additional insurance service resulting in additional fees although the value of goods does not exceed EUR 500. The label has been created anyway.', 'dhl', 'woocommerce-germanized' ) );
				}
			} catch ( \Exception $e ) {
				try {
					$this->delete_label( $label );
					$label->delete( true );
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}

				throw new \Exception( esc_html_x( 'Error while creating and uploading the label', 'dhl', 'woocommerce-germanized' ) );
			}
		}

		return $result;
	}

	/**
	 * @param Label\DHL $label
	 *
	 * @throws \Exception
	 */
	public function delete_label( $label ) {
		if ( ! empty( $label->get_number() ) ) {
			$endpoint = add_query_arg(
				array(
					'profile'  => $this->get_profile(),
					'shipment' => $label->get_number(),
				),
				'orders'
			);

			$response = $this->delete( $endpoint );

			if ( $response->is_error() ) {
				Package::log( 'Error while cancelling label: ' . wc_print_r( $response->get_error()->get_error_messages(), true ) );

				throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), absint( $response->get_code() ) );
			}

			return true;
		}

		return false;
	}

	protected function get_profile() {
		return 'STANDARD_GRUPPENPROFIL';
	}

	protected function walk_recursive_remove( array $the_array ) {
		foreach ( $the_array as $k => $v ) {
			if ( is_array( $v ) ) {
				$the_array[ $k ] = $this->walk_recursive_remove( $v );
			}

			if ( '' === $v ) {
				unset( $the_array[ $k ] );
			}
		}

		return $the_array;
	}

	protected function get_export_type( $customs_data, $label ) {
		$export_type = 'commercial_goods';

		if ( isset( $customs_data['export_reason'] ) && ! empty( $customs_data['export_reason'] ) ) {
			if ( 'gift' === $customs_data['export_reason'] ) {
				$export_type = 'PRESENT';
			} elseif ( 'sample' === $customs_data['export_reason'] ) {
				$export_type = 'COMMERCIAL_SAMPLE';
			} elseif ( 'repair' === $customs_data['export_reason'] ) {
				$export_type = 'RETURN_OF_GOODS';
			} elseif ( 'sale' === $customs_data['export_reason'] ) {
				$export_type = 'COMMERCIAL_GOODS';
			} else {
				$export_type = 'OTHER';
			}
		}

		/**
		 * Filter to allow adjusting the export type of a DHL label (for customs). Could be:
		 * <ul>
		 * <li>OTHER</li>
		 * <li>PRESENT</li>
		 * <li>COMMERCIAL_SAMPLE</li>
		 * <li>DOCUMENT</li>
		 * <li>RETURN_OF_GOODS</li>
		 * <li>COMMERCIAL_GOODS</li>
		 * </ul>
		 *
		 * @param string $export_type The export type.
		 * @param Label\Label  $label The label instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Shiptastic/DHL
		 */
		return apply_filters( 'woocommerce_shiptastic_dhl_label_api_export_type', strtoupper( $export_type ), $label );
	}

	public function test_connection() {
		$error    = new \WP_Error();
		$response = $this->post(
			'orders?validate=true',
			array(
				'profile'   => $this->get_profile(),
				'shipments' => array(
					array(
						'product'       => 'V01PAK',
						'billingNumber' => wc_stc_dhl_get_billing_number(
							'V01PAK',
							array(
								'api_type'   => 'dhl.com',
								'is_sandbox' => $this->is_sandbox(),
							)
						),
						'refNo'         => 'Order No. 1234',
						'shipDate'      => Package::get_date_de_timezone( 'Y-m-d' ),
						'shipper'       => array(
							'name1'         => 'Test',
							'addressStreet' => 'Sträßchensweg 10',
							'postalCode'    => '53113',
							'city'          => 'Bonn',
							'country'       => 'DEU',
						),
						'consignee'     => array(
							'name1'         => 'Test',
							'addressStreet' => 'Kurt-Schumacher-Str. 20',
							'postalCode'    => '53113',
							'city'          => 'Bonn',
							'country'       => 'DEU',
						),
						'details'       => array(
							'weight' => array(
								'uom'   => 'kg',
								'value' => 5,
							),
						),
					),
				),
			)
		);

		if ( $response->is_error() ) {
			if ( 401 === $response->get_code() ) {
				$error->add( 'unauthorized', _x( 'Your DHL API credentials seem to be invalid.', 'dhl', 'woocommerce-germanized' ) );
			} elseif ( 400 !== $response->get_code() ) {
				if ( $response->is_error() ) {
					$error = $response->get_error();
				} else {
					$error->add( 'dhl-api-error', _x( 'Unknown DHL API error.', 'dhl', 'woocommerce-germanized' ) );
				}
			}
		}

		return wc_stc_shipment_wp_error_has_errors( $error ) ? $error : true;
	}
}
