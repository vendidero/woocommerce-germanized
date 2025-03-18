<?php
namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;
abstract class Shipment extends \Vendidero\Shiptastic\Shipment {

	public function get_items( $context = 'admin' ) {
		add_filter( 'woocommerce_shiptastic_shipment_item_class', array( '\Vendidero\Germanized\Shiptastic', 'legacy_shipment_item_classname' ), 10, 3 );
		$items = parent::get_items( $context );
		remove_filter( 'woocommerce_shiptastic_shipment_item_class', array( '\Vendidero\Germanized\Shiptastic', 'legacy_shipment_item_classname' ), 10 );

		return $items;
	}
}
