<?php

defined( 'ABSPATH' ) || exit;

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

	protected function hooks() {
		parent::hooks();

		add_filter( 'advanced_woo_discount_rules_custom_target_for_variable_product_on_qty_update', array( $this, 'register_variable_price_target' ), 50 );
		add_filter( 'advanced_woo_discount_rules_custom_target_for_simple_product_on_qty_update', array( $this, 'register_simple_price_target' ), 50 );
	}

	public function register_simple_price_target( $target ) {
		$target = 'div.product p.price:not(.price-unit)';

		return apply_filters( 'woocommerce_gzd_woo_discount_rules_simple_product_price_target', $target );
	}

	public function register_variable_price_target( $target ) {
		$params = WC_germanized()->get_variation_script_params();
		$target = $params['wrapper'] . ' ' . $params['price_selector'] . ':not(.price-unit):visible:first';

		return apply_filters( 'woocommerce_gzd_woo_discount_rules_variable_product_price_target', $target );
	}

	public static function get_name() {
		return 'Woo Discount Rules';
	}

	public static function get_path() {
		return 'woo-discount-rules/woo-discount-rules.php';
	}
}
