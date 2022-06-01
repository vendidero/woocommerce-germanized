<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Composite Products
 *
 * Specific configuration for WooCommerce Composite Products
 * https://woocommerce.com/products/composite-products/
 *
 * @author vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Composite_Products extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Composite Products';
	}

	public static function get_path() {
		return 'woocommerce-composite-products/woocommerce-composite-products.php';
	}

	public function load() {
		/**
		 * Add single product shopmarks right before the composite add to cart button.
		 * Currently there is no hook available which might be used to add the shopmarks right after price output.
		 */
		add_action( 'woocommerce_composite_add_to_cart_button', array( $this, 'output_composite_shopmarks' ), 0 );
	}

	public function output_composite_shopmarks() {
		foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$callback = $shopmark->get_callback();

			if ( function_exists( $callback ) && $shopmark->is_enabled() && in_array( $shopmark->get_type(), array( 'unit_price', 'legal', 'tax', 'shipping_costs' ), true ) ) {
				call_user_func( $callback );
			}
		}
	}
}
