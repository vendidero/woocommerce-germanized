<?php

defined( 'ABSPATH' ) || exit;

/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Role_Based_Prices extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'WooCommerce Role Based Prices';
	}

	public static function get_path() {
		return 'woocommerce-role-based-prices/woocommerce-role-based-prices.php';
	}
}
