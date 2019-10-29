<?php

/**
 * WPML Helper
 *
 * Specific configuration for Role Based Pricing
 * https://wordpress.org/plugins/woocommerce-role-based-price/
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Role_Based_Price extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'WooCommerce Role Based Price';
	}

	public static function get_path() {
		return 'woocommerce-role-based-price/woocommerce-role-based-price.php';
	}

}