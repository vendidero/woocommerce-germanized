<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Dynamic Pricing & Discounts
 *
 * @see https://support.rightpress.net/hc/en-us/categories/200133263-WooCommerce-Dynamic-Pricing-Discounts
 */
class WC_GZD_Compatibility_WC_Dynamic_Pricing_And_Discounts extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Dynamic Pricing & Discounts';
	}

	public static function get_path() {
		return 'wc-dynamic-pricing-and-discounts/wc-dynamic-pricing-and-discounts.php';
	}

	public function load() {
		add_filter(
			'woocommerce_gzd_unit_price_observer_params',
			function( $params ) {
				if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
					if ( class_exists( 'RP_WCDPD_Settings' ) && '0' !== RP_WCDPD_Settings::get( 'product_pricing_change_display_prices' ) ) {
						$params['refresh_on_load'] = true;
					}
				}

				return $params;
			}
		);
	}
}
