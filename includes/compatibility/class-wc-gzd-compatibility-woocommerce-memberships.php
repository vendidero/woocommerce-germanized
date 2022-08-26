<?php

defined( 'ABSPATH' ) || exit;

/**
 *
 * Ensure compatibility between WooCommerce Germanized and WooCommerce Memberships
 *
 * @class       WC_GZD_Compatibility_WooCommerce_Memberships
 * @category    Class
 * @author      René Haubner, retsch Internetagentur, www.retsch-it.de
 */
class WC_GZD_Compatibility_WooCommerce_Memberships extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'WooCommerce Memberships';
	}

	public static function get_path() {
		return 'woocommerce-memberships/woocommerce-memberships.php';
	}

	public function calculate_unit_price( $product ) {
		if ( ! function_exists( 'wc_memberships' ) ) {
			return;
		}

		if ( function_exists( 'wc_memberships_user_has_member_discount' ) ) {
			if ( wc_memberships_user_has_member_discount( $product->get_id() ) ) {
				$product->recalculate_unit_price(
					array(
						'sale_price'    => $product->get_sale_price(),
						'regular_price' => $product->get_regular_price(),
					)
				);
			}
		}
	}
}
