<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Measurement Price Calculator Compatibility
 *
 * @see https://woocommerce.com/products/measurement-price-calculator/
 */
class WC_GZD_Compatibility_WooCommerce_Measurement_Price_Calculator extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Measurement Price Calculator';
	}

	public static function get_path() {
		return 'woocommerce-measurement-price-calculator/woocommerce-measurement-price-calculator.php';
	}

	public function load() {
		/**
		 * Adjust unit price quantity to the quantity chosen by the customer.
		 */
		add_filter( 'woocommerce_gzd_unit_price_cart_quantity', function( $quantity, $cart_item, $gzd_product ) {
			if ( isset( $cart_item['pricing_item_meta_data'], $cart_item['pricing_item_meta_data']['_measurement_needed'] ) && ! empty( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) ) {
				$quantity = floatval( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) * floatval( $quantity );
			}

			return $quantity;
		}, 10, 3 );

		/**
		 * Make sure to (re-) calculate order item unit price based on the actual quantity chosen by the customer.
		 */
		add_filter( 'woocommerce_gzd_order_item_quantity', function( $quantity, $gzd_order_item ) {
			if ( $measurement_data = $gzd_order_item->get_meta( '_measurement_data' ) ) {
				if ( isset( $measurement_data['_measurement_needed'] ) && ! empty( $measurement_data['_measurement_needed']  ) ) {
					$quantity = floatval( $measurement_data['_measurement_needed'] ) * floatval( $quantity );
				}
			}

			return $quantity;
		}, 10, 2 );

		/**
		 * Do not output product units in case measurement is activated as the product content differs from the static unit price data available.
		 */
		add_filter( 'woocommerce_gzd_cart_product_units_html', function( $units_html, $cart_item ) {
			if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
				if ( $measurement_data = $cart_item->get_meta( '_measurement_data' ) ) {
					if ( isset( $measurement_data['_measurement_needed'] ) && ! empty( $measurement_data['_measurement_needed']  ) ) {
						$units_html = '';
					}
				}
			} elseif ( isset( $cart_item['data'] ) ) {
				if ( isset( $cart_item['pricing_item_meta_data'], $cart_item['pricing_item_meta_data']['_measurement_needed'] ) && ! empty( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) ) {
					$units_html = '';
				}
			}

			return $units_html;
		}, 10, 2 );
	}
}
