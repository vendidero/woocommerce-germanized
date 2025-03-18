<?php

namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

class ShipmentItem extends \Vendidero\Shiptastic\ShipmentItem {

	/**
	 * @param \Vendidero\Shiptastic\ShipmentItem $item
	 *
	 * @return false
	 */
	public static function from_shiptastic( $item ) {
		if ( is_a( $item, '\Vendidero\Shiptastic\ShipmentItem' ) ) {
			$classname = '\Vendidero\Germanized\Shipments\ShipmentItem';
		} elseif ( is_a( $item, '\Vendidero\Shiptastic\ShipmentReturnItem' ) ) {
			$classname = '\Vendidero\Germanized\Shipments\ShipmentReturnItem';
		}

		$classname = apply_filters( 'woocommerce_gzd_shipment_item_class', $classname, $item->get_id(), $item->get_type() );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			$legacy_item = new $classname( $item );
			$legacy_item->set_changes( $item->get_changes() );

			return $legacy_item;
		} catch ( \Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $item->get_id(), $item->get_type() ) );
			return false;
		}
	}
}
