<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Woocommerce_Dynamic_Pricing extends WC_GZD_Compatibility {

	public function __construct() {
		parent::__construct(
			'WooCommerce Dynamic Pricing',
			'woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php'
		);
	}

	public function load() {
		// Filter seems to be removed due to low priority
		remove_filter( 'woocommerce_cart_item_price', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'cart_product_unit_price' ), 3 );
		remove_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'cart_subtotal_unit_price' ), 3 );

		// Readd filter with higher priority
		add_filter( 'woocommerce_cart_item_price', 'wc_gzd_cart_product_unit_price', 500, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', 500, 3 );

		// Filters to recalculate unit price during cart/checkout
		add_action( 'woocommerce_before_cart', array( $this, 'set_unit_price_filter' ), 10 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'set_unit_price_filter' ), 10 );
		add_action( 'woocommerce_gzd_review_order_before_cart_contents', array( $this, 'set_unit_price_filter' ), 10 );

		// Recalculate unit price before adding order item meta
		add_filter( 'woocommerce_gzd_order_item_unit_price', array( $this, 'unit_price_order_item' ), 10, 4 );
	}

	public function unit_price_order_item( $price, $gzd_product, $item, $order ) {
		$product_price = $order->get_item_subtotal( $item, true );

		$gzd_product->recalculate_unit_price( array(
			'regular_price' => $product_price,
			'price' => $product_price,
		) );

		return $gzd_product->get_unit_html( false );
	}

	public function set_unit_price_filter() {
		add_filter( 'woocommerce_gzd_unit_price_including_tax', array( $this, 'calculate_unit_price' ), 10, 4 );
		add_filter( 'woocommerce_gzd_unit_price_excluding_tax', array( $this, 'calculate_unit_price' ), 10, 4 );
	}

	public function calculate_unit_price( $price, $single_price, $qty, $product ) {
		$product->recalculate_unit_price();
		return $price;
	}

}