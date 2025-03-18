<?php
namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;
abstract class Shipment extends \Vendidero\Shiptastic\Shipment {

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return false|SimpleShipment|ReturnShipment
	 */
	public static function from_shiptastic( $shipment ) {
		if ( is_a( $shipment, '\Vendidero\Shiptastic\SimpleShipment' ) ) {
			$classname = '\Vendidero\Germanized\Shipments\SimpleShipment';
		} elseif ( is_a( $shipment, '\Vendidero\Shiptastic\ReturnShipment' ) ) {
			$classname = '\Vendidero\Germanized\Shipments\ReturnShipment';
		}

		$classname = apply_filters( 'woocommerce_gzd_shipment_class', $classname, $shipment->get_id(), $shipment->get_type() );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			$legacy_shipment = new $classname( $shipment );
			$legacy_shipment->set_changes( $shipment->get_changes() );

			return $legacy_shipment;
		} catch ( \Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $shipment->get_id(), $shipment->get_type() ) );
			return false;
		}
	}

	public function set_changes( $changes ) {
		$this->changes = $changes;
		$this->items   = null; // Force reloading items with the right legacy type
	}

	public function get_items( $context = 'admin' ) {
		add_filter( 'woocommerce_shiptastic_shipment_item_class', array( '\Vendidero\Germanized\Shiptastic', 'legacy_shipment_item_classname' ), 10, 3 );
		$items = parent::get_items( $context );
		remove_filter( 'woocommerce_shiptastic_shipment_item_class', array( '\Vendidero\Germanized\Shiptastic', 'legacy_shipment_item_classname' ), 10 );

		return $items;
	}
}
