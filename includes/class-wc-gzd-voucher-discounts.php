<?php

defined( 'ABSPATH' ) || exit;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Discounts', false ) ) {
	require_once WC_ABSPATH . 'includes/class-wc-discounts.php';
}

/**
 * Voucher discounts class.
 */
class WC_GZD_Voucher_Discounts extends WC_Discounts {

	/**
	 * @var WC_Coupon
	 */
	protected $coupon = null;

	/**
	 * @var WC_Cart|WC_Order
	 */
	protected $object = null;

	public function __construct( $object, $coupon ) {
		$this->coupon = $coupon;
		$this->object = $object;
	}

	protected function init() {
		if ( is_a( $this->object, 'WC_Cart' ) ) {
			$this->set_items_from_cart( $this->object );
		} elseif ( is_a( $this->object, 'WC_Order' ) ) {
			$this->set_items_from_order( $this->object );
		}
	}

	public function allow_free_shipping() {
		return apply_filters( 'woocommerce_gzd_voucher_includes_shipping_costs', $this->coupon->get_free_shipping( 'edit' ), $this->coupon );
	}

	public function apply_coupon( $coupon, $validate = true ) {
		$this->init();

		return parent::apply_coupon( $coupon, $validate );
	}

	public function set_items_from_cart( $cart ) {
		$this->items     = array();
		$this->discounts = array();

		if ( ! is_a( $cart, 'WC_Cart' ) ) {
			return;
		}

		$this->object = $cart;

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			$item                = new stdClass();
			$item->key           = $key;
			$item->object        = $cart_item;
			$item->product       = $cart_item['data'];
			$item->quantity      = $cart_item['quantity'];
			$item->price         = wc_add_number_precision( $cart_item['line_total'] ) + wc_add_number_precision( $cart_item['line_tax'] );
			$this->items[ $key ] = $item;
		}

		foreach ( $cart->get_fees() as $key => $fee ) {
			if ( $fee->amount <= 0 ) {
				continue;
			}

			if ( ! apply_filters( 'woocommerce_gzd_voucher_cart_allow_fee_reduction', true, $fee ) ) {
				continue;
			}

			$item           = new stdClass();
			$item->key      = 'fee_' . $key;
			$item->object   = null;
			$item->product  = false;
			$item->quantity = 1;
			$item->price    = wc_add_number_precision( $fee->amount );

			$this->items[ $item->key ] = $item;
		}

		if ( $this->allow_free_shipping() && $cart->get_shipping_total() > 0 ) {
			$item           = new stdClass();
			$item->key      = 'shipping';
			$item->object   = null;
			$item->product  = false;
			$item->quantity = 1;
			$item->price    = wc_add_number_precision( $cart->get_shipping_total() ) + wc_add_number_precision( $cart->get_shipping_tax() );

			$this->items[ $item->key ] = $item;
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );
	}

	public function set_items_from_order( $order ) {
		$this->items     = array();
		$this->discounts = array();

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$this->object = $order;
		$item_types   = array( 'line_item', 'fee' );

		if ( $this->allow_free_shipping() ) {
			$item_types[] = 'shipping';
		}

		foreach ( $order->get_items( $item_types ) as $order_item ) {
			if ( $order_item->get_total() <= 0 ) {
				continue;
			}

			if ( is_a( $order_item, 'WC_Order_Item_Fee' ) ) {
				if ( ! apply_filters( 'woocommerce_gzd_voucher_order_allow_fee_reduction', true, $order_item ) || $order_item->get_meta( '_code' ) === $this->coupon->get_code() ) {
					continue;
				}
			}

			$item           = new stdClass();
			$item->key      = $order_item->get_id();
			$item->object   = null;
			$item->product  = is_callable( $order_item, 'get_product' ) ? $order_item->get_product() : false;
			$item->quantity = $order_item->get_quantity();
			$item->price    = wc_add_number_precision_deep( $order_item->get_total() ) + wc_add_number_precision_deep( $order_item->get_total_tax() );

			$this->items[ $order_item->get_id() ] = $item;
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
	 * @since  3.2.0
	 * @param  object $coupon Coupon object.
	 * @return array
	 */
	protected function get_items_to_apply_coupon( $coupon ) {
		$items_to_apply = array();

		foreach ( $this->get_items_to_validate() as $item ) {
			$item_to_apply = clone $item; // Clone the item so changes to this item do not affect the originals.

			if ( 0 === $this->get_discounted_price_in_cents( $item_to_apply ) || 0 >= $item_to_apply->quantity ) {
				continue;
			}

			/**
			 * Make sure to check for WC_Product object existence.
			 */
			if ( $item_to_apply->product && ! $coupon->is_valid_for_product( $item_to_apply->product, $item_to_apply->object ) && ! $coupon->is_valid_for_cart() ) {
				continue;
			}

			$items_to_apply[] = $item_to_apply;
		}
		return $items_to_apply;
	}
}
