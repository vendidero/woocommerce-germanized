<?php

namespace Vendidero\Shiptastic\Compatibility;

use Vendidero\Shiptastic\Interfaces\Compatibility;
use Vendidero\Shiptastic\ShippingProvider\Helper;

defined( 'ABSPATH' ) || exit;

class ShipmentTracking implements Compatibility {

	public static function is_active() {
		return function_exists( 'wc_st_add_tracking_number' );
	}

	public static function init() {
		add_filter(
			'wc_shipment_tracking_before_add_tracking_items',
			function ( $tracking_items, $tracking_item, $order_id ) {
				self::transfer_tracking_to_shipment( $tracking_item, $order_id );

				return $tracking_items;
			},
			10,
			3
		);

		add_filter(
			'wc_shipment_tracking_before_delete_tracking_items',
			function ( $tracking_items, $tracking_item, $order_id ) {
				self::remove_tracking_from_shipment( $tracking_item, $order_id );

				return $tracking_items;
			},
			10,
			3
		);
	}

	public static function transfer_tracking_to_shipment( $tracking_item, $order_id ) {
		$tracking_item = wp_parse_args(
			$tracking_item,
			array(
				'tracking_number'          => '',
				'custom_tracking_provider' => '',
				'tracking_provider'        => '',
			)
		);

		if ( ! empty( $tracking_item['tracking_number'] ) ) {
			if ( $shipment_order = wc_stc_get_shipment_order( $order_id ) ) {
				if ( $shipment = $shipment_order->get_last_shipment_without_tracking() ) {
					$shipment->set_shipping_provider( '' );
					$shipment->set_tracking_id( $tracking_item['tracking_number'] );

					$provider_title = $tracking_item['custom_tracking_provider'] ? $tracking_item['custom_tracking_provider'] : $tracking_item['tracking_provider'];
					$provider       = false;

					if ( ! empty( $provider_title ) ) {
						$provider = Helper::instance()->get_shipping_provider_by_title( $provider_title );
					}

					$provider = apply_filters( 'woocommerce_shiptastic_shipment_tracking_item_shipping_provider', $provider, $provider_title, $tracking_item );

					if ( $provider ) {
						$shipment->set_shipping_provider( $provider->get_name() );
					}

					$shipment->update_status( 'shipped' );
				}
			}
		}
	}

	public static function remove_tracking_from_shipment( $tracking_item, $order_id ) {
		$tracking_item = wp_parse_args(
			$tracking_item,
			array(
				'tracking_number' => '',
			)
		);

		if ( ! empty( $tracking_item['tracking_number'] ) ) {
			$shipments = wc_stc_get_shipments(
				array(
					'order_id'    => $order_id,
					'tracking_id' => $tracking_item['tracking_number'],
				)
			);

			if ( ! empty( $shipments ) ) {
				foreach ( $shipments as $shipment ) {
					$shipment->remove_tracking();
					$shipment->save();
				}
			}
		}
	}
}
