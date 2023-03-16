<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Label\DeutschePost;
use Vendidero\Germanized\DHL\Label\Label;
use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ImWarenpostIntRest extends Rest {

	public function get_base_url() {
		return self::is_sandbox() ? 'https://api-qa.deutschepost.com' : Package::get_warenpost_international_rest_url();
	}

	public function get_pdf( $awb ) {
		$pdf = $this->get_request( '/dpi/shipping/v1/shipments/' . $awb . '/itemlabels', array(), 'pdf' );

		return $pdf;
	}

	/**
	 * Updates the label
	 *
	 * @param DeutschePost $label
	 * @param \stdClass $result
	 *
	 * @throws Exception
	 */
	public function update_label( &$label, $result ) {
		$order_id = wc_clean( $result->orderId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$awb      = wc_clean( $result->shipments[0]->awb );
		$barcode  = wc_clean( $result->shipments[0]->items[0]->barcode );
		$pdf      = $this->get_pdf( $awb );

		if ( ! $pdf ) {
			throw new Exception( _x( 'Error while fetching label PDF', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( $path = $label->upload_label_file( $pdf ) ) {
			$label->set_path( $path );
		} else {
			throw new Exception( _x( 'Error while fetching label PDF', 'dhl', 'woocommerce-germanized' ) );
		}

		$label->set_shop_order_id( $order_id );
		$label->set_wp_int_awb( $awb );
		$label->set_wp_int_barcode( $barcode );
		$label->set_number( $barcode );

		$label->save();

		return $label;
	}

	protected function clean_state( $string ) {
		// Remove han chinese chars
		$string = preg_replace( '/\p{Han}+/u', '', $string );
		$string = str_replace( array( '(', ')', '/' ), '', $string );
		// Remove double white spaces
		$string = preg_replace( '/\s+/', ' ', $string );

		return trim( $string );
	}

	/**
	 * Creates a new order based on the given data
	 *
	 * @see https://api-qa.deutschepost.com/dpi-apidoc/index_prod_v1.html#/reference/orders/create-order/create-order
	 *
	 * @param DeutschePost $label
	 *
	 * @throws Exception
	 */
	public function create_label( &$label ) {

		if ( ! $shipment = $label->get_shipment() ) {
			throw new Exception( _x( 'Missing shipment', 'dhl', 'woocommerce-germanized' ) );
		}

		$customs_data     = wc_gzd_dhl_get_shipment_customs_data( $label, 33 );
		$positions        = array();
		$position_index   = 0;
		$total_value      = 0;
		$total_net_weight = 0;

		foreach ( $customs_data['items'] as $position ) {
			/**
			 * The Warenpost API expects value and weight to be a per row value, e.g.
			 * if 2x Product A is included the total weight/value is expected. In contrarian to the DHL customs API.
			 */
			$pos_net_weight    = intval( wc_get_weight( $position['weight_in_kg'], 'g', 'kg' ) );
			$total_value      += $position['value'];
			$total_net_weight += $pos_net_weight;

			array_push(
				$positions,
				array(
					'contentPieceIndexNumber' => $position_index++,
					'contentPieceHsCode'      => $position['tariff_number'],
					'contentPieceDescription' => $position['description'],
					'contentPieceValue'       => $position['value'],
					'contentPieceNetweight'   => $pos_net_weight,
					'contentPieceOrigin'      => $position['origin_code'],
					'contentPieceAmount'      => $position['quantity'],
				)
			);
		}

		$is_return      = 'return' === $label->get_type();
		$sender_name    = ( $shipment->get_sender_company() ? $shipment->get_sender_company() . ' ' : '' ) . $shipment->get_formatted_sender_full_name();
		$recipient_name = $shipment->get_formatted_full_name();
		$recipient      = $recipient_name;

		if ( $shipment->get_company() ) {
			$recipient = empty( $recipient_name ) ? $shipment->get_company() : $shipment->get_company() . ', ' . $recipient_name;

			/**
			 * In case company + name exceeds length - use company name only
			 */
			if ( ! empty( $recipient_name ) && strlen( $recipient ) > 30 ) {
				$recipient = $shipment->get_company();
			}
		}

		/**
		 * @see https://api-qa.deutschepost.com/dpi-apidoc/#/reference/orders/create-order/create-order
		 */
		$request_data = array(
			'customerEkp' => $this->get_ekp(),
			'orderId'     => null,
			'items'       => array(
				array(
					'id'                  => 0,
					'product'             => $label->get_product_id(),
					'serviceLevel'        => apply_filters( 'woocommerce_gzd_deutsche_post_label_api_customs_shipment_service_level', 'STANDARD', $label ),
					'recipient'           => mb_substr( $recipient, 0, 30 ),
					'recipientPhone'      => $shipment->get_phone(),
					'recipientEmail'      => $shipment->get_email(),
					'addressLine1'        => mb_substr( $shipment->get_address_1(), 0, 30 ),
					'addressLine2'        => mb_substr( $shipment->get_address_2(), 0, 30 ),
					'city'                => $shipment->get_city(),
					'state'               => mb_substr( $this->clean_state( wc_gzd_dhl_format_label_state( $shipment->get_state(), $shipment->get_country() ) ), 0, 20 ),
					'postalCode'          => $shipment->get_postcode(),
					'destinationCountry'  => $shipment->get_country(),
					'shipmentAmount'      => wc_format_decimal( $shipment->get_total() + $shipment->get_additional_total(), 2 ),
					'shipmentCurrency'    => get_woocommerce_currency(),
					'shipmentGrossWeight' => wc_get_weight( $label->get_weight(), 'g', 'kg' ),
					'senderName'          => mb_substr( $sender_name, 0, 40 ),
					'senderAddressLine1'  => mb_substr( $shipment->get_sender_address_1(), 0, 35 ),
					'senderAddressLine2'  => mb_substr( $shipment->get_sender_address_2(), 0, 35 ),
					'senderCountry'       => $shipment->get_sender_country(),
					'senderCity'          => $shipment->get_sender_city(),
					'senderPostalCode'    => $shipment->get_sender_postcode(),
					'senderPhone'         => $shipment->get_sender_phone(),
					'senderEmail'         => $shipment->get_sender_email(),
					'returnItemWanted'    => false,
					'shipmentNaturetype'  => strtoupper( apply_filters( 'woocommerce_gzd_deutsche_post_label_api_customs_shipment_nature_type', ( $is_return ? 'RETURN_GOODS' : 'SALE_GOODS' ), $label ) ),
					'contents'            => array(),
				),
			),
			'orderStatus' => 'FINALIZE',
			'paperwork'   => array(
				'contactName'     => $sender_name,
				'awbCopyCount'    => 1,
				'jobReference'    => null,
				'pickupType'      => 'CUSTOMER_DROP_OFF',
				'pickupLocation'  => null,
				'pickupDate'      => null,
				'pickupTimeSlot'  => null,
				'telephoneNumber' => $shipment->get_sender_phone(),
			),
		);

		// Do only add customs data in case it is a non-EU shipment
		if ( Package::is_crossborder_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
			$request_data['items'][0]['contents'] = $positions;

			/**
			 * If the total position net weight and/or value is greater than the global shipment value
			 * use the position value instead.
			 */
			if ( $total_net_weight > $request_data['items'][0]['shipmentGrossWeight'] ) {
				$request_data['items'][0]['shipmentGrossWeight'] = $total_net_weight;
			}

			if ( $total_value > $request_data['items'][0]['shipmentAmount'] ) {
				$request_data['items'][0]['shipmentAmount'] = $total_value;
			}
		}

		$transmit_data = wc_string_to_bool( Package::get_setting( 'label_force_email_transfer' ) );

		if ( $dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() ) ) {
			$transmit_data = $dhl_order->supports_email_notification();
		}

		if ( ! apply_filters( 'woocommerce_gzd_deutsche_post_label_api_customs_transmit_communication_data', $transmit_data, $label ) ) {
			if ( $is_return ) {
				$request_data['senderPhone'] = '';
				$request_data['senderEmail'] = '';
			} else {
				$request_data['recipientPhone'] = '';
				$request_data['recipientEmail'] = '';
			}
		}

		$request_data = $this->walk_recursive_remove( $request_data );
		$result       = $this->post_request( '/dpi/shipping/v1/orders', json_encode( $request_data, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

		if ( isset( $result->shipments ) ) {
			return $this->update_label( $label, $result );
		} else {
			throw new Exception( _x( 'Invalid API response', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	protected function get_user_token() {
		$user_token = false;

		if ( get_transient( 'woocommerce_gzd_im_wp_int_user_token' ) ) {
			$user_token = get_transient( 'woocommerce_gzd_im_wp_int_user_token' );
		} else {
			$response_body = $this->get_request( '/v1/auth/accesstoken', array(), 'xml' );

			$reg_exp_ut = '/<userToken>(.+?)<\/userToken>/';

			if ( preg_match( $reg_exp_ut, $response_body, $match_ut ) ) {
				$user_token = $match_ut[1];

				set_transient( 'woocommerce_gzd_im_wp_int_user_token', $user_token, ( MINUTE_IN_SECONDS * 3 ) );
			}
		}

		if ( ! $user_token ) {
			throw new Exception( _x( 'Error while authenticating user.', 'dhl', 'woocommerce-germanized' ) );
		}

		return $user_token;
	}

	protected function is_sandbox() {
		return Package::is_debug_mode() && defined( 'WC_GZD_DHL_IM_WP_SANDBOX_USER' );
	}

	protected function get_auth() {
		return $this->get_basic_auth_encode( Package::get_internetmarke_warenpost_int_username(), Package::get_internetmarke_warenpost_int_password() );
	}

	/**
	 * Could be either:
	 *
	 * - application/pdf (A6)
	 * - application/pdf+singlepage (A6)
	 * - application/pdf+singlepage+6x4 (6x4 inch)
	 * - application/zpl (A6)
	 * - application/zpl+rotated (rotated by 90 degrees for label printers)
	 * - application/zpl+6x4 (6x4 inch)
	 * - application/zpl+rotated+6x4 (6x4 inch and rotated by 90 degrees for label printers)
	 *
	 * @return string
	 */
	protected function get_pdf_accept_header() {
		return apply_filters( 'woocommerce_gzd_deutsche_post_label_api_pdf_accept_header', 'application/pdf' );
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		if ( '/v1/auth/accesstoken' !== $endpoint ) {
			$token         = $this->get_user_token();
			$authorization = $token;
		}

		parent::set_header( $authorization );

		/**
		 * Add PDF header to make sure we are receiving the right file type from DP API
		 */
		if ( strpos( $endpoint, 'itemlabels' ) !== false ) {
			$this->remote_header['Accept'] = $this->get_pdf_accept_header();
		}

		$date = new \DateTime( 'now', new \DateTimeZone( 'Europe/Berlin' ) );

		$this->remote_header = array_merge(
			$this->remote_header,
			array(
				'KEY_PHASE'         => $this->get_key_phase(),
				'PARTNER_ID'        => $this->get_partner_id(),
				'REQUEST_TIMESTAMP' => $date->format( 'dmY-His' ),
				'PARTNER_SIGNATURE' => $this->get_signature( $date ),
			)
		);
	}

	protected function get_ekp() {
		return Package::get_internetmarke_warenpost_int_ekp();
	}

	protected function walk_recursive_remove( array $array ) {
		foreach ( $array as $k => $v ) {

			if ( is_array( $v ) ) {
				$array[ $k ] = $this->walk_recursive_remove( $v );
			}

			// Explicitly allow street_number fields to equal 0
			if ( '' === $v || is_null( $v ) ) {
				unset( $array[ $k ] );
			}
		}

		return $array;
	}

	protected function get_basic_auth_encode( $user, $pass ) {
		$pass = htmlentities( $pass, ENT_XML1 );

		return base64_encode( $user . ':' . $pass ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	protected function handle_get_response( $response_code, $response_body ) {
		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			default:
				throw new Exception( _x( 'Error during Warenpost International request.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	protected function handle_post_response( $response_code, $response_body ) {
		switch ( $response_code ) {
			case '200':
			case '201':
				break;
			default:
				$error_message = '';

				if ( isset( $response_body->messages ) ) {
					foreach ( $response_body->messages as $message ) {
						$error_message .= ( ! empty( $error_message ) ? ', ' : '' ) . $message;
					}
				}

				if ( empty( $error_message ) ) {
					$error_message = $response_code;
				}

				throw new Exception( sprintf( _x( 'Error during request: %s', 'dhl', 'woocommerce-germanized' ), $error_message ) );
		}
	}

	protected function get_partner_id() {
		return $this->is_sandbox() ? 'DP_LT' : Package::get_internetmarke_partner_id();
	}

	protected function get_key_phase() {
		return $this->is_sandbox() ? 1 : Package::get_internetmarke_key_phase();
	}

	protected function get_partner_token() {
		return Package::get_internetmarke_token();
	}

	protected function get_signature( $date = null ) {
		if ( ! $date ) {
			$date = new \DateTime( 'now', new \DateTimeZone( 'Europe/Berlin' ) );
		}

		return substr(
			md5(
				join(
					'::',
					array(
						$this->get_partner_id(),
						$date->format( 'dmY-His' ),
						$this->get_key_phase(),
						$this->get_partner_token(),
					)
				)
			),
			0,
			8
		);
	}
}
