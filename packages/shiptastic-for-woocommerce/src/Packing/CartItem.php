<?php

namespace Vendidero\Shiptastic\Packing;

use Vendidero\Shiptastic\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class CartItem extends Item {

	/**
	 * Box constructor.
	 *
	 * @param array $item
	 * @param boolean $incl_taxes
	 *
	 * @throws \Exception
	 */
	public function __construct( $item, $incl_taxes = false ) {
		$this->item = $item;

		if ( ! isset( $this->item['data'] ) || ! is_a( $this->item['data'], 'WC_Product' ) ) {
			throw new \Exception( 'Invalid item' );
		}

		$width  = empty( $this->get_product()->get_shipping_width() ) ? 0 : (float) wc_format_decimal( $this->get_product()->get_shipping_width() );
		$length = empty( $this->get_product()->get_shipping_length() ) ? 0 : (float) wc_format_decimal( $this->get_product()->get_shipping_length() );
		$depth  = empty( $this->get_product()->get_shipping_height() ) ? 0 : (float) wc_format_decimal( $this->get_product()->get_shipping_height() );

		$this->dimensions = array(
			'width'  => (int) wc_get_dimension( $width, 'mm' ),
			'length' => (int) wc_get_dimension( $length, 'mm' ),
			'depth'  => (int) wc_get_dimension( $depth, 'mm' ),
		);

		$weight        = empty( $this->get_product()->get_weight() ) ? 0 : (float) wc_format_decimal( $this->get_product()->get_weight() );
		$quantity      = (int) ceil( (float) $item['quantity'] );
		$line_total    = (int) wc_add_number_precision( $this->item['line_total'] );
		$line_subtotal = (int) wc_add_number_precision( $this->item['line_subtotal'] );

		if ( $incl_taxes ) {
			$line_total    += (int) wc_add_number_precision( $this->item['line_tax'] );
			$line_subtotal += (int) wc_add_number_precision( $this->item['line_subtotal_tax'] );
		}

		$this->weight   = (int) wc_get_weight( $weight, 'g' );
		$this->total    = $quantity > 0 ? NumberUtil::round( $line_total / $quantity ) : 0;
		$this->subtotal = $quantity > 0 ? NumberUtil::round( $line_subtotal / $quantity ) : 0;
	}

	protected function load_product() {
		$this->product = wc_shiptastic_get_product( $this->item['data'] );
	}

	/**
	 * @return array
	 */
	public function get_cart_item() {
		return $this->get_reference();
	}

	public function get_id() {
		return $this->get_product()->get_id();
	}
}
