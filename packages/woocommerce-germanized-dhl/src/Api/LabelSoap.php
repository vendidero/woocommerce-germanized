<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\Label;
use Vendidero\Germanized\Shipments\PDFMerger;
use Vendidero\Germanized\Shipments\PDFSplitter;
use Vendidero\Germanized\DHL\SimpleLabel;
use Vendidero\Germanized\DHL\ReturnLabel;
use Vendidero\Germanized\DHL\ParcelLocator;

defined( 'ABSPATH' ) || exit;

class LabelSoap extends Soap {

    const DHL_MAX_ITEMS = '99';

    const DHL_RETURN_PRODUCT = '07';

    public function __construct( ) {
        try {
            parent::__construct( Package::get_gk_api_url() );
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    public function get_access_token() {
        return $this->get_auth_api()->get_access_token( Package::get_gk_api_user(), Package::get_gk_api_signature() );
    }

    public function test_connection() {

    }

	/**
	 * @param Label $label
	 *
	 * @return mixed
	 * @throws Exception
	 */
    public function get_label( &$label ) {
    	if ( empty( $label->get_number() ) ) {
    		return $this->create_label( $label );
	    } else {
			$soap_request = array(
				'Version'            => array(
					'majorRelease'   => '3',
					'minorRelease'   => '1'
				),
				'shipmentNumber'     => $label->get_number(),
				'labelResponseType'  => 'B64',
			);

		    try {
			    $soap_client = $this->get_access_token();
			    Package::log( '"getLabel" called with: ' . print_r( $soap_request, true ) );

			    $response_body = $soap_client->getLabel( $soap_request );
			    Package::log( 'Response: Successful' );

		    } catch ( Exception $e ) {
			    Package::log( 'Response Error: ' . $e->getMessage() );
			    throw $e;
		    }

		    // Label not found
		    if ( 2000 === $response_body->Status->statusCode ) {
		    	return $this->create_label( $label );
		    } else {
			    return $this->update_label( $label, $response_body->Status, $response_body );
		    }
	    }
    }

    /**
     * @param Label $label
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function create_label( &$label ) {
    	try {
	        $soap_request = $this->get_create_label_request( $label );
            $soap_client  = $this->get_access_token();
            Package::log( '"createShipmentOrder" called with: ' . print_r( $soap_request, true ) );

            $response_body = $soap_client->createShipmentOrder( $soap_request );
            Package::log( 'Response: Successful' );

        } catch ( Exception $e ) {
            Package::log( 'Response Error: ' . $e->getMessage() );

            switch( $e->getMessage() ) {
	            case "Unauthorized":
	            	throw new Exception( _x( 'Your DHL API credentials seem to be invalid. Please check your DHL settings.', 'dhl', 'woocommerce-germanized' ) );
                    break;
	            case "SOAP-ERROR: Encoding: object has no 'customsTariffNumber' property":
	            case "SOAP-ERROR: Encoding: object has no 'countryCodeOrigin' property":
		            throw new Exception( _x( 'Your products are missing data relevant for custom declarations. Please provide missing DHL fields (country of origin, HS code) in your product data > shipping tab.', 'dhl', 'woocommerce-germanized' ) );
		            break;
            }

            throw $e;
        }

	    if ( ! isset( $response_body->Status ) || ! isset( $response_body->CreationState ) ) {

		    if ( isset( $response_body->Status ) && ! empty( $response_body->Status->statusText ) ) {
			    throw new Exception( sprintf( _x( 'There was an error contacting the DHL API: %s.', 'dhl', 'woocommerce-germanized' ), $response_body->Status->statusText ) );
		    }

		    throw new Exception( _x( 'An error ocurred while contacting the DHL API. Please consider enabling the sandbox mode.', 'dhl', 'woocommerce-germanized' ) );
	    }

        return $this->update_label( $label, $response_body->Status, $response_body->CreationState );
    }

	/**
	 * @param SimpleLabel $label
	 * @param $status
	 * @param $response_body
	 *
	 * @return mixed
	 * @throws Exception
	 */
    protected function update_label( &$label, $status, $response_body ) {
	    if ( 0 !== $status->statusCode ) {
		    if ( isset( $response_body->LabelData->Status ) && isset( $response_body->LabelData->Status->statusMessage ) ) {
			    $messages = (array) $response_body->LabelData->Status->statusMessage;
			    $messages = implode( "\n", array_unique( $messages ) );

			    throw new Exception( $messages );
		    } else {
			    throw new Exception( _x( 'There was an error generating the label. Please try again or consider switching to sandbox mode.', 'dhl', 'woocommerce-germanized' ) );
		    }
	    } else {
		    // Give the server 1 second to create the PDF before downloading it
		    // sleep( 1 );

		    $return_label = false;

		    try {

			    if ( isset( $response_body->shipmentNumber ) ) {
				    $label->set_number( $response_body->shipmentNumber );
			    }

			    // Make sure the label does exist from this point on so that the parent id is available for returns.
			    $label->save();

			    // Create separate return label
			    if ( isset( $response_body->returnShipmentNumber ) ) {

			    	$return_label = $label->get_inlay_return_label();

			    	if ( ! $return_label ) {
						$return_label = wc_gzd_dhl_create_inlay_return_label( $label, array( 'created_via' => 'gkv' ) );
				    }

			    	if ( $return_label ) {
			    		$return_label->set_number( $response_body->returnShipmentNumber );
				    }
			    }

			    $default_file  = base64_decode( $response_body->LabelData->labelData );
			    $return_file   = false;

			    // Try to split the PDF to extract return label
			    if ( $return_label ) {

				    $splitter = $splitter = new PDFSplitter( $default_file, true );
				    $pdfs     = $splitter->split();

				    if ( $pdfs && ! empty( $pdfs ) && sizeof( $pdfs ) > 1 ) {
				    	$return_file  = $pdfs[1];
				    }

				    if ( $return_file ) {

					    if ( ! $filename_return_label = $return_label->get_filename() ) {
						    $filename_return_label = wc_gzd_dhl_generate_label_filename( $return_label, 'return-label' );
					    }

					    if ( $path = wc_gzd_dhl_upload_data( $filename_return_label, $return_file ) ) {
						    $return_label->set_default_path( $path );
						    $return_label->set_path( $path );
					    }
				    }

				    $return_label->save();
			    }

			    // Store the downloaded label as default file
			    if ( ! $filename_label = $label->get_default_filename() ) {
				    $filename_label = wc_gzd_dhl_generate_label_filename( $label, 'label-default' );
			    }

			    if ( $path = wc_gzd_dhl_upload_data( $filename_label, $default_file ) ) {
				    $label->set_default_path( $path );
			    }

			    // Merge export label into label path so that by default the shop owner downloads the merged file
			    if ( isset( $response_body->LabelData->exportLabelData ) ) {

			    	// Save export file
				    if ( ! $filename_export = $label->get_export_filename() ) {
					    $filename_export = wc_gzd_dhl_generate_label_filename( $label, 'label-export' );
				    }

				    if ( $path = wc_gzd_dhl_upload_data( $filename_export, base64_decode( $response_body->LabelData->exportLabelData ) ) ) {
					    $label->set_export_path( $path );
				    }

				    // Merge files
				    $merger = new PDFMerger();
				    $merger->add( $label->get_default_file() );
				    $merger->add( $label->get_export_file() );

				    if ( ! $filename_label = $label->get_filename() ) {
					    $filename_label = wc_gzd_dhl_generate_label_filename( $label );
				    }

				    $file = $merger->output( $filename_label, 'S' );

				    if ( $path = wc_gzd_dhl_upload_data( $filename_label, $file ) ) {
					    $label->set_path( $path );
				    }

			    } else {
					$label->set_path( $path );
			    }

		    } catch( Exception $e ) {
		    	// Delete the label dues to errors.
		    	$label->delete();

			    throw new Exception( _x( 'Error while creating and uploading the label', 'dhl', 'woocommerce-germanized' ) );
		    }

		    return $label;
	    }
    }

    /**
     * @param SimpleLabel $label
     *
     * @throws Exception
     */
    protected function delete_label_call( &$label ) {
        $soap_request =	array(
            'Version'          => array(
                'majorRelease' => '3',
                'minorRelease' => '1'
            ),
            'shipmentNumber'   => $label->get_number()
        );

        try {
            Package::log( '"deleteShipmentOrder" called with: ' . print_r( $soap_request, true ) );

            $soap_client   = $this->get_access_token();
            $response_body = $soap_client->deleteShipmentOrder( $soap_request );

            Package::log( 'Response Body: ' . print_r( $response_body, true ) );

        } catch ( Exception $e ) {
            throw $e;
        }

	    /**
	     * Action fires before deleting a DHL PDF label through an API call.
	     *
	     * @param Label $label The label object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/DHL
	     */
	    do_action( 'woocommerce_gzd_dhl_label_api_before_delete', $label );

	    if ( $return_label = $label->get_inlay_return_label() ) {

	    	$return_label->set_number( '' );

		    if ( $file = $return_label->get_file() ) {
			    wp_delete_file( $file );
		    }

		    $return_label->set_path( '' );
		    $return_label->set_default_path( '' );
	    }

	    $label->set_number( '' );

	    if ( $file = $label->get_file() ) {
		    wp_delete_file( $file );
	    }

	    $label->set_path( '' );

	    if ( $file = $label->get_default_file() ) {
		    wp_delete_file( $file );
	    }

	    $label->set_default_path( '' );

	    if ( $file = $label->get_export_file() ) {
		    wp_delete_file( $file );
	    }

	    $label->set_export_path( '' );

	    /**
	     * Action fires after deleting a DHL PDF label through an API call.
	     *
	     * @param Label $label The label object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/DHL
	     */
	    do_action( 'woocommerce_gzd_dhl_label_api_deleted', $label );

        if ( 0 !== $response_body->Status->statusCode ) {
            throw new Exception( sprintf( _x( 'Could not delete label - %s', 'dhl', 'woocommerce-germanized' ), $response_body->Status->statusMessage ) );
        }

        return $label;
    }

    /**
     * @param Label $label
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
        $product_number = preg_match('!\d+!', $dhl_product, $matches );

        if ( $product_number ) {
            $account_number = Package::get_setting( 'account_number' ) . $matches[0] . Package::get_participation_number( $dhl_product );

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
     * @param SimpleLabel $label
     * @return array
     *
     * @throws Exception
     */
    protected function get_create_label_request( $label ) {
        $shipment = $label->get_shipment();

        if ( ! $shipment ) {
            throw new Exception( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) );
        }

        $services  = array();
        $bank_data = array();

        foreach( $label->get_services() as $service ) {

            $services[ $service ] = array(
                'active' => 1
            );

            switch ( $service ) {
                case 'AdditionalInsurance':
                    $services[ $service ]['insuranceAmount'] = $shipment->get_total();
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
                        'bank_bic'    => 'bic'
                    );

	                $ref_replacements = wc_gzd_dhl_get_label_payment_ref_placeholder( $shipment );

                    foreach ( $bank_data_map as $key => $value ) {
                        if ( $setting_value = Package::get_setting( $key ) ) {
                            $bank_data[ $value ] = $setting_value;

                            if ( in_array( $key, array( 'bank_ref', 'bank_ref_2' ) ) ) {
	                            $bank_data[ $value ] = str_replace( array_keys( $ref_replacements ), array_values( $ref_replacements ), $bank_data[ $value ] );
                            }
                        }
                    }
                    break;
                case 'PreferredDay':
                    $services[ $service ]['details'] = $label->get_preferred_day() ? $label->get_preferred_day()->date( 'Y-m-d' ) : '';
                    break;
                case 'PreferredTime':
                    $services[ $service ]['type'] = wc_gzd_dhl_format_preferred_api_time( $label->get_preferred_time() );
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
		            $services[ $service ]['details'] = $shipment->get_email();
		            break;
            }
        }

        $dhl_label_body = array(
            'Version'            => array(
                'majorRelease'   => '3',
                'minorRelease'   => '1'
            ),
            'labelResponseType'  => 'B64',
            'ShipmentOrder'      => array (
                'sequenceNumber' => $label->get_shipment_id(),
                'Shipment'       => array(
                    'ShipmentDetails' => array(
                        'product'           => $label->get_dhl_product(),
                        'accountNumber'     => self::get_account_number( $label->get_dhl_product() ),
                        'customerReference' => wc_gzd_dhl_get_label_customer_reference( $label, $shipment ),
                        'shipmentDate'      => Package::get_date_de_timezone( 'Y-m-d' ),
                        'ShipmentItem'      => array(
                            'weightInKG' => $label->get_weight(),
	                        'lengthInCM' => $label->has_dimensions() ? $label->get_length() : '',
                            'widthInCM'  => $label->has_dimensions() ? $label->get_width() : '',
                            'heightInCM' => $label->has_dimensions() ? $label->get_height() : '',
                        ),
                        'Service'           => $services,
                        'Notification'      => apply_filters( 'woocommerce_gzd_dhl_label_api_enable_notification', $label->has_email_notification(), $label ) ? array( 'recipientEmailAddress' => $shipment->get_email() ) : array(),
                        'BankData'          => $bank_data,
                    ),
                    'Receiver'                => array(
                        'name1'               => $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name(),
                        'Address'             => array(
                            'name2'           => $shipment->get_company() ? $shipment->get_formatted_full_name() : '',
	                        /**
	                         * By default the name3 parameter is used to transmit the additional
	                         * address field to the DHL API. You may adjust the field value by using this filter.
	                         *
	                         * @param string $value The field value.
	                         * @param Label  $label The label instance.
	                         *
	                         * @since 3.0.3
	                         * @package Vendidero/Germanized/DHL
	                         */
                            'name3'           => apply_filters( 'woocommerce_gzd_dhl_label_api_receiver_name3', wc_gzd_dhl_get_label_shipment_address_addition( $shipment ), $label ),
                            'streetName'      => $shipment->get_address_street(),
                            'streetNumber'    => wc_gzd_dhl_get_label_shipment_street_number( $shipment ),
                            'zip'             => $shipment->get_postcode(),
                            'city'            => $shipment->get_city(),
                            'Origin'          => array(
                                'countryISOCode' => $shipment->get_country(),
                                'state'          => wc_gzd_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() )
                            )
                        ),
                        'Communication' => array(
	                        /**
	                         * Choose whether to transmit the full name of the shipment receiver as contactPerson
	                         * while creating a label.
	                         *
	                         * @param string $name The name of the shipmen receiver.
	                         * @param Label  $label The label instance.
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
	                         * @param Label  $label The label instance.
	                         *
	                         * @since 3.0.3
	                         * @package Vendidero/Germanized/DHL
	                         */
                            'phone' => apply_filters( 'woocommerce_gzd_dhl_label_api_communication_phone', '', $label ),
	                        /**
	                         * Choose whether to transfer the email to DHL on creating a label.
	                         * By default the email is only transmitted if the customer opted in.
	                         *
	                         * This email address is not used to notify the customer via DHL. It is only
	                         * meant for communicaton purposes.
	                         *
	                         * @param string $email The email.
	                         * @param Label  $label The label instance.
	                         *
	                         * @since 3.0.3
	                         * @package Vendidero/Germanized/DHL
	                         */
                            'email' => apply_filters( 'woocommerce_gzd_dhl_label_api_communication_email', $label->has_email_notification() ? $shipment->get_email() : '', $label ),
                        )
                    )
                )
            )
        );

	    /**
	     * This filter allows using a ShipperReference configured in the GKP instead of transmitting
	     * the shipper data from the DHL settings. Use this filter carefully and make sure that the
	     * reference exists.
	     *
	     * @param string $shipper_reference The shipper reference from the GKP.
	     * @param Label  $label The label instance.
	     *
	     * @since 3.0.5
	     * @package Vendidero/Germanized/DHL
	     */
	    $shipper_reference = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_reference', '', $label );

	    if ( ! empty( $shipper_reference ) ) {
		    $dhl_label_body['ShipmentOrder']['Shipment']['ShipperReference'] = $shipper_reference;
	    } else {
	    	$name1 = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_name1', Package::get_setting( 'shipper_company' ) ? Package::get_setting( 'shipper_company' ) : Package::get_setting( 'shipper_name' ), $label );
	    	$name2 = apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_name2', Package::get_setting( 'shipper_company' ) ? Package::get_setting( 'shipper_name' ) : '', $label );

		    $dhl_label_body['ShipmentOrder']['Shipment']['Shipper'] = array(
			    'Name'      => array(
				    'name1' => $name1,
				    'name2' => $name2,
			    ),
			    'Address'   => array(
				    'streetName'   => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_street_name', Package::get_setting( 'shipper_street' ), $label ),
				    'streetNumber' => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_street_number', Package::get_setting( 'shipper_street_no' ), $label ),
				    'zip'          => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_zip', Package::get_setting( 'shipper_postcode' ), $label ),
				    'city'         => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_city', Package::get_setting( 'shipper_city' ), $label ),
				    'Origin'       => array(
					    'countryISOCode' => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_country', Package::get_setting( 'shipper_country' ), $label ),
					    'state'          => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_state', wc_gzd_dhl_format_label_state( Package::get_setting( 'shipper_state' ), Package::get_setting( 'shipper_country' ) ), $label ),
				    )
			    ),
			    'Communication' => array(
				    'phone'         => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_phone', Package::get_setting( 'shipper_phone' ), $label ),
				    'email'         => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_email', Package::get_setting( 'shipper_email' ), $label ),
				    'contactPerson' => apply_filters( 'woocommerce_gzd_dhl_label_api_shipper_contact_person', Package::get_setting( 'shipper_name' ), $label ),
			    )
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

        if ( $shipment->send_to_external_pickup( array_keys( wc_gzd_dhl_get_pickup_types() ) ) ) {
            // Address is NOT needed if using a parcel shop
            unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Address'] );

            $parcel_shop = array(
                'zip'    => $shipment->get_postcode(),
                'city'   => $shipment->get_city(),
                'Origin' => array(
                    'countryISOCode' => $shipment->get_country(),
                    'state'          => wc_gzd_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() )
                )
            );

            $address_number = filter_var( $shipment->get_address_1(), FILTER_SANITIZE_NUMBER_INT );

            if ( $shipment->send_to_external_pickup( 'packstation' ) ) {
                $parcel_shop['postNumber']        = ParcelLocator::get_postnumber_by_shipment( $shipment );
                $parcel_shop['packstationNumber'] = $address_number;

                $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Packstation'] = $parcel_shop;
            }

            if ( $shipment->send_to_external_pickup( 'postoffice' ) || $shipment->send_to_external_pickup( 'parcelshop' ) ) {
                if ( $post_number = ParcelLocator::get_postnumber_by_shipment( $shipment ) ) {
                    $parcel_shop['postNumber'] = $post_number;
                    unset( $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Communication']['email'] );
                }

                $parcel_shop['postfilialNumber'] = $address_number;
                $dhl_label_body['ShipmentOrder']['Shipment']['Receiver']['Postfiliale'] = $parcel_shop;
            }
        }

        if ( $label->has_inlay_return() ) {
            $dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentAccountNumber'] = self::get_return_account_number();
            $dhl_label_body['ShipmentOrder']['Shipment']['ShipmentDetails']['returnShipmentReference']     = wc_gzd_dhl_get_inlay_return_label_reference( $label, $shipment );

            $dhl_label_body['ShipmentOrder']['Shipment']['ReturnReceiver'] = array(
                'Name' => array(
                    'name1' => $label->get_return_company() ? $label->get_return_company() : $label->get_return_formatted_full_name(),
                    'name2' => $label->get_return_company() ? $label->get_return_formatted_full_name() : ''
                ),
                'Address' => array(
                    'streetName'   => $label->get_return_street(),
                    'streetNumber' => $label->get_return_street_number(),
                    'zip'          => $label->get_return_postcode(),
                    'city'         => $label->get_return_city(),
                    'Origin'       => array(
                        'countryISOCode' => $label->get_return_country(),
                        'state'          => wc_gzd_dhl_format_label_state( $label->get_return_state(), $label->get_return_country() ),
                    )
                ),
                'Communication' => array(
                	'contactPerson' => $label->get_return_formatted_full_name(),
                    'phone'         => $label->get_return_phone(),
                    'email'         => $label->get_return_email()
                )
            );
        }

        if ( $label->codeable_address_only() ) {
            $dhl_label_body['ShipmentOrder']['PrintOnlyIfCodeable'] = array( 'active' => 1 );
        }

        if ( Package::is_crossborder_shipment( $shipment->get_country() ) ) {

            if ( sizeof( $shipment->get_items() ) > self::DHL_MAX_ITEMS ) {
                throw new Exception( sprintf( _x( 'Only %s shipment items can be processed, your shipment has %s items.', 'dhl', 'woocommerce-germanized' ), self::DHL_MAX_ITEMS, sizeof( $shipment->get_items() ) ) );
            }

            $customs_data = wc_gzd_dhl_get_shipment_customs_data( $label );
            $customs_data['termsOfTrade'] = $label->get_duties();

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
	         * @param Label  $label The label instance.
	         *
	         * @since 3.3.0
	         * @package Vendidero/Germanized/DHL
	         */
	        $customs_data['exportType'] = strtoupper( apply_filters( 'woocommerce_gzd_dhl_label_api_export_type', 'COMMERCIAL_GOODS', $label ) );

	        /**
	         * Filter to allow adjusting the export invoice number.
	         *
	         * @param string $invoice_number The invoice number.
	         * @param Label  $label The label instance.
	         *
	         * @since 3.3.4
	         * @package Vendidero/Germanized/DHL
	         */
	        $customs_data['invoiceNumber'] = apply_filters( 'woocommerce_gzd_dhl_label_api_export_invoice_number', $customs_data['invoiceNumber'], $label );

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

        return $this->body_request;
    }
}
