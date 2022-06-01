<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Compatibility_WooCommerce_All_Products_For_Subscriptions extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce All Products For Subscriptions';
	}

	public static function get_path() {
		return 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php';
	}

	public function load() {
		/**
		 * Make sure to prevent the plugin from adding its options to the
		 * price_html field.
		 *
		 * @see https://wordpress.org/support/topic/konflikt-mit-germanized-und-woocommerce-subscriptions/#post-13992198
		 */
		add_filter( 'wcsatt_modify_variation_data_price_html', '__return_false' );
	}
}
