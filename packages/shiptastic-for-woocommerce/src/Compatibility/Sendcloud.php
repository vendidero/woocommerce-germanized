<?php

namespace Vendidero\Shiptastic\Compatibility;

use Vendidero\Shiptastic\Extensions;
use Vendidero\Shiptastic\Interfaces\Compatibility;
use Vendidero\Shiptastic\ShippingProvider\Helper;

defined( 'ABSPATH' ) || exit;

class Sendcloud implements Compatibility {

	public static function is_active() {
		return Extensions::is_plugin_active( 'sendcloud-connected-shipping' );
	}

	public static function init() {
		add_filter( 'woocommerce_new_order_note_data', array( __CLASS__, 'parse_order_note' ), 10, 2 );
	}

	/**
	 * Check whether the note contains Sendcloud content and act accordingly.
	 * Sample: The Austrian Post tracking number for this SendCloud shipment is: 1234567890 and can be traced at: http://sendcloud.com?tracking
	 *
	 * @param $data
	 * @param $note_details
	 *
	 * @return array
	 */
	public static function parse_order_note( $data, $note_details ) {
		$data         = wp_parse_args(
			$data,
			array(
				'comment_content' => '',
			)
		);
		$note_details = wp_parse_args(
			$note_details,
			array(
				'order_id' => 0,
			)
		);

		if ( strstr( $data['comment_content'], 'tracking number for this SendCloud shipment' ) ) {
			$tracking_number         = '';
			$shipping_provider_title = '';
			$tracking_url            = '';

			if ( $shipment_order = wc_stc_get_shipment_order( $note_details['order_id'] ) ) {
				preg_match( '/shipment is: ?([^\s]+)/', $data['comment_content'], $matches );

				if ( 2 === count( $matches ) ) {
					$tracking_number = $matches[1];
					$tracking_number = strtoupper( $tracking_number );
					$tracking_number = preg_replace( '/[^A-Z0-9]/', '', $tracking_number ); // Remove non-alphanumeric characters.
				}

				preg_match( '/The (.*?) tracking number for/', $data['comment_content'], $matches );

				if ( 2 === count( $matches ) ) {
					$shipping_provider_title = $matches[1];
				}

				preg_match( '/can be traced at: ?([^\s]+)/', $data['comment_content'], $matches );

				if ( 2 === count( $matches ) ) {
					$tracking_url = sanitize_url( $matches[1] );
				}

				if ( ! empty( $tracking_number ) ) {
					if ( $shipment = $shipment_order->get_last_shipment_without_tracking() ) {
						$shipment->set_shipping_provider( '' );
						$shipment->set_tracking_id( $tracking_number );

						if ( ! empty( $shipping_provider_title ) ) {
							$provider = Helper::instance()->get_shipping_provider_by_title( $shipping_provider_title );
						}

						$provider = apply_filters( 'woocommerce_shiptastic_sencloud_shipping_provider', $provider, $shipping_provider_title );

						if ( $provider ) {
							$shipment->set_shipping_provider( $provider->get_name() );
						} elseif ( ! empty( $shipping_provider_title ) ) {
							$shipment->set_shipping_provider_title( $shipping_provider_title );

							if ( ! empty( $tracking_url ) ) {
								$shipment->set_tracking_url( $tracking_url );
							}
						}

						if ( apply_filters( 'woocommerce_shiptastic_sencloud_mark_shipment_as_shipped', false, $shipment ) ) {
							$shipment->update_status( 'shipped' );
						} else {
							$shipment->save();
						}
					}
				}
			}
		}

		return $data;
	}
}
