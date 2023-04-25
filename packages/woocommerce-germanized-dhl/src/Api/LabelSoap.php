<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\Label;
use Vendidero\Germanized\Shipments\Labels\Factory;
use Vendidero\Germanized\Shipments\PDFMerger;
use Vendidero\Germanized\Shipments\PDFSplitter;
use Vendidero\Germanized\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

class LabelSoap extends Soap {

	const DHL_MAX_ITEMS = '99';

	const DHL_RETURN_PRODUCT = '07';

	public function __construct() {
		try {
			parent::__construct( Package::get_gk_api_url() );
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Use the current local WSDL file instead as DHL
	 * does not seem to server minor version WSDL files via URL.
	 *
	 * @param $wsdl_link
	 *
	 * @return string
	 */
	protected function get_wsdl_file( $wsdl_link ) {
		$core_file = Package::get_core_wsdl_file( 'geschaeftskundenversand-api-3.4.0.wsdl' );

		if ( $core_file ) {
			return $core_file;
		} else {
			return $wsdl_link;
		}
	}

	public function get_access_token() {
		return $this->get_auth_api()->get_access_token( Package::get_gk_api_user(), Package::get_gk_api_signature() );
	}

	public function test_connection() {
		$error = new \WP_Error();

		try {
			$soap_client = $this->get_access_token();
			$response    = $soap_client->validateShipment(
				array(
					'Version'           => array(
						'majorRelease' => '3',
						'minorRelease' => '4',
					),
					'labelResponseType' => 'URL',
					'labelFormat'       => '',
					'ShipmentOrder'     => array(
						'sequenceNumber' => '',
						'Shipment'       => array(
							'ShipmentDetails' => array(
								'product'       => 'V01PAK',
								'accountNumber' => '12345678901234',
								'shipmentDate'  => '2020-12-29',
								'ShipmentItem'  => array(
									'weightInKG' => 5,
								),
							),
							'Shipper'         => array(
								'Name'    => 'Test',
								'Address' => array(
									'streetName' => 'Street 1',
									'zip'        => '12345',
									'city'       => 'Berlin',
									'Origin'     => array(
										'countryISOCode' => 'DE',
									),
								),
							),
							'Receiver'        => array(
								'name1'   => 'test1',
								'Address' => array(
									'streetName' => 'Street 2',
									'zip'        => '12345',
									'city'       => 'Berlin',
									'Origin'     => array(
										'countryISOCode' => 'DE',
									),
								),
							),
						),
					),
				)
			);

			if ( isset( $response->Status, $response->Status->statusCode ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( 1001 === (int) $response->Status->statusCode ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					throw new \Exception( 'Unauthorized' );
				}
			}
		} catch ( Exception $e ) {
			switch ( $e->getMessage() ) {
				case 'Unauthorized':
					$error->add( 'unauthorized', _x( 'Your DHL API credentials seem to be invalid.', 'dhl', 'woocommerce-germanized' ) );
					break;
				default:
					$error->add( $e->getCode(), $e->getMessage() );
					break;
			}
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}

	/**
	 * @param Label\DHL $label
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_label( &$label ) {
		if ( empty( $label->get_number() ) ) {
			return $this->create_label( $label );
		} else {
			$soap_request = array(
				'Version'           => array(
					'majorRelease' => '3',
					'minorRelease' => '4',
				),
				'shipmentNumber'    => $label->get_number(),
				'labelResponseType' => 'B64',
			);

			try {
				$soap_client = $this->get_access_token();
				Package::log( '"getLabel" called with: ' . print_r( $soap_request, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

				$response_body = $soap_client->getLabel( $soap_request );
				Package::log( 'Response: Successful' );

			} catch ( Exception $e ) {
				Package::log( 'Response Error: ' . $e->getMessage() );
				throw $e;
			}

			// Label not found
			if ( 2000 === $response_body->Status->statusCode ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return $this->create_label( $label );
			} else {
				return $this->update_label( $label, $response_body->Status, $response_body ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}
	}

	/**
	 * @param Label\DHL $label
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function create_label( &$label ) {
		try {
			$soap_request = $this->get_create_label_request( $label );
			$soap_client  = $this->get_access_token();
			Package::log( '"createShipmentOrder" called with: ' . print_r( $soap_request, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			$response_body = $soap_client->createShipmentOrder( $soap_request );

			Package::log( 'Response: Successful' );

		} catch ( Exception $e ) {
			Package::log( 'Response Error: ' . $e->getMessage() );

			switch ( $e->getMessage() ) {
				case 'Unauthorized':
					throw new Exception( _x( 'Your DHL API credentials seem to be invalid. Please check your DHL settings.', 'dhl', 'woocommerce-germanized' ) );
				case "SOAP-ERROR: Encoding: object has no 'customsTariffNumber' property":
				case "SOAP-ERROR: Encoding: object has no 'countryCodeOrigin' property":
					throw new Exception( _x( 'Your products are missing data relevant for custom declarations. Please provide missing DHL fields (country of origin, HS code) in your product data > shipping tab.', 'dhl', 'woocommerce-germanized' ) );
			}

			throw $e;
		}

		if ( ! isset( $response_body->Status ) || ! isset( $response_body->CreationState ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( isset( $response_body->Status ) && ! empty( $response_body->Status->statusText ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				throw new Exception( sprintf( _x( 'There was an error contacting the DHL API: %s.', 'dhl', 'woocommerce-germanized' ), $response_body->Status->statusText ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			throw new Exception( _x( 'An error ocurred while contacting the DHL API. Please consider enabling the sandbox mode.', 'dhl', 'woocommerce-germanized' ) );
		}

		return $this->update_label( $label, $response_body->Status, $response_body->CreationState ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @param Label\DHL $label
	 * @param $status
	 * @param $response_body
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function update_label( &$label, $status, $response_body ) {
		if ( 0 !== $status->statusCode && 'ok' !== $status->statusText ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( isset( $response_body->LabelData->Status ) && isset( $response_body->LabelData->Status->statusMessage ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$messages = (array) $response_body->LabelData->Status->statusMessage; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$messages = implode( "\n", array_unique( $messages ) );

				throw new Exception( $messages );
			} else {
				throw new Exception( _x( 'There was an error generating the label. Please try again or consider switching to sandbox mode.', 'dhl', 'woocommerce-germanized' ) );
			}
		} else {
			$return_label = false;

			try {

				if ( isset( $response_body->shipmentNumber ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$label->set_number( $response_body->shipmentNumber ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}

				// Make sure the label does exist from this point on so that the parent id is available for returns.
				$label->save();

				// Create separate return label
				if ( isset( $response_body->returnShipmentNumber ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
						$return_label->set_number( $response_body->returnShipmentNumber ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}

				$default_file = base64_decode( $response_body->LabelData->labelData ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$return_file  = false;

				// Try to split the PDF to extract return label
				if ( $return_label ) {
					$splitter = $splitter = new PDFSplitter( $default_file, true );
					$pdfs     = $splitter->split();

					if ( $pdfs && ! empty( $pdfs ) && count( $pdfs ) > 1 ) {
						$return_file = $pdfs[1];
					}

					if ( $return_file ) {
						$return_label->upload_label_file( $return_file );
					}

					$return_label->save();
				}

				// Store the downloaded label as default file
				$path = $label->upload_label_file( $default_file, 'default' );

				// Merge export label into label path so that by default the shop owner downloads the merged file
				if ( isset( $response_body->LabelData->exportLabelData ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					// Save export file
					$label->upload_label_file( base64_decode( $response_body->LabelData->exportLabelData ), 'export' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					// Merge files
					$merger = new PDFMerger();
					$merger->add( $label->get_default_file() );
					$merger->add( $label->get_export_file() );

					$filename_label = $label->get_filename();
					$file           = $merger->output( $filename_label, 'S' );

					$label->upload_label_file( $file );
				} else {
					$label->set_path( $path );
				}
			} catch ( Exception $e ) {
				// Delete the label dues to errors.
				$label->delete();

				throw new Exception( _x( 'Error while creating and uploading the label', 'dhl', 'woocommerce-germanized' ) );
			}

			return $label;
		}
	}

	/**
	 * @param Label\DHL $label
	 *
	 * @throws Exception
	 */
	protected function delete_label_call( &$label ) {
		$soap_request = array(
			'Version'        => array(
				'majorRelease' => '3',
				'minorRelease' => '4',
			),
			'shipmentNumber' => $label->get_number(),
		);

		try {
			Package::log( '"deleteShipmentOrder" called with: ' . print_r( $soap_request, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			$soap_client   = $this->get_access_token();
			$response_body = $soap_client->deleteShipmentOrder( $soap_request );

			Package::log( 'Response Body: ' . print_r( $response_body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		} catch ( Exception $e ) {
			throw $e;
		}

		/**
		 * Action fires after deleting a DHL PDF label through an API call.
		 *
		 * @param Label\DHL $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( 'woocommerce_gzd_dhl_label_api_deleted', $label );

		if ( 0 !== $response_body->Status->statusCode ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			throw new Exception( sprintf( _x( 'Could not delete label - %s', 'dhl', 'woocommerce-germanized' ), $response_body->Status->statusMessage ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return $label;
	}

	/**
	 * @param Label\DHL $label
	 *
	 * @throws Exception
	 */
	public function delete_label( &$label ) {
		try {
			if ( ! empty( $label->get_number() ) ) {
				return $this->delete_label_call( $label );
			}
		} catch ( Exception $e ) {
			throw $e;
		}

		return false;
	}

	protected function get_account_number( $dhl_product ) {
		$product_number = preg_match( '!\d+!', $dhl_product, $matches );

		if ( $product_number ) {
			$participation_number = Package::get_participation_number( $dhl_product );
			$account_base         = Package::get_setting( 'account_number' );

			// Participation number contains account number too
			if ( strlen( $participation_number ) >= 12 ) {
				$account_base         = substr( $participation_number, 0, 10 ); // First 10 chars
				$participation_number = substr( $participation_number, -2 ); // Last 2 chars
			}

			$account_number = $account_base . $matches[0] . $participation_number;

			if ( strlen( $account_number ) !== 14 ) {
				throw new Exception( sprintf( _x( 'Either your customer number or the participation number for <strong>%1$s</strong> is missing. Please validate your <a href="%2$s">settings</a> and try again.', 'dhl', 'woocommerce-germanized' ), esc_html( wc_gzd_dhl_get_product_title( $dhl_product ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&provider=dhl' ) ) ) );
			}

			return $account_number;
		} else {
			throw new Exception( _x( 'Could not create account number - no product number.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	protected function get_return_account_number() {
		$product_number = self::DHL_RETURN_PRODUCT;
		$account_number = Package::get_setting( 'account_number' ) . $product_number . Package::get_participation_number( 'return' );

		return $account_number;
	}

	/**
	 * @param Label\DHL $label
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function get_create_label_request( $label ) {
		$shipment     = $label->get_shipment();
		$dhl_provider = Package::get_dhl_shipping_provider();

		if ( ! $shipment ) {
			throw new Exception( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) );
		}

		$services           = array();
		$bank_data          = array();
		$available_services = wc_gzd_dhl_get_product_services( $label->get_product_id(), $shipment );

		foreach ( $label->get_services() as $service ) {
			if ( ! in_array( $service, $available_services, true ) ) {
				continue;
			}

			$services[ $service ] = array(
				'active' => 1,
			);

			switch ( $service ) {
				case 'AdditionalInsurance':
					$services[ $service ]['insuranceAmount'] = apply_filters( 'woocommerce_gzd_dhl_label_api_insurance_amount', $shipment->get_total(), $shipment, $label );
					break;
				case 'IdentCheck':
					$services[ $service ]['Ident']['surname']     = $shipment->get_last_name();
					$services[ $service ]['Ident']['givenName']   = $shipment->get_first_name();
					$services[ $service ]['Ident']['dateOfBirth'] = $label->get_ident_date_of_birth() ? $label->get_ident_date_of_birth()->date( 'Y-m-d' ) : '';
					$services[ $service ]['Ident']['minimumAge']  = $label->get_ident_min_age();
					break;
				case 'CashOnDelivery':
					$services[ $service ]['codAmount'] = $label->get_cod_total();

					$bank_data_map = array(
						'bank_holder' => 'accountOwner',
						'bank_name'   => 'bankName',
						'bank_iban'   => 'iban',
						'bank_ref'    => 'note1',
						'bank_ref_2'  => 'note2',
						'bank_bic'    => 'bic',
					);

					$ref_replacements = wc_gzd_dhl_get_label_payment_ref_placeholder( $shipment );

					foreach ( $bank_data_map as $key => $value ) {
						if ( $setting_value = Package::get_setting( $key ) ) {
							$bank_data[ $value ] = $setting_value;

							if ( in_array( $key, array( 'bank_ref', 'bank_ref_2' ), true ) ) {
								$bank_data[ $value ] = str_replace( array_keys( $ref_replacements ), array_values( $ref_replacements ), $bank_data[ $value ] );
							}
						}
					}
					break;
				case 'PreferredDay':
					$services[ $service ]['details'] = $label->get_preferred_day() ? $label->get_preferred_day()->date( 'Y-m-d' ) : '';
					break;
				case 'VisualCheckOfAge':
					$services[ $service ]['type'] = $label->get_visual_min_age();
					break;
				case 'PreferredLocation':
					$services[ $service ]['details'] = $label->get_preferred_location();
					break;
				case 'PreferredNeighbour':
					$services[ $service ]['details'] = $label->get_preferred_neighbor();
					break;
				case 'ParcelOutletRouting':
					if ( ! empty( $shipment->get_email() ) ) {
						$services[ $service ]['details'] = $shipment->get_email();
					}
					break;
			}
		}

		/**
		 * Endorsement option (Vorausverfügung)
		 */
		if ( 'V53WPAK' === $label->get_product_id() ) {
			$services['Endorsement'] = array(
				'active' => 1,
				'type'   => wc_gzd_dhl_get_label_endorsement_type( $label, $shipment ),
			);
		}

		$account_number            = self::get_account_number( $label->get_product_id() );
		$formatted_recipient_state = wc_gzd_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() );

		$dhl_label_body = array(
			'Version'           => array(
				'majorRelease' => '3',
				'minorRelease' => '4',
			),
			'labelResponseType' => 'B64',
			'ShipmentOrder'     => array(
				'sequenceNumber' => $label->get_shipment_id(),
				'Shipment'       => array(
					'ShipmentDetails' => array(
						'product'           => $label->get_product_id(),
						'accountNumber'     => $account_number,
						'customerReference' => wc_gzd_dhl_get_label_customer_reference( $label, $shipment ),
						'shipmentDate'      => Package::get_date_de_timezone( 'Y-m-d' ),
						'ShipmentItem'      => array(
							'weightInKG' => $label->get_weight(),
							'lengthInCM' => $label->has_dimensions() ? $label->get_length() : '',
							'widthInCM'  => $label->has_dimensions() ? $label->get_width() : '',
							'heightInCM' => $label->has_dimensions() ? $label->get_height() : '',
						),
						'Service'           => $services,
						'Notification'      => ( apply_filters( 'woocommerce_gzd_dhl_label_api_enable_notification', $label->has_email_notification(), $label ) && ! empty( $shipment->get_email() ) ) ? array( 'recipientEmailAddress' => $shipment->get_email() ) : array(),
						'BankData'          => $bank_data,
					),
					'Receiver'        => array(
						'name1'         => $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name(),
						'Address'       => array(
							'name2'        => $shipment->get_company() ? $shipment->get_formatted_full_name() : '',
							/**
							 * By default the name3 parameter is used to transmit the additional
							 * address field to the DHL API. You may adjust the field value by using this filter.
							 *
							 * @param string $value The field value.
							 * @param Label\DHL  $label The label instance.
							 *
							 * @since 3.0.3
							 * @package Vendidero/Germanized/DHL
							 */
							'name3'        => apply_filters( 'woocommerce_gzd_dhl_label_api_receiver_name3', wc_gzd_dhl_get_label_shipment_address_addition( $shipment ), $label ),
							'streetName'   => $shipment->get_address_street(),
							'streetNumber' => wc_gzd_dhl_get_label_shipment_street_number( $shipment ),
							'zip'          => $shipment->get_postcode(),
							'city'         => $shipment->get_city(),
							/**
							 * The province field actually prints the state on the DHL label.
							 *
							 * @param string $value The field value.
							 * @param Label\DHL  $label The label instance.
							 *
							 * @since 3.0.3
							 * @package Vendidero/Germanized/DHL
							 */
							'province'     => apply_filters( 'woocommerce_gzd_dhl_label_api_province', ( ! $shipment->is_shipping_inner_eu() && $shipment->get_city() !== $formatted_recipient_state ? $formatted_recipient_state : '' ), $label ),
							'Origin'       => array(
								'countryISOCode' => $shipment->get_country(),
								'state'          => $formatted_recipient_state,
							),
						),
						'Communication' => array(
							/**
							 * Choose whether to transmit the full name of the shipment receiver as contactPerson
							 * while creating a label.
							 *
							 * @param string $name The name of the shipmen receiver.
							 * @param Label\DHL  $label The label instance.
							 *
							 * @since 3.0.5
							 * @package Vendidero/Germanized/DHL
							 */
							'contactPerson' => apply_filters( 'woocommerce_gzd_dhl_label_api_communication_contact_person', $shipment->get_formatted_full_name(), $label ),
							/**
							 * Choose whether to transfer the phone number to DHL on creating a label.
							 * By default the phone number is not transmitted.
							 *
							 * @param string $phone The phone number.
							 * @param Label\DHL  $label The label instance.
							 *
							 * @since 3.0.3
							 * @package Vendidero/Germanized/DHL
							 */
							'phone'         => apply_filters( 'woocommerce_gzd_dhl_label_api_communication_phone', '', $label ),
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
							 * @package Vendidero/Germanized/DHL
							 */
							'email'         => apply_filters( 'woocommerce_gzd_dhl_label_api_communication_email', $label->has_email_notification() || isset( $services['CDP'] ) ? $shipment->get_email() : '', $label ),
						),
					),
				),
			),
		);

		/**
		 * This filter allows using a ShipperReference configured in the GKP instead of transmitting
		 * the shipper data from the DHL settings. Use this filter carefully and make sure that the
		 * reference exists.
		 *
		 * @param string $shipper_reference The shipper reference from the GKP.
		 * @param Label\DHL  $label The label instance.
		 *
		 * @since 3.0.5
		 * @package Vendidero/Germanized/DHL
		 */
		$shipper_reference = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_reference', $dhl_provider->has_custom_shipper_reference() ? $dhl_provider->get_label_custom_shipper_reference() : '', $label );

		if ( ! empty( $shipper_reference ) ) {
			$dhl_label_body['ShipmentOrder']['Shipment']['ShipperReference'] = $shipper_reference;
		} else {
			$name1         = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_name1', $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name(), $label );
			$name2         = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_name2', $shipment->get_sender_company() ? $shipment->get_formatted_sender_full_name() : '', $label );
			$street_number = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_street_number', $shipment->get_sender_address_street_number(), $label );
			$street        = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_street_name', $shipment->get_sender_address_street(), $label );
			$zip           = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_zip', $shipment->get_sender_postcode(), $label );
			$city          = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_city', $shipment->get_sender_city(), $label );
			$phone         = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_phone', $shipment->get_sender_phone(), $label );
			$email         = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_email', $shipment->get_sender_email(), $label );
			$country       = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_country', $shipment->get_sender_country(), $label );
			$state         = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_state', $shipment->get_sender_state(), $label );

			$fields_necessary = array(
				'street'        => $street,
				'street_number' => $street_number,
				'full_name'     => $name1,
				'postcode'      => $zip,
				'city'          => $city,
			);

			$address_fields         = wc_gzd_get_shipment_setting_default_address_fields();
			$missing_address_fields = array();

			foreach ( $fields_necessary as $field => $value ) {
				if ( empty( $value ) && array_key_exists( $field, $address_fields ) ) {
					$missing_address_fields[] = $address_fields[ $field ];
				}
			}

			if ( ! empty( $missing_address_fields ) ) {
				throw new Exception( sprintf( _x( 'Your shipper address is incomplete (%1$s). Please validate your <a href="%2$s">settings</a> and try again.', 'dhl', 'woocommerce-germanized' ), implode( ', ', $missing_address_fields ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=address' ) ) ) );
			}

			$dhl_label_body['ShipmentOrder']['Shipment']['Shipper'] = array(
				'Name'          => array(
					'name1' => $name1,
					'name2' => $name2,
				),
				'Address'       => array(
					'streetName'   => $street,
					'streetNumber' => $street_number,
					'zip'          => $zip,
					'city'         => $city,
					'Origin'       => array(
						'countryISOCode' => $country,
						'state'          => wc_gzd_dhl_format_label_state( $state, $country ),
					),
				),
				'Communication' => array(
					'phone'         => $phone,
					'email'         => $email,
					'contactPerson' => $shipment->get_formatted_sender_full_name(),
				),
			);
		}

		$label_custom_format        = wc_gzd_dhl_get_custom_label_format( $label );
		$label_custom_return_format = wc_gzd_dhl_get_custom_label_format( $label, 'inlay_return' );

		if ( ! empty( $label_custom_format ) ) {
			$dhl_label_body['labelFormat'] = $label_custom_format;
		}

		if ( ! empty( $label_custom_return_format ) ) {
			$dhl_label_body['labelFormatRetoure'] = $label_custom_return_format;
		}

		if ( $shipment->send_to_external_pickup() ) {
			if ( 'DE' === $shipment->get_country() ) {
				// Address is NOT needed if using a parcel shop
				unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Address'] );

				$parcel_shop = array(
					'zip'    => $shipment->get_postcode(),
					'city'   => $shipment->get_city(),
					'Origin' => array(
						'countryISOCode' => $shipment->get_country(),
						'state'          => wc_gzd_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() ),
					),
				);

				$address_number = filter_var( $shipment->get_address_1(), FILTER_SANITIZE_NUMBER_INT );

				if ( $shipment->send_to_external_pickup( 'packstation' ) ) {
					$parcel_shop['postNumber']        = ParcelLocator::get_postnumber_by_shipment( $shipment );
					$parcel_shop['packstationNumber'] = $address_number;

					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Packstation'] = $parcel_shop;
				} elseif ( $shipment->send_to_external_pickup( 'postoffice' ) || $shipment->send_to_external_pickup( 'parcelshop' ) ) {
					if ( $post_number = ParcelLocator::get_postnumber_by_shipment( $shipment ) ) {
						$parcel_shop['postNumber'] = $post_number;
						unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] );
					} else {
						$parcel_shop['postNumber'] = '';
						$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] = $shipment->get_email();
					}

					$parcel_shop['postfilialNumber']                                        = $address_number;
					$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Postfiliale'] = $parcel_shop;
				} else {
					throw new Exception( _x( 'Please make sure that the Packstation (or postoffice, parcelshop) exists and is indicated correctly.', 'dhl', 'woocommerce-germanized' ) );
				}
			} else {
				$dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] = $shipment->get_email();
			}
		}

		if ( $label->has_inlay_return() ) {
			$dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentAccountNumber'] = self::get_return_account_number();
			$dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentReference']     = wc_gzd_dhl_get_inlay_return_label_reference( $label, $shipment );

			$dhl_label_body['ShipmentOrder']['Shipment']['ReturnReceiver'] = array(
				'Name'          => array(
					'name1' => $label->get_return_company() ? $label->get_return_company() : $label->get_return_formatted_full_name(),
					'name2' => $label->get_return_company() ? $label->get_return_formatted_full_name() : '',
				),
				'Address'       => array(
					'streetName'   => $label->get_return_street(),
					'streetNumber' => $label->get_return_street_number(),
					'zip'          => $label->get_return_postcode(),
					'city'         => $label->get_return_city(),
					'Origin'       => array(
						'countryISOCode' => $label->get_return_country(),
						'state'          => wc_gzd_dhl_format_label_state( $label->get_return_state(), $label->get_return_country() ),
					),
				),
				'Communication' => array(
					'contactPerson' => $label->get_return_formatted_full_name(),
					'phone'         => $label->get_return_phone(),
					'email'         => $label->get_return_email(),
				),
			);
		}

		if ( $label->codeable_address_only() ) {
			$dhl_label_body['ShipmentOrder']['PrintOnlyIfCodeable'] = array( 'active' => 1 );
		}

		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {

			if ( count( $shipment->get_items() ) > self::DHL_MAX_ITEMS ) {
				throw new Exception( sprintf( _x( 'Only %1$s shipment items can be processed, your shipment has %2$s items.', 'dhl', 'woocommerce-germanized' ), self::DHL_MAX_ITEMS, count( $shipment->get_items() ) ) );
			}

			$customs_label_data = wc_gzd_dhl_get_shipment_customs_data( $label );
			$customs_items      = array();

			foreach ( $customs_label_data['items'] as $item_id => $item_data ) {
				$customs_items[] = array(
					'description'         => $item_data['description'],
					'countryCodeOrigin'   => $item_data['origin_code'],
					'customsTariffNumber' => $item_data['tariff_number'],
					'amount'              => $item_data['quantity'],
					/**
					 * netWeightInKG is defined as the weight per item (e.g. 2 items in case the quantity equals 2).
					 */
					'netWeightInKG'       => $item_data['single_weight_in_kg'],
					/**
					 * Single product value per item
					 */
					'customsValue'        => $item_data['single_value'],
				);
			}

			/**
			 * In case the customs item total weight is greater than label weight (e.g. due to rounding issues) replace it
			 */
			if ( $customs_label_data['item_total_weight_in_kg'] > $label->get_weight() ) {
				$dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['ShipmentItem']['weightInKG'] = wc_format_decimal( $customs_label_data['item_total_weight_in_kg'] ) + $shipment->get_packaging_weight();
			}

			$customs_data = array(
				'termsOfTrade'               => $label->get_duties(),
				'additionalFee'              => $customs_label_data['additional_fee'],
				'exportTypeDescription'      => $customs_label_data['export_type_description'],
				'placeOfCommital'            => $customs_label_data['place_of_commital'],
				'addresseesCustomsReference' => $customs_label_data['receiver_customs_ref_number'],
				'sendersCustomsReference'    => $customs_label_data['sender_customs_ref_number'],
				'customsCurrency'            => strtoupper( $customs_label_data['currency'] ),
				'ExportDocPosition'          => $customs_items,
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
				 * @package Vendidero/Germanized/DHL
				 */
				'exportType'                 => strtoupper( apply_filters( 'woocommerce_gzd_dhl_label_api_export_type', 'COMMERCIAL_GOODS', $label ) ),
				/**
				 * Filter to allow adjusting the export invoice number.
				 *
				 * @param string $invoice_number The invoice number.
				 * @param Label\Label $label The label instance.
				 *
				 * @since 3.3.4
				 * @package Vendidero/Germanized/DHL
				 */
				'invoiceNumber'              => apply_filters( 'woocommerce_gzd_dhl_label_api_export_invoice_number', $customs_label_data['invoice_number'], $label ),
			);

			$dhl_label_body['ShipmentOrder']['Shipment']['ExportDocument'] = $customs_data;
		}

		// Unset/remove any items that are empty strings or 0, even if required!
		$this->body_request = $this->walk_recursive_remove( $dhl_label_body );

		// Ensure Export Document is set before adding additional fee
		if ( isset( $this->body_request['ShipmentOrder']['Shipment']['ExportDocument'] ) && ! isset( $this->body_request['ShipmentOrder']['Shipment']['ExportDocument']['additionalFee'] ) ) {
			// Additional fees, required and 0 so place after check
			$this->body_request['ShipmentOrder']['Shipment']['ExportDocument']['additionalFee'] = 0;
		}

		// If "Ident-Check" enabled, then ensure both fields are passed even if empty
		if ( $label->has_service( 'IdentCheck' ) ) {
			if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] ) ) {
				$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['minimumAge'] = '';
			}
			if ( ! isset( $this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] ) ) {
				$this->body_request['ShipmentOrder']['Shipment']['ShipmentDetails']['Service']['IdentCheck']['Ident']['dateOfBirth'] = '';
			}
		}

		return apply_filters( 'woocommerce_gzd_dhl_label_api_create_label_request', $this->body_request, $label, $shipment, $this );
	}
}
