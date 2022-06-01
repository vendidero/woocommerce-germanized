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
class WC_GZD_Compatibility_WooCommerce_Dynamic_Pricing extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'WooCommerce Dynamic Pricing';
	}

	public static function get_path() {
		return 'woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php';
	}

	public function variable_unit_prices_hash( $hash ) {
		if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
			$instance = WC_Dynamic_Pricing::instance();

			if ( is_callable( array( $instance, 'on_woocommerce_get_variation_prices_hash' ) ) ) {
				return $instance->on_woocommerce_get_variation_prices_hash( $hash );
			}
		}

		return $hash;
	}
}
