<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

class WithdrawalItem extends \WC_Order_Item {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $extra_data = array(
		'parent_id'    => 0,
		'quantity'     => 1,
		'product_id'   => 0,
		'variation_id' => 0,
	);

	protected $parent = null;

	/**
	 * Get order item type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'withdrawal';
	}

	/**
	 * @param \WC_Order_Item_Product $item
	 *
	 * @return void
	 */
	public function from_order_item( $item ) {
		$this->set_parent_id( $item->get_id() );
		$this->set_name( $item->get_name() );
		$this->set_product_id( $item->get_product_id() );
		$this->set_variation_id( $item->get_variation_id() );
	}

	public function calculate_taxes( $calculate_tax_for = array() ) {
		return true;
	}

	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	public function set_parent_id( $parent_id ) {
		$this->set_prop( 'parent_id', absint( $parent_id ) );
		$this->parent = null;
	}

	/**
	 * Get product ID.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get variation ID.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_variation_id( $context = 'view' ) {
		return $this->get_prop( 'variation_id', $context );
	}

	/**
	 * Set Product ID
	 *
	 * @param int $value Product ID.
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Set variation ID.
	 *
	 * @param int $value Variation ID.
	 */
	public function set_variation_id( $value ) {
		$this->set_prop( 'variation_id', absint( $value ) );
	}

	/**
	 * Get the associated product.
	 *
	 * @return \WC_Product|bool
	 */
	public function get_product() {
		if ( $this->get_variation_id() ) {
			$product = wc_get_product( $this->get_variation_id() );
		} else {
			$product = wc_get_product( $this->get_product_id() );
		}

		return apply_filters( 'eu_owb_woocommerce_withdrawal_item_product', $product, $this );
	}

	/**
	 * @return \WC_Order_Item_Product|false
	 */
	public function get_parent() {
		if ( is_null( $this->parent ) ) {
			$this->parent = false;

			if ( $this->get_parent_id() > 0 ) {
				if ( $order = $this->get_order() ) {
					$this->parent = $order->get_item( $this->get_parent_id() );
				}
			}
		}

		return $this->parent;
	}

	/**
	 * Get quantity.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_quantity( $context = 'view' ) {
		return $this->get_prop( 'quantity', $context );
	}

	/**
	 * Set quantity.
	 *
	 * @param int $value Quantity.
	 */
	public function set_quantity( $value ) {
		$this->set_prop( 'quantity', wc_stock_amount( $value ) );
	}
}
