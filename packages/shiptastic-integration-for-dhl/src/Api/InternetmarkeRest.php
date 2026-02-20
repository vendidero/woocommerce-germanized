<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Label\DeutschePost;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class InternetmarkeRest extends \Vendidero\Shiptastic\API\REST {

	public function get_title() {
		return _x( 'Internetmarke REST', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl_im_rest';
	}

	public function get_url() {
		return 'https://api-eu.dhl.com/post/de/shipping/im/v1/';
	}

	protected function get_auth_instance() {
		return new InternetmarkeAuth( $this );
	}

	/**
	 * @param Response $response
	 *
	 * @return Response
	 */
	protected function parse_error( $response ) {
		$response = parent::parse_error( $response );
		$body     = $response->get_body();
		$code     = $response->get_code();
		$error    = new ShipmentError();

		if ( 401 === $code ) {
			$error->add( 401, sprintf( _x( 'Your Internetmarke <a href="%s">API credentials</a> seem to be invalid or the API access has not yet been approved.', 'dhl', 'woocommerce-germanized' ), esc_url( Package::get_deutsche_post_shipping_provider()->get_edit_link() ) ) );
		} elseif ( isset( $body['description'] ) ) {
			$title         = isset( $body['title'] ) ? wp_kses_post( wp_unslash( $body['title'] ) ) . ': ' : '';
			$error_message = $title . wp_kses_post( wp_unslash( $body['description'] ) );

			$error->add( $code, $error_message );
		}

		if ( $error->has_errors() ) {
			$response->set_error( $error );
		}

		return $response;
	}

	public function update_balance( $new_balance ) {
		set_transient( 'wc_stc_dhl_portokasse_balance', absint( $new_balance ), HOUR_IN_SECONDS );
	}

	public function refresh_balance() {
		$this->get_auth_instance()->revoke();
		delete_transient( 'wc_stc_dhl_portokasse_balance' );

		$this->get_auth_instance()->auth();
	}

	public function get_page_formats() {
		$response = $this->get( 'app/catalog', array( 'types' => 'PAGE_FORMATS' ) );

		if ( $response->is_error() ) {
			throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), esc_html( $response->get_code() ) );
		}

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( ! empty( $body['pageFormats'] ) ) {
				return wc_clean( $body['pageFormats'] );
			}
		}

		return array();
	}

	/**
	 * @return bool
	 */
	protected function is_auth_request( $url ) {
		$auth_url = $this->get_auth_api()->get_url();

		if ( empty( $auth_url ) ) {
			return false;
		}

		return $this->get_request_url( 'user' ) === $url;
	}

	public function get_preview_link( $product_id, $address_type = 'FRANKING_ZONE' ) {
		$response = $this->post(
			'app/shoppingcart/png?validate=true',
			array(
				'type'          => 'AppShoppingCartPreviewPNGRequest',
				'productCode'   => $product_id,
				'voucherLayout' => $address_type,
			)
		);

		if ( ! $response->is_error() ) {
			return $response->get_body()['link'];
		} else {
			throw new \Exception( wp_kses_post( $response->get_error()->get_error_message() ) );
		}
	}

	public function charge_wallet( $amount ) {
		$response = $this->put( 'app/wallet?amount=' . Package::eur_to_cents( $amount ) );

		if ( ! $response->is_error() ) {
			$new_balance = wc_clean( $response->get_body()['walletBalance'] );

			$this->update_balance( $new_balance );

			return Package::cents_to_eur( $new_balance );
		} else {
			throw new \Exception( wp_kses_post( $response->get_error()->get_error_message() ) );
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\DeutschePost $label
	 *
	 * @throws \Exception
	 */
	public function get_label( $label ) {
		$shipment = $label->get_shipment();

		if ( ! $shipment ) {
			throw new \Exception( esc_html( sprintf( _x( 'Could not fetch shipment %d.', 'dhl', 'woocommerce-germanized' ), $label->get_shipment_id() ) ) );
		}

		$has_contact_name = $shipment->get_sender_first_name() || $shipment->get_sender_last_name();

		$sender = array(
			'name'           => wc_shiptastic_substring( ( $has_contact_name ? ( $shipment->get_sender_first_name() . ' ' . $shipment->get_sender_last_name() ) : $shipment->get_sender_company() ), 0, 50 ),
			'additionalName' => wc_shiptastic_substring( ( $has_contact_name ? $shipment->get_sender_company() : '' ), 0, 40 ),
			'addressLine1'   => wc_shiptastic_substring( $shipment->get_sender_address_1(), 0, 50 ),
			'addressLine2'   => wc_shiptastic_substring( $shipment->get_sender_address_2(), 0, 60 ),
			'postalCode'     => $shipment->get_sender_postcode(),
			'city'           => wc_shiptastic_substring( $shipment->get_sender_city(), 0, 40 ),
			'country'        => wc_stc_country_to_alpha3( $shipment->get_sender_country() ),
		);

		$receiver_address_2 = $shipment->get_address_2();

		if ( 'simple' === $shipment->get_type() && $shipment->send_to_external_pickup( 'locker' ) ) {
			$receiver_address_2 = ( empty( $receiver_address_2 ) ? '' : $receiver_address_2 . ' ' ) . $shipment->get_pickup_location_customer_number();
		}

		$receiver = array(
			'name'           => wc_shiptastic_substring( $shipment->get_first_name() . ' ' . $shipment->get_last_name(), 0, 50 ),
			'additionalName' => wc_shiptastic_substring( $shipment->get_company(), 0, 40 ),
			'addressLine1'   => wc_shiptastic_substring( $shipment->get_address_1(), 0, 50 ),
			'addressLine2'   => wc_shiptastic_substring( $receiver_address_2, 0, 60 ),
			'postalCode'     => $shipment->get_postcode(),
			'city'           => wc_shiptastic_substring( $shipment->get_city(), 0, 40 ),
			'country'        => wc_stc_country_to_alpha3( $shipment->get_country() ),
		);

		/**
		 * Adjust the Deutsche Post (Internetmarke) label print X position.
		 *
		 * @param mixed $x The x axis position.
		 * @param DeutschePost $label The label instance.
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @since 3.4.5
		 * @package Vendidero/Shiptastic/DHL
		 */
		$label_x = apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_position_x', $label->get_position_x(), $label, $shipment );
		/**
		 * Adjust the Deutsche Post (Internetmarke) label print Y position.
		 *
		 * @param mixed $y The y axis position.
		 * @param DeutschePost $label The label instance.
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @since 3.4.5
		 * @package Vendidero/Shiptastic/DHL
		 */
		$label_y           = apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_position_y', $label->get_position_y(), $label, $shipment );
		$label_page_number = apply_filters( 'woocommerce_shiptastic_deutsche_post_label_api_page_number', 1, $label, $shipment );

		$request = array(
			'type'               => 'AppShoppingCartPDFRequest',
			'total'              => $label->get_stamp_total(),
			'createManifest'     => true,
			'createShippingList' => 2,
			'dpi'                => 'DPI300',
			'pageFormatId'       => $label->get_print_format(),
			'positions'          => array(
				array(
					'productCode'   => $label->get_product_id(),
					'address'       => array(
						'sender'   => $sender,
						'receiver' => $receiver,
					),
					'voucherLayout' => 'ADDRESS_ZONE',
					'positionType'  => 'AppShoppingCartPDFPosition',
					'position'      => array(
						'labelX' => $label_x,
						'labelY' => $label_y,
						'page'   => $label_page_number,
					),
				),
			),
		);

		$response = $this->post( 'app/shoppingcart/pdf?directCheckout=true&validate=false', $this->clean_request( $request ) );

		if ( $response->is_error() ) {
			throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), esc_html( $response->get_code() ) );
		}

		$stamp = $response->get_body();

		if ( ! empty( $stamp['link'] ) ) {
			$label->set_shop_order_id( wc_clean( $stamp['shoppingCart']['shopOrderId'] ) );
			$label->set_original_url( $stamp['link'] );

			$voucher_list = (array) $stamp['shoppingCart']['voucherList'];

			foreach ( $voucher_list as $voucher ) {
				if ( isset( $voucher['trackId'] ) ) {
					$label->set_number( wc_clean( $voucher['trackId'] ) );
				} else {
					$label->set_number( wc_clean( $voucher['voucherId'] ) );
				}

				$label->set_voucher_id( wc_clean( $voucher['voucherId'] ) );
			}

			if ( isset( $stamp['manifestLink'] ) ) {
				$label->set_manifest_url( $stamp['manifestLink'] );
			}

			if ( isset( $stamp['walletBallance'] ) ) {
				$this->update_balance( $stamp['walletBallance'] );
			}

			$label->save();
			$result = $label->download_label_file( $stamp['link'] );

			if ( ! $result ) {
				throw new \Exception( esc_html_x( 'Error while downloading the PDF stamp.', 'dhl', 'woocommerce-germanized' ) );
			}

			$label->save();

			return $label;
		} else {
			throw new \Exception( esc_html_x( 'Invalid stamp response.', 'dhl', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\DHL\Label\DeutschePost $label
	 *
	 * @throws \Exception
	 */
	public function refund_label( $label ) {
		$request = array(
			'shoppingCart' => array(
				'shopOrderId' => $label->get_shop_order_id(),
				'voucherList' => array(
					array(
						'voucherId' => $label->get_voucher_id(),
					),
				),
			),
		);

		$response = $this->post( 'app/retoure', $this->clean_request( $request ) );

		if ( $response->is_error() ) {
			throw new \Exception( wp_kses_post( implode( "\n", $response->get_error()->get_error_messages() ) ), absint( $response->get_code() ) );
		}

		$result = $response->get_body();

		return ! empty( $result['retoureTransactionId'] ) ? wc_clean( $result['retoureTransactionId'] ) : '';
	}
}
