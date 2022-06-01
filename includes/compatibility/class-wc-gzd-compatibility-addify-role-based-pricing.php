<?php

defined( 'ABSPATH' ) || exit;

/**
 * Addify Role Based Pricing Compatibility
 *
 * Specific configuration for Addify Role Based Pricing Compatibility
 * https://woocommerce.com/products/role-based-pricing-for-woocommerce/
 *
 */
class WC_GZD_Compatibility_Addify_Role_Based_Pricing extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'Role Based Pricing for WooCommerce';
	}

	public static function get_path() {
		return 'role-based-pricing-for-woocommerce/addify_role_based_pricing.php';
	}
}
