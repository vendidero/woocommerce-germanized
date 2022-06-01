<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooProduct class
 */
class WC_GZD_Order_Item {

	/**
	 * The actual order item object
	 *
	 * @var WC_Order_Item
	 */
	protected $order_item;

	/**
	 * @param WC_Order_Item $order_item
	 */
	public function __construct( $order_item ) {
		$this->order_item = $order_item;
	}

	/**
	 * Returns the Woo Order Item original object
	 *
	 * @return WC_Order_Item
	 */
	public function get_order_item() {
		return $this->order_item;
	}

	public function get_id() {
		return $this->order_item->get_id();
	}

	public function save() {
		return $this->order_item->save();
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->order_item, $method ) ) {
			return call_user_func_array( array( $this->order_item, $method ), $args );
		}

		return false;
	}
}
