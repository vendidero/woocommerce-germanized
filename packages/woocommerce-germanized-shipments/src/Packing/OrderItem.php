<?php

namespace Vendidero\Germanized\Shipments\Packing;

use Vendidero\Germanized\Shipments\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * An item to be packed.
 */
class OrderItem extends Item {

	/**
	 * Box constructor.
	 *
	 * @param \WC_Order_Item_Product $item
	 *
	 * @throws \Exception
	 */
	public function __construct( $item ) {
		$this->item = $item;

		if ( ! is_callable( array( $item, 'get_product' ) ) ) {
			throw new \Exception( 'Invalid item' );
		}

		if ( $s_product = $this->get_product() ) {
			$width  = empty( $s_product->get_shipping_width() ) ? 0 : (float) wc_format_decimal( $s_product->get_shipping_width() );
			$length = empty( $s_product->get_shipping_length() ) ? 0 : (float) wc_format_decimal( $s_product->get_shipping_length() );
			$depth  = empty( $s_product->get_shipping_height() ) ? 0 : (float) wc_format_decimal( $s_product->get_shipping_height() );

			$this->dimensions = array(
				'width'  => (int) ceil( (float) wc_get_dimension( $width, 'mm' ) ),
				'length' => (int) ceil( (float) wc_get_dimension( $length, 'mm' ) ),
				'depth'  => (int) ceil( (float) wc_get_dimension( $depth, 'mm' ) ),
			);

			$weight       = empty( $this->product->get_weight() ) ? 0 : (float) wc_format_decimal( $this->product->get_weight() );
			$this->weight = (int) ceil( (float) wc_get_weight( $weight, 'g' ) );
		}

		$quantity      = (int) ceil( (float) $item->get_quantity() );
		$incl_taxes    = $item->get_order() ? $item->get_order()->get_prices_include_tax() : wc_prices_include_tax();
		$line_total    = (int) wc_add_number_precision( $this->item->get_total() );
		$line_subtotal = (int) wc_add_number_precision( $this->item->get_subtotal() );

		if ( $incl_taxes ) {
			$line_total    += (int) wc_add_number_precision( $this->item->get_total_tax() );
			$line_subtotal += (int) wc_add_number_precision( $this->item->get_subtotal_tax() );
		}

		$this->total    = $quantity > 0 ? NumberUtil::round( $line_total / $quantity ) : 0;
		$this->subtotal = $quantity > 0 ? NumberUtil::round( $line_subtotal / $quantity ) : 0;
	}

	protected function load_product() {
		if ( $product = $this->item->get_product() ) {
			$this->product = apply_filters( 'woocommerce_gzd_shipments_order_item_product', wc_gzd_shipments_get_product( $product ), $this->item );
		}
	}

	public function get_id() {
		return $this->item->get_id();
	}

	/**
	 * @return \WC_Order_Item_Product
	 */
	public function get_order_item() {
		return $this->get_reference();
	}
}
