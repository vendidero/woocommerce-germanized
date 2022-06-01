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
		 * This plugin does not adjust product price via PHP but adds a completely separate price
		 * wrapper to the single product price page. This price wrapper contains the total product price (including discounts).
		 * Register a custom observer for the selector which is marked as containing a total price.
		 */
		add_filter(
			'woocommerce_gzd_unit_price_observer_price_selectors',
			function( $price_selectors ) {

				return $price_selectors;
			}
		);

		add_filter(
			'woocommerce_gzd_unit_price_observer_params',
			function( $params ) {
				if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
					global $post;

					if ( $post && ( $product = wc_get_product( $post ) ) ) {
						if ( $measurement = $product->get_meta( '_wc_price_calculator' ) ) {
							if ( isset( $measurement['calculator_type'] ) && ! empty( $measurement['calculator_type'] ) ) {
								$params['refresh_on_load'] = true;

								$params['price_selector']['tr.calculated-price .product_price'] = array(
									'is_total_price'    => true,
									'quantity_selector' => '.amount_needed',
								);

							}
						}
					}
				}

				return $params;
			}
		);

		/**
		 * Adjust unit price quantity to the quantity chosen by the customer.
		 */
		add_filter(
			'woocommerce_gzd_unit_price_cart_quantity',
			function( $quantity, $cart_item, $gzd_product ) {
				if ( isset( $cart_item['pricing_item_meta_data'], $cart_item['pricing_item_meta_data']['_measurement_needed'] ) && ! empty( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) ) {
					$quantity = floatval( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) * floatval( $quantity );
				}

				return $quantity;
			},
			10,
			3
		);

		/**
		 * Make sure to (re-) calculate order item unit price based on the actual quantity chosen by the customer.
		 */
		add_filter(
			'woocommerce_gzd_order_item_quantity',
			function( $quantity, $gzd_order_item ) {
				if ( $measurement_data = $gzd_order_item->get_meta( '_measurement_data' ) ) {
					if ( isset( $measurement_data['_measurement_needed'] ) && ! empty( $measurement_data['_measurement_needed'] ) ) {
						$quantity = floatval( $measurement_data['_measurement_needed'] ) * floatval( $quantity );
					}
				}

				return $quantity;
			},
			10,
			2
		);

		/**
		 * Do not output product units in case measurement is activated as the product content differs from the static unit price data available.
		 */
		add_filter(
			'woocommerce_gzd_cart_product_units_html',
			function( $units_html, $cart_item ) {
				if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
					if ( $measurement_data = $cart_item->get_meta( '_measurement_data' ) ) {
						if ( isset( $measurement_data['_measurement_needed'] ) && ! empty( $measurement_data['_measurement_needed'] ) ) {
							$units_html = '';
						}
					}
				} elseif ( isset( $cart_item['data'] ) ) {
					if ( isset( $cart_item['pricing_item_meta_data'], $cart_item['pricing_item_meta_data']['_measurement_needed'] ) && ! empty( $cart_item['pricing_item_meta_data']['_measurement_needed'] ) ) {
						$units_html = '';
					}
				}

				return $units_html;
			},
			10,
			2
		);
	}
}
