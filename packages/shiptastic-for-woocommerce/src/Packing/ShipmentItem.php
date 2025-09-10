<?php

namespace Vendidero\Shiptastic\Packing;

use Vendidero\Shiptastic\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class ShipmentItem extends Item {

	/**
	 * Box constructor.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item
	 */
	public function __construct( $item ) {
		$this->item = $item;

		if ( $shipment = $item->get_shipment() ) {
			$dimension_unit = $shipment->get_dimension_unit();
			$weight_unit    = $shipment->get_weight_unit();
		} else {
			$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );
			$weight_unit    = get_option( 'woocommerce_weight_unit', 'kg' );
		}

		$width  = empty( $this->item->get_width() ) ? 0 : (float) wc_format_decimal( $this->item->get_width() );
		$length = empty( $this->item->get_length() ) ? 0 : (float) wc_format_decimal( $this->item->get_length() );
		$depth  = empty( $this->item->get_height() ) ? 0 : (float) wc_format_decimal( $this->item->get_height() );

		$this->dimensions = array(
			'width'  => (int) ceil( (float) wc_get_dimension( $width, 'mm', $dimension_unit ) ),
			'length' => (int) ceil( (float) wc_get_dimension( $length, 'mm', $dimension_unit ) ),
			'depth'  => (int) ceil( (float) wc_get_dimension( $depth, 'mm', $dimension_unit ) ),
		);

		$weight        = empty( $this->item->get_weight() ) ? 0 : (float) wc_format_decimal( $this->item->get_weight() );
		$quantity      = (int) ceil( (float) $item->get_quantity() );
		$line_total    = (int) wc_add_number_precision( $this->item->get_total() );
		$line_subtotal = (int) wc_add_number_precision( $this->item->get_subtotal() );

		$this->weight = (int) ceil( (float) wc_get_weight( $weight, 'g', $weight_unit ) );

		$this->total    = $quantity > 0 ? NumberUtil::round( $line_total / $quantity ) : 0;
		$this->subtotal = $quantity > 0 ? NumberUtil::round( $line_subtotal / $quantity ) : 0;
	}

	/**
	 * @return \Vendidero\Shiptastic\ShipmentItem
	 */
	public function get_shipment_item() {
		return $this->get_reference();
	}

	public function get_id() {
		return $this->item->get_id();
	}

	protected function load_product() {
		$this->product = wc_shiptastic_get_product( $this->item->get_product() );
	}
}
