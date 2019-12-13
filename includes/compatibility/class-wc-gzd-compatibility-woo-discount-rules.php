<?php

/**
 * Woo Discount Rules
 *
 * Specific configuration for Woo Discount Rules
 * https://wordpress.org/plugins/woo-discount-rules/
 *
 * This plugin should be configured to not show discount on product pages as
 * this plugin does only adjust the visible price through WC price_html filters which does
 * not change the actual product price. For that reason Germanized is not able to adjust the
 * unit price accordingly on the single product page.
 */
class WC_GZD_Compatibility_Woo_Discount_Rules extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'Woo Discount Rules';
	}

	public static function get_path() {
		return 'woo-discount-rules/woo-discount-rules.php';
	}
}