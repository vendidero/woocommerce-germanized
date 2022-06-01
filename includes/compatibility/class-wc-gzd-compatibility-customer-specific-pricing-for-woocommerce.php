<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility script for https://wisdmlabs.com/docs/article/wisdm-customer-specific-pricing/csp-getting-started/csp-user-guide/specific-pricing-options-at-the-product-level/
 *
 * @class        WC_GZD_Compatibility_Customer_Specific_Pricing_For_WooCommerce
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Customer_Specific_Pricing_For_WooCommerce extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Customer Specific Pricing for WooCommerce';
	}

	public static function get_path() {
		return 'customer-specific-pricing-for-woocommerce/customer-specific-pricing-for-woocommerce.php';
	}

	public function load() {
		/**
		 * This plugin does not adjust product price via PHP but adds a completely separate price
		 * wrapper to the single product price page. This price wrapper contains the total product price (including discounts).
		 * Register a custom observer for the selector which is marked as containing a total price.
		 */
		add_filter(
			'woocommerce_gzd_unit_price_observer_params',
			function( $params ) {
				$params['refresh_on_load'] = true;

				$params['price_selector']['div#product_total_price'] = array(
					'is_total_price' => true,
				);

				return $params;
			}
		);
	}
}
