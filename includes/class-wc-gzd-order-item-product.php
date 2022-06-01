<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooProduct class
 */
class WC_GZD_Order_Item_Product extends WC_GZD_Order_Item {

	/**
	 * @var WC_Order_Item_Product
	 */
	public $order_item = null;

	public function get_unit() {
		return $this->order_item->get_meta( '_unit', true );
	}

	public function set_unit( $unit ) {
		$this->order_item->update_meta_data( '_unit', $unit );
	}

	public function get_unit_base() {
		return $this->order_item->get_meta( '_unit_base', true );
	}

	public function get_deposit_type() {
		return $this->order_item->get_meta( '_deposit_type', true );
	}

	public function get_deposit_net_amount() {
		$amount = $this->order_item->get_meta( '_deposit_net_amount', true );

		if ( '' === $amount ) {
			$amount = wc_format_decimal( 0 );
		}

		return $amount;
	}

	private function parse_incl_tax( $incl_tax ) {
		if ( 'incl' === $incl_tax ) {
			$incl_tax = true;
		} elseif ( 'excl' === $incl_tax ) {
			$incl_tax = false;
		} elseif ( '' === $incl_tax ) {
			$incl_tax = false;
		}

		return wc_string_to_bool( $incl_tax );
	}

	public function get_deposit_amount( $incl_tax = false ) {
		$incl_tax = $this->parse_incl_tax( $incl_tax );

		if ( false === $incl_tax ) {
			return $this->get_deposit_net_amount();
		}

		$amount = $this->order_item->get_meta( '_deposit_amount', true );

		if ( '' === $amount ) {
			$amount = wc_format_decimal( 0 );
		}

		return $amount;
	}

	public function get_deposit_amount_html( $tax_display = '' ) {
		$args        = array();
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );

		if ( $order = $this->get_order_item()->get_order() ) {
			$args['currency'] = $order->get_currency();
		}

		$price_html = wc_price( $this->get_deposit_amount( 'incl' === $tax_display ? true : false ), $args );

		return wc_gzd_format_deposit_amount(
			$price_html,
			array(
				'type'            => $this->get_deposit_type(),
				'quantity'        => $this->get_deposit_quantity(),
				'packaging_type'  => $this->get_deposit_packaging_type(),
				'amount_per_unit' => $this->get_deposit_amount_per_unit( 'incl' === $tax_display ? true : false ),
			)
		);
	}

	public function get_deposit_quantity() {
		$quantity = $this->order_item->get_meta( '_deposit_quantity', true );

		if ( '' === $quantity ) {
			$quantity = 1;
		}

		return $quantity;
	}

	public function get_deposit_packaging_type() {
		return $this->order_item->get_meta( '_deposit_packaging_type', true );
	}

	public function get_deposit_packaging_type_title() {
		$returnable_type_title = '';

		if ( $returnable_type = $this->get_deposit_packaging_type() ) {
			$returnable_type_title = WC_germanized()->deposit_types->get_packaging_type_title( $returnable_type );
		}

		/**
		 * Filter to adjust the deposit packaging type title for an order item.
		 *
		 * @param string $title The title.
		 * @param WC_GZD_Order_Item_Product $order_item The order item product object.
		 *
		 * @since 3.9.0
		 */
		return apply_filters( 'woocommerce_gzd_order_item_product_deposit_packaging_type_title', $returnable_type_title, $this );
	}

	public function get_deposit_net_amount_per_unit() {
		$amount = $this->order_item->get_meta( '_deposit_net_amount_per_unit', true );

		if ( '' === $amount ) {
			$amount = wc_format_decimal( 0 );
		}

		return $amount;
	}

	public function get_deposit_amount_per_unit( $incl_tax = false ) {
		$incl_tax = $this->parse_incl_tax( $incl_tax );

		if ( false === $incl_tax ) {
			return $this->get_deposit_net_amount_per_unit();
		}

		$amount = $this->order_item->get_meta( '_deposit_amount_per_unit', true );

		if ( '' === $amount ) {
			$amount = wc_format_decimal( 0 );
		}

		return $amount;
	}

	public function has_deposit() {
		return ! empty( $this->get_deposit_type() ) && $this->get_deposit_amount() > 0;
	}

	public function get_formatted_unit_base() {
		return wc_gzd_format_unit_base( $this->get_unit_base() );
	}

	public function get_formatted_unit() {
		return wc_gzd_format_unit( $this->get_unit() );
	}

	public function set_unit_base( $unit ) {
		$this->order_item->update_meta_data( '_unit_base', $unit );
	}

	public function set_deposit_type( $deposit_type ) {
		$this->order_item->update_meta_data( '_deposit_type', $deposit_type );
	}

	public function set_deposit_packaging_type( $packaging_type ) {
		$this->order_item->update_meta_data( '_deposit_packaging_type', $packaging_type );
	}

	public function set_deposit_amount( $amount ) {
		$this->order_item->update_meta_data( '_deposit_amount', wc_format_decimal( $amount ) );
	}

	public function set_deposit_net_amount( $amount ) {
		$this->order_item->update_meta_data( '_deposit_net_amount', wc_format_decimal( $amount ) );
	}

	public function set_deposit_quantity( $amount ) {
		$this->order_item->update_meta_data( '_deposit_quantity', absint( $amount ) );
	}

	public function set_deposit_amount_per_unit( $amount ) {
		$this->order_item->update_meta_data( '_deposit_amount_per_unit', wc_format_decimal( $amount ) );
	}

	public function set_deposit_net_amount_per_unit( $amount ) {
		$this->order_item->update_meta_data( '_deposit_net_amount_per_unit', wc_format_decimal( $amount ) );
	}

	public function get_unit_product() {
		return $this->order_item->get_meta( '_unit_product', true );
	}

	public function set_unit_product( $unit ) {
		$this->order_item->update_meta_data( '_unit_product', $unit );
	}

	public function get_cart_description() {
		return $this->order_item->get_meta( '_item_desc', true );
	}

	public function get_defect_description() {
		return $this->order_item->get_meta( '_defect_description', true );
	}

	public function set_cart_description( $item_desc ) {
		$this->order_item->update_meta_data( '_item_desc', $item_desc );
	}

	public function set_defect_description( $item_desc ) {
		$this->order_item->update_meta_data( '_defect_description', $item_desc );
	}

	public function get_delivery_time() {
		return $this->order_item->get_meta( '_delivery_time', true );
	}

	public function set_delivery_time( $delivery_time ) {
		$this->order_item->update_meta_data( '_delivery_time', $delivery_time );
	}

	public function get_min_age() {
		/**
		 * Legacy check
		 */
		if ( ! $this->order_item->meta_exists( '_min_age' ) ) {
			if ( ( $product = $this->order_item->get_product() ) && ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) ) {
				return $gzd_product->get_min_age();
			}
		}

		return $this->order_item->get_meta( '_min_age', true );
	}

	public function set_min_age( $min_age ) {
		$this->order_item->update_meta_data( '_min_age', $min_age );
	}

	public function needs_age_verification() {
		$min_age = $this->get_min_age();

		/**
		 * Legacy check
		 */
		if ( ! $this->order_item->meta_exists( '_min_age' ) ) {
			if ( $product = $this->order_item->get_product() ) {
				return wc_gzd_needs_age_verification( $product );
			}
		}

		return ! empty( $min_age ) ? true : false;
	}

	public function has_unit_price() {
		$base = $this->get_unit_base();
		$unit = $this->get_unit();

		return ( ! empty( $base ) && ! empty( $unit ) ) ? true : false;
	}

	public function get_formatted_unit_price( $inc_tax = true, $after_discounts = false ) {
		$legacy = $this->order_item->get_meta( '_unit_price', true );

		if ( ! empty( $legacy ) ) {
			return $legacy;
		}

		$html       = '';
		$price_args = array();

		if ( $order = $this->order_item->get_order() ) {
			$price_args['currency'] = $order->get_currency();
		}

		if ( $this->has_unit_price() ) {
			$price = $after_discounts ? $this->get_unit_price() : $this->get_unit_price_subtotal();

			if ( ! $inc_tax ) {
				$price = $after_discounts ? $this->get_unit_price_net() : $this->get_unit_price_subtotal_net();
			}

			$html = wc_gzd_format_unit_price( wc_price( $price, $price_args ), $this->get_formatted_unit(), $this->get_formatted_unit_base(), wc_gzd_format_product_units_decimal( $this->get_unit_product() ) );
		}

		return $html;
	}

	public function get_formatted_product_units() {
		$legacy = $this->order_item->get_meta( '_units', true );

		if ( ! empty( $legacy ) ) {
			return $legacy;
		}

		/**
		 * Format
		 */
		$html = '';
		$text = get_option( 'woocommerce_gzd_product_units_text' );

		if ( $this->has_unit_product() ) {
			$replacements = array(
				'{product_units}' => str_replace( '.', ',', $this->get_unit_product() ),
				'{unit}'          => $this->get_formatted_unit(),
				'{unit_price}'    => $this->get_formatted_unit_price(),
			);

			$html = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

		/**
		 * Filter to adjust the product units HTML output.
		 *
		 * @param string $html The HTML output.
		 * @param WC_GZD_Order_Item_Product $order_item The order item product object.
		 *
		 * @since 3.1.12
		 *
		 */
		return apply_filters( 'woocommerce_gzd_order_item_product_units_html', $html, $this );
	}

	public function has_unit_product() {
		$products = $this->get_unit_product();

		return ( $products && ! empty( $products ) && $this->get_unit() );
	}

	public function get_unit_price() {
		return $this->order_item->get_meta( '_unit_price_raw', true );
	}

	public function set_unit_price( $price ) {
		$this->order_item->update_meta_data( '_unit_price_raw', wc_format_decimal( $price, '' ) );
	}

	public function get_unit_price_subtotal() {
		return $this->order_item->get_meta( '_unit_price_subtotal_raw', true );
	}

	public function set_unit_price_subtotal( $price ) {
		$this->order_item->update_meta_data( '_unit_price_subtotal_raw', wc_format_decimal( $price, '' ) );
	}

	public function get_unit_price_net() {
		return $this->order_item->get_meta( '_unit_price_net_raw', true );
	}

	public function set_unit_price_net( $price ) {
		$this->order_item->update_meta_data( '_unit_price_net_raw', wc_format_decimal( $price, '' ) );
	}

	public function get_unit_price_subtotal_net() {
		return $this->order_item->get_meta( '_unit_price_subtotal_net_raw', true );
	}

	public function set_unit_price_subtotal_net( $price ) {
		$this->order_item->update_meta_data( '_unit_price_subtotal_net_raw', wc_format_decimal( $price, '' ) );
	}

	public function get_quantity() {
		$quantity = $this->order_item->get_quantity();

		if ( empty( $quantity ) ) {
			$quantity = 1;
		}

		/**
		 * Determines the quantity used to calculate the item total used for unit price (re-) calculation for an order item.
		 *
		 * @param float $quantity The item quantity.
		 * @param WC_GZD_Order_Item_Product $gzd_order_item Germanized order item instance.
		 * @param WC_Order_Item_Product $order_item The order item instance.
		 *
		 * @since 3.7.3
		 */
		return apply_filters( 'woocommerce_gzd_order_item_quantity', $quantity, $this, $this->order_item );
	}

	public function recalculate_unit_price() {
		if ( ! $this->has_unit_price() ) {
			return false;
		}

		$quantity       = floatval( $this->get_quantity() );
		$net_total      = floatval( $this->order_item->get_total() ) / $quantity;
		$gross_total    = $net_total + ( floatval( $this->order_item->get_total_tax() ) / $quantity );
		$net_subtotal   = floatval( $this->order_item->get_subtotal() ) / $quantity;
		$gross_subtotal = $net_subtotal + ( floatval( $this->order_item->get_subtotal_tax() ) / $quantity );

		/**
		 * Prefer using (filterable) order methods to determine the item total in case the quantity
		 * used for unit price calculation has not been changed via filter.
		 *
		 * @see WC_GZD_Compatibility_WooCommerce_Measurement_Price_Calculator
		 */
		if ( ( $order = $this->order_item->get_order() ) && $this->get_quantity() === $this->order_item->get_quantity() ) {
			$net_total      = $order->get_item_total( $this->order_item, false, false );
			$gross_total    = $order->get_item_total( $this->order_item, true, false );
			$net_subtotal   = $order->get_item_subtotal( $this->order_item, false, false );
			$gross_subtotal = $order->get_item_subtotal( $this->order_item, true, false );
		}

		$prices_net = wc_gzd_recalculate_unit_price(
			array(
				'regular_price' => $net_total,
				'sale_price'    => $net_subtotal,
				'base'          => $this->get_unit_base(),
				'products'      => $this->get_unit_product(),
			)
		);

		$prices_gross = wc_gzd_recalculate_unit_price(
			array(
				'regular_price' => $gross_total,
				'sale_price'    => $gross_subtotal,
				'base'          => $this->get_unit_base(),
				'products'      => $this->get_unit_product(),
			)
		);

		$this->set_unit_price( $prices_gross['regular'] );
		$this->set_unit_price_subtotal( $prices_gross['sale'] );

		$this->set_unit_price_net( $prices_net['regular'] );
		$this->set_unit_price_subtotal_net( $prices_net['sale'] );

		/**
		 * Order item unit price recalculation
		 *
		 * This action fires before recalculating unit price for a certain order item (e.g. when taxes are recalculated).
		 *
		 * @param WC_Order_Item_Product     $order_item
		 * @param WC_GZD_Order_Item_Product $gzd_order_item
		 *
		 * @since 3.1.10
		 */
		do_action( 'woocommerce_gzd_recalculate_order_item_unit_price', $this->order_item, $this );

		return true;
	}
}
