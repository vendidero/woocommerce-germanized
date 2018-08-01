<?php
/**
 * WC GZD Role Based Pricing Compatibility Base Helper
 *
 * @class 		WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing
 * @category	Class
 * @author 		vendidero
 */
abstract class WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing extends WC_GZD_Compatibility {

	protected function hooks() {
		// Add filter to price output
		add_filter( 'woocommerce_get_price_html', array( $this, 'set_unit_price_product_filter' ), 200, 2 );

		// Filter seems to be removed due to low priority
		remove_filter( 'woocommerce_cart_item_price', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'cart_product_unit_price' ) );
		remove_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'cart_subtotal_unit_price' ) );

		// Readd filter with higher priority
		add_filter( 'woocommerce_cart_item_price', 'wc_gzd_cart_product_unit_price', 500, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', 500, 3 );

		// Filters to recalculate unit price during cart/checkout
		add_action( 'woocommerce_before_mini_cart', array( $this, 'set_unit_price_filter' ), 10 );
		add_action( 'woocommerce_before_cart', array( $this, 'set_unit_price_filter' ), 10 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'set_unit_price_filter' ), 10 );
		add_action( 'woocommerce_gzd_review_order_before_cart_contents', array( $this, 'set_unit_price_filter' ), 10 );

		// Recalculate unit price before adding order item meta
		add_filter( 'woocommerce_gzd_order_item_unit_price', array( $this, 'unit_price_order_item' ), 10, 4 );

		// Support variable products
		add_filter( 'woocommerce_gzd_get_variation_unit_prices_hash', array( $this, 'variable_unit_prices_hash' ), 10, 1 );
	}

	public function load() {
		$this->hooks();
	}

	public function set_unit_price_product_filter( $html, $product ) {
		$this->set_unit_price_filter();
		return $html;
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
		add_action( 'woocommerce_gzd_before_get_unit_price', array( $this, 'calculate_unit_price' ), 10, 1 );
		// Adjust variable from-to unit prices
		add_action( 'woocommerce_gzd_before_get_variable_variation_unit_price', array( $this, 'calculate_unit_price' ), 10, 1 );
	}

	public function calculate_unit_price( $product ) {
		$product->recalculate_unit_price();
	}

	public function variable_unit_prices_hash( $price_hash ) {
		// Get a key based on role, since all rules use roles.
		$session_id = null;

		$roles = array();
		if ( is_user_logged_in() ) {
			$user = new WP_User( get_current_user_id() );
			if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
				foreach ( $user->roles as $role ) {
					$roles[ $role ] = $role;
				}
			}
		}

		if ( ! empty( $roles ) ) {
			$session_id = implode( '', $roles );
		} else {
			$session_id = 'norole';
		}

		$price_hash[] = $session_id;

		return $price_hash;
	}

}